<?php

if (!class_exists('Login')) {
    require_once dirname(__FILE__) . '/../videos/configuration.php';
    require_once dirname(__FILE__) . '/Streamer.php';

    class Login {

        static function run($user, $pass, $aVideoURL, $encodedPass = false) {
            ini_set('memory_limit', '50M');
            ini_set('max_execution_time', 10);
            error_log("Login::run ($user, ***, $aVideoURL, $encodedPass)");
            global $global;
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
                    'header' => 'Content-type: application/x-www-form-urlencoded',
                    'content' => $postdata
                )
            );

            $context = stream_context_create($opts);
            $url = $aVideoURL . 'login';
            $result = url_get_contents($url, $context);
            if (empty($result)) {
                error_log("Get Login fail, try again");
                $result = url_get_contents($url, $context);
            }

            
            error_log("Login::run response: ($result)");
            if (empty($result)) {
                $object = new stdClass();
                $object->streamer = false;
                $object->streamers_id = 0;
                $object->isLogged = false;
                $object->isAdmin = false;
                $object->canUpload = false;
                $object->canComment = false;
                $object->categories = array();
                $object->userGroups = array();
                error_log("Login::run Error on Login context");
                error_log($url);
                error_log($result);
            } else {
                $result = remove_utf8_bom($result);
                $object = json_decode($result);
                if (!empty($object)) {
                    error_log("Login::run got an object");
                    $object->streamer = $aVideoURL;
                    $object->streamers_id = 0;
                    if (!empty($object->canUpload)) {
                        $object->streamers_id = Streamer::createIfNotExists($user, $pass, $aVideoURL, $encodedPass);
                    }
                    if ($object->streamers_id) {
                        $s = new Streamer($object->streamers_id);
                        $resultV = $s->verify();
                        if (!empty($resultV) && !$resultV->verified) {
                            error_log("Error on Login not verified");
                            return false;
                        }

                        $object->isAdmin = $s->getIsAdmin();
                        if (!$encodedPass || $encodedPass === 'false') {
                            $pass = encryptPassword($pass, $aVideoURL);
                        }
                        // update pass
                        $s->setPass($pass);
                        $s->save();
                        $cookieLife = time() + 3600 * 24 * 2; // 2 day
                        setcookie("user", $user, $cookieLife, "/");
                        setcookie("pass", $pass, $cookieLife, "/");
                        setcookie("aVideoURL", $aVideoURL, $cookieLife, "/");
                        error_log("Login:: almost done");
                    }
                } else {
                    $object = new stdClass();
                    error_log("Encoder Login Error: ".json_error().$result);
                }
            }
            $object->aVideoURL = $url;
            $object->result = $result;
            _session_start();
            $_SESSION['login'] = $object;
            error_log("Login:: done session_id = ". session_id()." session_login ". json_encode($_SESSION['login']));
        }

        static function logoff() {
            error_log("logoff:: done session_id = ". session_id());
            unset($_SESSION['login']);
            setcookie('user', null, -1, "/");
            setcookie('pass', null, -1, "/");
            unset($_COOKIE['user']);
            unset($_COOKIE['pass']);
        }

        static function isLogged() {
            $isLogged = !empty($_SESSION['login']->isLogged);
            if (!$isLogged && !empty($_COOKIE['user']) && !empty($_COOKIE['pass']) && !empty($_COOKIE['aVideoURL'])) {
                error_log("isLogged: Login::run");
                Login::run($_COOKIE['user'], $_COOKIE['pass'], $_COOKIE['aVideoURL'], true);
            }
            $islogged = !empty($_SESSION['login']->isLogged);
            if(!empty($_GET['justLogin'])){
                error_log("isLogged:: session_id = ". session_id());
            }
            return $isLogged;
        }

        static function isAdmin() {
            return !empty($_SESSION['login']->isAdmin);
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
            return self::isAdmin() || (self::isLogged() && !empty($_SESSION['login']->canUpload));
        }
        static function canStream() {
            //error_log("canUpload: ". json_encode($_SESSION['login']));
            return self::isAdmin() || (self::isLogged() && !empty($_SESSION['login']->canStream));
        }

        static function canComment() {
            return !empty($_SESSION['login']->canComment);
        }

        static function getStreamerURL() {
            if (!static::isLogged()) {
                return false;
            }
            global $global;
            if(!empty($global['forceStreamerSiteURL'])){
                return $global['forceStreamerSiteURL'];
            }
            return $_SESSION['login']->streamer;
        }

        static function getStreamerId() {
            if (!static::isLogged()) {
                return false;
            }
            return $_SESSION['login']->streamers_id;
        }

    }

}