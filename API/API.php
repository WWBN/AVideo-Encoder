<?php

class API
{
    static function checkCredentials()
    {
        $object = new stdClass();
        $object->error = true;
        $object->msg = '';
        $object->login = new stdClass();
        $object->login->user = @$_REQUEST['user'];
        $object->login->siteURL = @$_REQUEST['siteURL'];
        if (empty($object->login->user) || empty($_REQUEST['pass'])) {
            $object->msg = 'User and Password can not be blank';
            die(json_encode($object));
        }
        if (!Streamer::isURLAllowed($object->login->siteURL)) {
            $object->msg = 'This streamer site is not allowed';
            die(json_encode($object));
        }
        error_log('login.json: Login::run');
        Login::run($object->login->user, $_REQUEST['pass'], $object->login->siteURL, @$_REQUEST['encodedPass']);
        if (!empty($_SESSION['login'])) {
            $object->login->streamers_id = intval($_SESSION['login']->streamers_id);
        } else {
            $object->msg = 'Your site is banned';
            die(json_encode($object));
        }
        return $object;
    }
}
