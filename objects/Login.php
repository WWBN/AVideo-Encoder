<?php

if (!class_exists('Login')) {
    require_once dirname(__FILE__) . '/../videos/configuration.php';
    require_once dirname(__FILE__) . '/Streamer.php';

    class Login {

        static function run($user, $pass, $youPHPTubeURL, $encodedPass = false) {
            ini_set('memory_limit', '50M');
            ini_set('max_execution_time', 10);
            global $global;
            $youPHPTubeURL = trim($youPHPTubeURL);
            if (substr($youPHPTubeURL, -1) !== '/') {
                $youPHPTubeURL .= "/";
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
            $url = $youPHPTubeURL . 'login';
            $result = url_get_contents($url, $context);
            if (empty($result)) {
                error_log("Get Login fail, try again");
                $result = file_get_contents($url, false, $context);
            }

            
            if (empty($result)) {
                $object = new stdClass();
                $object->streamer = false;
                $object->streamers_id = 0;
                $object->isLogged = false;
                $object->isAdmin = false;
                $object->canUpload = false;
                $object->canComment = false;
                $object->categories = array();
                error_log("Error on Login context");
                error_log($url);
                error_log($result);
            } else {
                $object = json_decode($result);
                if (!empty($object)) {
                    $object->streamer = $youPHPTubeURL;
                    $object->streamers_id = 0;
                    if (!empty($object->canUpload)) {
                        $object->streamers_id = Streamer::createIfNotExists($user, $pass, $youPHPTubeURL, $encodedPass);
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
                            $pass = encryptPassword($pass, $youPHPTubeURL);
                        }
                        // update pass
                        $s->setPass($pass);
                        $s->save();
                        $cookieLife = time() + 3600 * 24 * 2; // 2 day
                        setcookie("user", $user, $cookieLife, "/");
                        setcookie("pass", $pass, $cookieLife, "/");
                        setcookie("youPHPTubeURL", $youPHPTubeURL, $cookieLife, "/");
                    }
                } else {
                    $object = new stdClass();
                    error_log("Encoder Login Error: ".json_error().$result);
                }
            }
            $object->youPHPTubeURL = $url;
            $object->result = $result;
            $_SESSION['login'] = $object;
        }

        static function logoff() {
            unset($_SESSION['login']);
            setcookie('user', null, -1, "/");
            setcookie('pass', null, -1, "/");
            unset($_COOKIE['user']);
            unset($_COOKIE['pass']);
        }

        static function isLogged() {
            $isLogged = !empty($_SESSION['login']->isLogged);
            if (!$isLogged && !empty($_COOKIE['user']) && !empty($_COOKIE['pass']) && !empty($_COOKIE['youPHPTubeURL'])) {
                Login::run($_COOKIE['user'], $_COOKIE['pass'], $_COOKIE['youPHPTubeURL'], true);
            }
            return !empty($_SESSION['login']->isLogged);
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
            return self::isLogged() && !empty($_SESSION['login']->canUpload);
        }

        static function canComment() {
            return !empty($_SESSION['login']->canComment);
        }

        static function getStreamerURL() {
            if (!static::isLogged()) {
                return false;
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