<?php


require_once __DIR__ . '/../objects/Login.php';
require_once __DIR__ . '/../objects/Encoder.php';
require_once __DIR__ . '/../objects/Streamer.php';
require_once __DIR__ . '/../objects/functions.php';

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

    static function canChangeQueue($queue_id)
    {
        if (empty($_SESSION['login'])) {
            return false;
        }
        $streamer = new Streamer($_SESSION['login']->streamers_id);
        if (self::isAdmin()) {
            return true;
        }
        $encoder = new Encoder($queue_id);
        return $encoder->getStreamers_id() == $_SESSION['login']->streamers_id;
    }

    static function isAdmin()
    {
        if (empty($_SESSION['login'])) {
            return false;
        }
        $streamer = new Streamer($_SESSION['login']->streamers_id);
        return !empty($streamer->getIsAdmin());
    }

    static function cleanQueueArray($queue)
    {
        // Convert object to array if necessary
        if (is_object($queue)) {
            $queue = (array)$queue;
        }

        // Set videos_id
        if (isset($queue['return_vars']->videos_id)) {
            $queue['videos_id'] = $queue['return_vars']->videos_id;
        }

        $keep = array(
            'id',
            'created',
            'modified',
            'videos_id',
            'priority',
            'videoDownloadedLink',
            'downloadedFileName',
            'streamers_id',
            'conversion',
            'download',
            'title',
        );

        // Clean the queue
        foreach ($queue as $key => $value) {
            if (!in_array($key, $keep)) {
                unset($queue[$key]);
            }
        }

        return $queue;
    }
}
