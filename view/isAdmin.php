<?php
require_once dirname(__FILE__) . '/../videos/configuration.php';
require_once '../objects/Streamer.php';
require_once '../objects/Login.php';
header('Content-Type: application/json');
if(!Login::isAdmin() || empty($_POST['id']) || !isset($_POST['isAdmin'])){
    return false;
}
if (!isEncoderAdminCSRFTokenValid()) {
    error_log('isAdmin: invalid or missing csrfToken');
    die(json_encode(false));
}
$s = new Streamer($_POST['id']);
$s->setIsAdmin($_POST['isAdmin']);
echo json_encode($s->save());