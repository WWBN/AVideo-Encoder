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
            $sql = "SELECT * FROM  " . static::getTableName() . " WHERE user = '{$user}' AND lower(siteURL) = lower('{$siteURL}') LIMIT 1";
            //echo $sql;exit;            
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

        public function save()
        {
            if (!isset($this->priority)) {
                $this->priority = 6;
            }
            return parent::save();
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
            $s = new Streamer($streamers_id);
            $jsonString = $s->getJson();
            if (empty($jsonString)) {
                $response['msg'] = 'There is no token for this';
                return $response;
            } else {
                $json = json_decode($jsonString, true);
            }


            $response['accessToken'] = $json[$provider]['json']["restream.ypt.me"]['access_token'];
            if (empty($response['accessToken'])) {
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
            return $json['accessToken']["access_token"];
        }
    }
}
