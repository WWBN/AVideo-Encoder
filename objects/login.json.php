<?php
header('Content-Type: application/json');
require_once './Login.php';
require_once './Streamer.php';
$object = new stdClass();
if(empty($_POST['user']) || empty($_POST['pass'])){
    $object->error = "User and Password can not be blank";
     die(json_encode($object));
}
if(!Streamer::isURLAllowed($_POST['siteURL'])){
    $object->error = "This streamer site is not allowed";
    die(json_encode($object));
}

Login::run($_POST['user'], $_POST['pass'], $_POST['siteURL'], $_POST['encodedPass']);
if(!empty($_SESSION['login'])){
    $json = json_encode($_SESSION['login']);
}else{   
    $object->error = "Your site is banned";
    die(json_encode($object));
}
header("Content-length: ".  strlen($json));
echo $json;