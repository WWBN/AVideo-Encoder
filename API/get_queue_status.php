<?php
//OK
require_once __DIR__ . '/../videos/configuration.php';
header('Content-Type: application/json');

require_once __DIR__ . '/../objects/Login.php';
require_once __DIR__ . '/../objects/Encoder.php';
require_once __DIR__ . '/API.php';

$object = API::checkCredentials();

$status = array(
    Encoder::$STATUS_ENCODING, 
    Encoder::$STATUS_DOWNLOADING,
    Encoder::$STATUS_DOWNLOADED,
    Encoder::$STATUS_QUEUE,
    Encoder::$STATUS_ERROR,
    Encoder::$STATUS_DONE,
    Encoder::$STATUS_TRANSFERRING,
    Encoder::$STATUS_PACKING,
    Encoder::$STATUS_FIXING,
);

$object->queue = Encoder::getQueue($status, $object->login->streamers_id);
$object->error = false;

if(!empty($_REQUEST['videos_id'])){
    $object->videos_id = intval($_REQUEST['videos_id']);
    foreach ($object->queue as $key => $value) {
        if($value['return_vars']->videos_id !== $object->videos_id){
            unset($object->queue[$key]);
        }
    }
}

foreach ($object->queue as $key => $value) {
    $object->queue[$key]['conversion'] = Encoder::getVideoConversionStatus($value['id']);
    $object->queue[$key]['download'] = Encoder::getYoutubeDlProgress($value['id']);

    if(empty($object->queue[$key]['conversion'])){
        $object->queue[$key]['conversion'] = null;
    }

    if(empty($object->queue[$key]['download'])){
        $object->queue[$key]['download'] = null;
    }

    $object->queue[$key] = API::cleanQueueArray($object->queue[$key]);
}

die(json_encode($object));