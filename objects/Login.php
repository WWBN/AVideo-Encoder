<?php

header('Set-Cookie: cross-site-cookie=name; SameSite=None; Secure');
if (!class_exists('Login')) {
    require_once dirname(__FILE__) . '/../videos/configuration.php';
    require_once dirname(__FILE__) . '/Streamer.php';

    class Login {

        private static function modifyUrl($url) {
            $url = str_ireplace(array('rtmp://'), array(''), $url);
            $url = str_ireplace(array('https://https://'), array('https://'), $url);
            if (strpos($url, '/live?p=') !== false) {
                $parsedUrl = parse_url($url);
                $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : '';

                // Remove everything after the last '/' before '/live?p='
                $path = substr($path, 0, strrpos($path, '/live?p='));

                return $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $path . '/';
            }
            return $url;
        }

        static function run(
            $user,
            #[\SensitiveParameter]
            $pass,
            $aVideoURL,
            $encodedPass = false
        ) {
            global $_runLogin;
            $aVideoURL = self::modifyUrl($aVideoURL);
            $index = "$user, $pass, $aVideoURL";
            if (!isset($_runLogin)) {
                $_runLogin = array();
            }
            if (empty($_runLogin[$index])) {
                ini_set('memory_limit', '50M');
                ini_set('max_execution_time', 10);
                error_log("Login::run ($user, ***, $aVideoURL, $encodedPass)");
                global $global;
                $agent = getSelfUserAgent();
                $aVideoURL = trim($aVideoURL);
                if (substr($aVideoURL, -1) !== '/') {
                    $aVideoURL .= "/";
                }
                $postdata = http_build_query(
                        array(
                            'user' => $user,
                            'pass' => $pass,
                            'encodedPass' => $encodedPass
                        )
                );

                $opts = array(
                    "ssl" => array(
                        "verify_peer" => false,
                        "verify_peer_name" => false,
                        "allow_self_signed" => true
                    ),
                    'http' => array(
                        'method' => 'POST',
                        'header' => array(
                            "Content-type: application/x-www-form-urlencoded\r\n",
                            "User-Agent: {$agent}\r\n"),
                        'content' => $postdata
                    ),
                );

                $context = stream_context_create($opts);
                $url = $aVideoURL . 'login?user=' . urlencode($user) . '&pass=' . urlencode($pass) . '&encodedPass=' . urlencode($encodedPass);

                //echo $url;exit;
                error_log("Login::run request login user ($user)");
                $result = url_get_contents($url, $context);
                error_log("Login::run request login complete user ($user)");
                if (empty($result)) {
                    error_log("Get Login fail, try again user ($user)");
                    $result = url_get_contents($url);
                }

                //error_log("Login::run response: ($result)");
                if (empty($result)) {
                    $object = new stdClass();
                    $object->streamer = false;
                    $object->streamers_id = 0;
                    $object->isLogged = false;
                    $object->isStreamerAdmin = false;
                    $object->isAdmin = false;
                    $object->canUpload = false;
                    $object->canComment = false;
                    $object->canCreateCategory = false;
                    $object->theme = '';
                    $object->categories = array();
                    $object->userGroups = array();
                    error_log("Login::run Error on Login context");
                    error_log($url);
                    //error_log($result);
                } else {
                    $result = remove_utf8_bom($result);
                    $object = json_decode($result);
                    if (!empty($object)) {
                        error_log("Login::run got an object");
                        if(!empty($object->streamer)){
                            $object->streamerDetails = $object->streamer;
                        }else{
                            $object->streamerDetails = false;
                        }
                        $object->streamer = $aVideoURL;
                        $object->streamers_id = 0;
                        if (!empty($object->canUpload) || !empty($object->isAdmin)) {
                            $object->streamers_id = Streamer::createIfNotExists($user, $pass, $aVideoURL, $encodedPass);
                        }
                        if ($object->streamers_id) {
                            $s = new Streamer($object->streamers_id);
                            $resultV = $s->verify();
                            if (!empty($resultV) && !$resultV->verified) {
                                error_log("Error on Login not verified");
                                return false;
                            }

                            $object->isStreamerAdmin = $object->isAdmin;
                            $object->isAdmin = $s->getIsAdmin();
                            if (!$encodedPass || $encodedPass === 'false') {
                                $pass = encryptPassword($pass, $aVideoURL);
                            }
                            // update pass
                            $s->setPass($object->pass);
                            $s->save();
                            $cookieLife = time() + 3600 * 24 * 2; // 2 day
                            setcookie("encoder_user", $user, $cookieLife, "/");
                            setcookie("encoder_pass", $pass, $cookieLife, "/");
                            setcookie("aVideoURL", $aVideoURL, $cookieLife, "/");
                            error_log("Login:: almost done");
                        }
                    } else {
                        $object = new stdClass();
                        error_log("Encoder Login Error: " . json_error()." $result");
                    }
                }
                $object->aVideoURL = $url;
                $object->result = $result;
            } else {
                $object = $_runLogin[$index];
            }
            _session_start();
            $object->PHPSESSID = session_id(); // to allow cross domain logins
            $_SESSION['login'] = $object;
            error_log("Login:: done session_id = " . session_id() . " session_login ");
        }

        static function logoff() {
            error_log("logoff:: done session_id = " . session_id());
            unset($_SESSION['login']);
            setcookie('encoder_user', '', -1, "/");
            setcookie('encoder_pass', '', -1, "/");
            unset($_COOKIE['encoder_user']);
            unset($_COOKIE['encoder_pass']);
        }

        static function isLogged() {
            $isLogged = !empty($_SESSION['login']->isLogged);
            if (!$isLogged && !empty($_COOKIE['encoder_user']) && !empty($_COOKIE['encoder_pass']) && !empty($_COOKIE['encoder_aVideoURL'])) {
                error_log("isLogged: Login::run");
                Login::run($_COOKIE['encoder_user'], $_COOKIE['encoder_pass'], $_COOKIE['encoder_aVideoURL'], true);
            } else if (!$isLogged && !empty($_SESSION['login'])) {
                error_log("isLogged: false ");
            }
            if (!empty($_GET['justLogin'])) {
                //$_GET['justLogin'] = $_SESSION['login']->isLogged;
                //error_log("isLogged:: session_login = " . json_encode($_SESSION['login']->isLogged));
            }
            return $isLogged;
        }

        static function isAdmin() {
            return !empty($_SESSION['login']->isAdmin);
        }

        static function isStreamerAdmin() {
            return !empty($_SESSION['login']->isStreamerAdmin);
        }

        static function canBulkEncode() {
            global $global;
            if (self::isAdmin() || empty($global['disableBulkEncode'])) {
                return true;
            }
            return false;
        }

        static function canUpload() {
            //error_log("canUpload: ". json_encode($_SESSION['login']));
            return self::isAdmin() || self::isStreamerAdmin() || (self::isLogged() && !empty($_SESSION['login']->canUpload));
        }

        static function canStream() {
            //error_log("canUpload: ". json_encode($_SESSION['login']));
            return self::isAdmin() || self::isStreamerAdmin() || (self::isLogged() && !empty($_SESSION['login']->canStream));
        }

        static function canComment() {
            return !empty($_SESSION['login']->canComment);
        }

        static function canCreateCategory() {
            return self::isStreamerAdmin() || !empty($_SESSION['login']->canCreateCategory);
        }

        static function getTheme() {
            return !empty($_SESSION['login']->theme);
        }

        static function getStreamerURL() {
            return Streamer::getStreamerURL();;
        }

        static function getStreamerUser() {
            if (!static::isLogged()) {
                return false;
            }
            global $global;
            return $_SESSION['login']->user;
        }

        static function getStreamerPass() {
            if (!static::isLogged()) {
                return false;
            }
            global $global;
            return $_SESSION['login']->pass;
        }

        static function getStreamerUserId() {
            if (!static::isLogged()) {
                return false;
            }
            return intval($_SESSION['login']->id);
        }

        static function getStreamerId() {
            if (!static::isLogged()) {
                return 0;
            }
            return intval($_SESSION['login']->streamers_id);
        }

        /**
         * Get allowed resolutions for the logged user from login session
         * @return array|null Array of allowed resolutions or null if not set
         */
        static function getAllowedResolutions($streamersId) {
            _error_log("Login::getAllowedResolutions - isLogged: " . json_encode(static::isLogged()));
            _error_log("Login::getAllowedResolutions - Session login data: " . json_encode($_SESSION['login'] ?? 'no session'));

            // First, check if we have a logged user with valid allowedResolutions
            if (static::isLogged() && !empty($_SESSION['login']->allowedResolutions) && is_array($_SESSION['login']->allowedResolutions)) {
                _error_log("Login::getAllowedResolutions - Found allowedResolutions: " . json_encode($_SESSION['login']->allowedResolutions));
                return $_SESSION['login']->allowedResolutions;
            }

            // If user is not logged in OR allowedResolutions is null/empty, try to re-login using database streamer credentials
            if (!static::isLogged()) {
                _error_log("Login::getAllowedResolutions - User not logged in, attempting re-login with database credentials");
            } else {
                _error_log("Login::getAllowedResolutions - allowedResolutions is null, attempting re-login with database credentials");
            }

            if (self::retryLoginWithDatabaseCredentials($streamersId)) {
                // After re-login, check again for allowedResolutions
                if (static::isLogged() && !empty($_SESSION['login']->allowedResolutions) && is_array($_SESSION['login']->allowedResolutions)) {
                    _error_log("Login::getAllowedResolutions - Found allowedResolutions after re-login: " . json_encode($_SESSION['login']->allowedResolutions));
                    return $_SESSION['login']->allowedResolutions;
                }
            }

            _error_log("Login::getAllowedResolutions - No allowedResolutions found even after re-login attempt");
            return null;
        }

        /**
         * Attempt to re-login using database streamer credentials
         * @return bool True if re-login was successful, false otherwise
         */
        private static function retryLoginWithDatabaseCredentials($streamersId) {
            try {
                if (!$streamersId) {
                    _error_log("Login::retryLoginWithDatabaseCredentials - No streamers_id found");
                    return false;
                }

                // Load streamer from database
                require_once __DIR__ . '/Streamer.php';
                $streamer = new Streamer($streamersId);
                if (!$streamer->getId()) {
                    _error_log("Login::retryLoginWithDatabaseCredentials - Streamer not found in database: $streamersId");
                    return false;
                }

                $user = $streamer->getUser();
                $pass = $streamer->getPass(); // This should be the encoded password
                $streamerURL = $streamer->getStreamerURL();

                if (empty($user) || empty($pass) || empty($streamerURL)) {
                    _error_log("Login::retryLoginWithDatabaseCredentials - Missing credentials: user=$user, pass=" . (empty($pass) ? 'empty' : 'set') . ", url=$streamerURL");
                    return false;
                }

                _error_log("Login::retryLoginWithDatabaseCredentials - Attempting re-login with user: $user, url: $streamerURL");

                // Perform login with encoded password
                self::run($user, $pass, $streamerURL, true);

                // Check if login was successful and we now have allowedResolutions
                if (self::isLogged() && isset($_SESSION['login']->allowedResolutions)) {
                    _error_log("Login::retryLoginWithDatabaseCredentials - Re-login successful");
                    return true;
                }

                _error_log("Login::retryLoginWithDatabaseCredentials - Re-login failed or no allowedResolutions returned");
                return false;

            } catch (Exception $e) {
                _error_log("Login::retryLoginWithDatabaseCredentials - Exception: " . $e->getMessage());
                return false;
            }
        }    }

}
