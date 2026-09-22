<?php

if (!class_exists('Streamer')) {
    require_once dirname(__FILE__) . '/Configuration.php';

    class Streamer extends ObjectYPT
    {
        const RESTREAMER_URL = 'https://restream.ypt.me/';
        protected $id, $siteURL, $user, $pass, $priority, $isAdmin, $json, $created, $modified;

        static function getSearchFieldsNames()
        {
            return array('siteURL');
        }

        static function getTableName()
        {
            global $global;
            return $global['tablesPrefix'] . 'streamers';
        }

        private static function get($user, $siteURL)
        {
            global $global;
            if (empty($global)) {
                $global = [];
            }
            $sql = "SELECT * FROM " . static::getTableName() . " WHERE user = ? AND lower(siteURL) = lower(?) LIMIT 1";
            /**
             * @var array $global
             * @var object $global['mysqli']
             */
            $stmt = $global['mysqli']->prepare($sql);
            if (!$stmt) {
                _error_log('Streamer::get prepare failed: (' . $global['mysqli']->errno . ') ' . $global['mysqli']->error);
                return false;
            }

            $stmt->bind_param('ss', $user, $siteURL);
            if (!$stmt->execute()) {
                _error_log('Streamer::get execute failed: (' . $stmt->errno . ') ' . $stmt->error);
                $stmt->close();
                return false;
            }

            $res = $stmt->get_result();
            if (!$res) {
                _error_log('Streamer::get result failed: (' . $stmt->errno . ') ' . $stmt->error);
                $stmt->close();
                return false;
            }

            $row = $res->fetch_assoc();
            $res->free();
            $stmt->close();
            return $row ?: false;
        }

        private static function getFirst()
        {
            global $global;
            if (empty($global)) {
                $global = [];
            }
            $sql = "SELECT * FROM  " . static::getTableName() . " LIMIT 1";

            /**
             * @var array $global
             * @var object $global['mysqli']
             */
            $res = $global['mysqli']->query($sql);
            if ($res) {
                return $res->fetch_assoc();
            } else {
                die($sql . '\nError : (' . $global['mysqli']->errno . ') ' . $global['mysqli']->error);
            }
            return false;
        }

        static function getFirstURL()
        {
            global $global;
            if (!empty($global['forceStreamerURL'])) {
                return $global['forceStreamerURL'];
            }
            $row = static::getFirst();
            return $row['siteURL'];
        }

        static function getStreamerURL()
        {
            global $global;
            if (!empty($global['forceStreamerSiteURL'])) {
                return $global['forceStreamerSiteURL'];
            }
            $streamerURL = @$_REQUEST['webSiteRootURL'];
            if (empty($streamerURL)) {
                if (!empty($_SESSION['login']) && !empty($_SESSION['login']->streamer)) {
                    $streamerURL = $_SESSION['login']->streamer;
                } else {
                    $streamerURL = Streamer::getFirstURL();
                }
            }
            $streamerURL = addLastSlash($streamerURL);
            return $streamerURL;
        }

        static function createIfNotExists($user, $pass, $siteURL, $encodedPass = false)
        {
            error_log("createIfNotExists:: $user");
            if (substr($siteURL, -1) !== '/') {
                $siteURL .= "/";
            }
            if (!$encodedPass || $encodedPass === 'false') {
                $pass = encryptPassword($pass, $siteURL);
            }
            if ($row = static::get($user, $siteURL)) {
                if (!empty($row['id'])) {
                    return $row['id'];
                }
            }

            if (static::isURLAllowed($siteURL)) {
                $config = new Configuration();
                $s = new Streamer('');
                $s->setUser($user);
                $s->setPass($pass);
                $s->setSiteURL($siteURL);
                $s->setIsAdmin(0);
                $s->setPriority($config->getDefaultPriority());
                return $s->save();
            } else {
                return false;
            }
        }

        protected function requestAuthentication($credential, $encoded)
        {
            $url = addLastSlash($this->getSiteURL()) . 'objects/login.json.php';
            if (strtolower((string) parse_url($url, PHP_URL_SCHEME)) !== 'https'
                || empty(getExternalHttpUrlForShell($url, 'Streamer::refreshAuthentication'))) {
                return false;
            }
            $curl = curl_init($url);
            curl_setopt_array($curl, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query(['user' => $this->getUser(), 'pass' => $credential, 'encodedPass' => $encoded ? 1 : 0]),
                CURLOPT_USERAGENT => getSelfUserAgent(),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            ]);
            $body = curl_exec($curl);
            $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            return $status === 200 ? json_decode($body) : false;
        }

        public function refreshAuthentication(
            #[\SensitiveParameter]
            $password = null
        ) {
            $result = ['error' => true, 'msg' => 'Could not verify access to the site. Try again later.'];
            $user = $this->getUser();
            $site = $this->getSiteURL();
            // Authenticate in a separate HTTP request; keep the current encoder session
            // and its admin/owner identity unchanged. Never send credentials in the URL.
            $response = $this->requestAuthentication($password === null ? $this->getPass() : $password, $password === null);
            if (!is_object($response) || !isset($response->isLogged)) {
                return $result;
            }
            if (empty($response->isLogged)) {
                $result['authentication_required'] = true;
                $result['account'] = $user;
                $result['site'] = $site;
                $result['msg'] = $password === null
                    ? 'Access to the site has expired or was revoked. Enter this account password to renew it.'
                    : 'Login was not accepted. Check the password or sign in through the site.';
                return $result;
            }
            if (empty($response->canUpload) || empty($response->user) || strcasecmp($response->user, $user) !== 0 || empty($response->pass)) {
                $result['msg'] = 'The site did not authorize uploads for this account.';
                return $result;
            }
            // Reload before saving so renewal cannot overwrite concurrent account edits.
            if (!$this->load($this->getId()) || $this->getUser() !== $user || $this->getSiteURL() !== $site) {
                return $result;
            }
            $this->setPass($response->pass);
            if (!$this->save()) {
                return $result;
            }
            return ['error' => false, 'msg' => 'Access renewed.'];
        }

        public function save()
        {
            if (!isset($this->priority)) {
                $this->priority = 6;
            }
            $id = parent::save();
            // Ensure every streamer site is registered on search.avideo.com.
            // verify() uses a 1 hour cache, so repeated saves won't spam the HTTP endpoint.
            if (!empty($this->getSiteURL())) {
                $this->verify();
            }
            return $id;
        }

        function verify()
        {
            $timeout = 5;
            ini_set('default_socket_timeout', $timeout);
            $url = $this->getSiteURL();
            $cacheFile = _sys_get_temp_dir() . "/" . md5($url) . "_verify.log";
            $lifetime = 3600; //1 hour
            error_log("Verification Start {$url}");
            $verifyURL = "";
            if (!file_exists($cacheFile) || (time() > (filemtime($cacheFile) + $lifetime))) {
                error_log("Verification Creating the Cache {$url}");
                $verifyURL = "https://search.avideo.com/verify.php?url=" . urlencode($url);
                $result = url_get_contents($verifyURL, '', 5);
                $bytes = file_put_contents($cacheFile, $result);
                $json = json_decode($result);
                if (empty($json)) {
                    error_log("Verification: error on get result: {$result}");
                    if (!unlink($cacheFile)) {
                        error_log("Verification: could not delete the file: {$cacheFile}");
                    }
                    return false;
                }
            } else {
                error_log("Verification GetFrom Cache {$url}");
                $result = url_get_contents($cacheFile);
                $json = json_decode($result);
                if (empty($json)) {
                    error_log("Verification: error on get cached result: {$cacheFile} {$result}");
                    if (unlink($cacheFile)) {
                        error_log("Verification: try again: {$cacheFile} {$result}");
                        return $this->verify();
                    } else {
                        error_log("Verification: could not delete the file: {$cacheFile} {$result}");
                        return false;
                    }
                }
            }
            error_log("Verification Response ($verifyURL): {$result}");
            return $json;
        }

        static function isURLAllowed($siteURL)
        {
            if (substr($siteURL, -1) !== '/') {
                $siteURL .= "/";
            }
            $config = new Configuration();
            $urls = $config->getAllowedStreamersURL();
            if (empty($urls)) {
                return true;
            }
            $allowed = explode(PHP_EOL, $urls);
            $allowed[] = "http://localhost/AVideo/";
            $allowed[] = "http://127.0.0.1/AVideo/";
            $allowed[] = "https://localhost/AVideo/";
            $allowed[] = "https://127.0.0.1/AVideo/";

            $return = false;

            $siteURL = str_replace('https://', '', $siteURL);
            $siteURL = str_replace('http://', '', $siteURL);

            if (empty($allowed)) {
                $return = true;
            } else {
                foreach ($allowed as $value) {
                    if (empty($value)) {
                        continue;
                    }
                    $value = trim($value);
                    if (substr($value, -1) !== '/') {
                        $value .= "/";
                    }

                    $value = str_replace('https://', '', $value);
                    $value = str_replace('http://', '', $value);
                    //var_dump($siteURL,$value);
                    error_log("$siteURL == $value");
                    if ($siteURL == $value) {
                        $return = true;
                        break;
                    }
                }
            }
            return $return;
        }

        function getId()
        {
            return $this->id;
        }

        function getSiteURL()
        {
            global $global;
            if (!empty($global['forceStreamerSiteURL'])) {
                return trim($global['forceStreamerSiteURL']);
            }
            return trim($this->siteURL);
        }

        function getUser()
        {
            return $this->user;
        }

        function getPass()
        {
            return $this->pass;
        }

        function getCreated()
        {
            return $this->created;
        }

        function getModified()
        {
            return $this->modified;
        }

        function setId($id)
        {
            $this->id = $id;
        }

        function setSiteURL($siteURL)
        {
            if (!empty($siteURL) && substr($siteURL, -1) !== '/') {
                $siteURL .= "/";
            }
            $this->siteURL = $siteURL;
        }

        function setUser($user)
        {
            $this->user = $user;
        }

        function setPass($pass)
        {
            $config = new Configuration();
            if (version_compare($config->getVersion(), '4.0') < 0) {
                $pass = substr($pass, 0, 45);
            }else{
                $pass = substr($pass, 0, 255);
            }
            $this->pass = $pass;
        }

        function setCreated($created)
        {
            $this->created = $created;
        }

        function setModified($modified)
        {
            $this->modified = $modified;
        }

        function getPriority()
        {
            return $this->priority;
        }

        function setPriority($priority)
        {
            $this->priority = $priority;
        }

        function getIsAdmin()
        {
            return $this->isAdmin;
        }

        function setIsAdmin($isAdmin)
        {
            $this->isAdmin = $isAdmin;
        }

        function getJson()
        {
            return $this->json;
        }

        function setJson($json)
        {
            if (!is_string($json)) {
                $json = json_encode($json);
            }
            $this->json = $json;
        }

        static function revalidateToken($streamers_id, $provider)
        {
            $response = array(
                'error' => true,
                'msg' => '',
                'provider' =>  $provider,
            );

            if (empty($provider)) {
                $response['msg'] = "Provider is empty";
                return $response;
            }

            $s = new Streamer($streamers_id);
            $jsonString = $s->getJson();
            if (empty($jsonString)) {
                $response['msg'] = "There is no token for this streamers_id = $streamers_id [$provider] ".json_encode(debug_backtrace());
                return $response;
            } else {
                $json = json_decode($jsonString, true);
            }
            $response['json'] = $json;
            if (empty($json[$provider]['json']["restream.ypt.me"])) {
                $response['msg'] = "No restream token found for provider '$provider' on streamers_id = $streamers_id";
                return $response;
            }
            if(empty($json[$provider]['json']["restream.ypt.me"]['access_token'])){
                $response['accessToken'] = $json[$provider]['json']["restream.ypt.me"]['accessToken'] ?? null;
            }else{
                $response['accessToken'] = $json[$provider]['json']["restream.ypt.me"]['access_token'];
            }

            if (empty($response['accessToken'])) {
                _error_log(json_encode($json));
                $response['msg'] = "revalidateToken($streamers_id, $provider) access_token is empty ";
                return $response;
            }
            /*
            $response['expires_at'] = $json[$provider]['json']["restream.ypt.me"]["expires"]["expires_at"];
            if (time() <= $response['expires_at']) {
                $response['msg'] = "Not expired yet";
                return $response;
            }
            */

            $access_token = base64_encode(json_encode($response['accessToken']));

            $url = Streamer::RESTREAMER_URL . 'refresh.json.php';
            $url = addQueryStringParameter($url, 'access_token', $access_token);
            $response['url'] = addQueryStringParameter($url, 'provider', $response['provider']);
            //var_dump($pub['publisher_social_medias_id'], $response['error']provider, $url);exit;

            $response['resp'] = url_get_contents($response['url']);
            if (empty($response['resp'])) {
                $response['msg'] = "revalidateToken($streamers_id, $provider) response is empty";
                return $response;
            }

            $response['respJson'] = json_decode($response['resp'], true);

            $response['error'] = empty($response['respJson']) || $response['respJson']['error'];
            $response['msg'] = empty($response['respJson']) ? 'Empty response' : $response['respJson']['msg'];
            $response['saved'] = false;
            if(empty($response['error'] ) && !empty($response['respJson']['new_access_token'])){
                $json[$provider]['json']["restream.ypt.me"]['accessToken'] = $response['respJson']['new_access_token'];
                $json[$provider]['json']["restream.ypt.me"]['expires'] = $response['respJson']['expires'];

                $response['accessToken'] = $response['respJson']['new_access_token'];
                $response['expires'] = $response['respJson']['expires'];

                $s->setJson($json);
                $response['saved'] = $s->save();
            }

            return $response;
        }

        static function getAccessToken($streamers_id, $provider){
            $json = self::revalidateToken($streamers_id, $provider);
            //var_dump($json);exit;
            if(empty($json['accessToken']["access_token"])){
                _error_log(json_encode($json));
                return false;
            }
            return $json['accessToken']["access_token"];
        }
    }
}
