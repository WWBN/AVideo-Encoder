<?php
header('Content-Type: application/json');
require_once './Login.php';
$object = new stdClass();
if(empty($_POST['user']) || empty($_POST['pass'])){
    $object->error = "User and Password can not be blank";
     die(json_encode($object));
}
Login::run($_POST['user'], $_POST['pass'], $_POST['siteURL']);
$object->isLogged = Login::isLogged();
$object->isAdmin = Login::isAdmin();
$object->canUpload = Login::canUpload();
$object->canComment = Login::canComment();
echo json_encode($object);