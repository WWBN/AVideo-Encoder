<?php
require_once __DIR__ . '/../videos/configuration.php';
header('Content-Type: application/json');

require_once __DIR__ . '/../objects/Login.php';
require_once __DIR__ . '/../objects/Encoder.php';
require_once __DIR__ . '/API.php';

$object = API::checkCredentials();


$object->queue = Encoder::getQueue(array(), $object->login->streamers_id);

die(json_encode($object));