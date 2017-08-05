<?php

header('Content-Type: application/json');
require_once dirname(__FILE__) . '/../videos/configuration.php';
require_once '../objects/Encoder.php';
require_once '../objects/Login.php';

$obj = new stdClass();
$obj->error = true;
if (!empty($_POST['id'])) {
    $obj->item = $_POST['id'];
    $e = new Encoder($_POST['id']);
    if(!Login::isAdmin()){
        if(Login::getStreamerId() != $e->getStreamers_id()){
            $obj->msg = "You must be Admin to be able to delete somebody else queue";
            echo json_encode($obj);
            exit;
        }
    }
    if (!empty($e->getId())) {
        $obj->error = false;
        $obj->msg = json_encode($e->delete());
    } else {
        $obj->msg = "Queue Item not found";
    }
} else {
    $obj->msg = "Id not found";
}
echo json_encode($obj);
