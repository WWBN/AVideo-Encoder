<?php
require_once dirname(__FILE__) . '/../videos/configuration.php';
require_once '../objects/Streamer.php';
require_once '../objects/Login.php';
header('Content-Type: application/json');
if(!Login::isAdmin() || empty($_POST['id']) || !isset($_POST['isAdmin'])){
    return false;
}
$s = new Streamer($_POST['id']);
$s->setIsAdmin($_POST['isAdmin']);
echo json_encode($s->save());