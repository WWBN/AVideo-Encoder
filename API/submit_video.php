<?php

require_once __DIR__ . '/../videos/configuration.php';
header('Content-Type: application/json');

require_once __DIR__ . '/API.php';

if(empty($_REQUEST['videoURL'])){
    $object->msg = 'videoURL is required';
    die(json_encode($object));
}

$object = API::checkCredentials();
if (!Login::canUpload()) {
    $object->msg = "This user can not upload files";
    die(json_encode($object));
}

$object->videoURL = $_REQUEST['videoURL'];
$object->videoTitle = @$_REQUEST['videoTitle'];

$object->addVideo = addVideo($object->videoURL, $object->login->streamers_id, $object->videoTitle);

$object->error = !empty($object->addVideo->error);

die(json_encode($object));