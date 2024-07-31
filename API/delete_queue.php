<?php
//OK
require_once __DIR__ . '/../videos/configuration.php';
header('Content-Type: application/json');

require_once __DIR__ . '/../objects/Login.php';
require_once __DIR__ . '/../objects/Encoder.php';
require_once __DIR__ . '/API.php';

$object = API::checkCredentials();


if(empty($_REQUEST['queue_id'])){
    $object->msg = 'queue_id is empty';
    die(json_encode($object));
}

if(!API::canChangeQueue($_REQUEST['queue_id'])){
    $object->msg = 'You cannot change the queue';
    die(json_encode($object));
}

$object->queue_id = intval($_REQUEST['queue_id']);
$encoder = new Encoder($object->queue_id);

$object->error = !$encoder->delete();

die(json_encode($object));