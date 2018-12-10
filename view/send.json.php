<?php
header('Content-Type: application/json');
require_once dirname(__FILE__) . '/../videos/configuration.php';
require_once $global['systemRootPath'].'objects/Encoder.php';
require_once $global['systemRootPath'].'objects/Login.php';
$obj = new stdClass();
$obj->error = true;
if (!empty($_POST['id'])) {
    $obj->item = $_POST['id'];
    $e = new Encoder($_POST['id']);
    if (!empty($e->getId())) {
        if(!Login::isAdmin()){
            if(Login::getStreamerId() != $e->getStreamers_id()){
                $obj->msg = "You must be Admin to be able to send somebody else queue";
                echo json_encode($obj);
                exit;
            }
        }
        $obj->error = false;
        $obj->msg = $e->send();
    } else {
        $obj->msg = "Queue Item not found";
    }
} else {
    $obj->msg = "Id not found";
}
echo json_encode($obj->msg);