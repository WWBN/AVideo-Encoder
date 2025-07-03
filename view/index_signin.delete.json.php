<?php
$config = dirname(__FILE__) . '/../videos/configuration.php';
if (!file_exists($config)) {
    header("Location: install/index.php");
}
//header('Access-Control-Allow-Origin: *');
require_once $config;
require_once '../objects/Encoder.php';
require_once '../objects/Configuration.php';
require_once '../objects/Format.php';
require_once '../objects/Streamer.php';
require_once '../objects/Login.php';
require_once '../locale/function.php';
header('Content-Type: application/json');

if(!Login::isLogged()){
    die('Must login');
}

$s = new Streamer(Login::getStreamerId());
$saved = $s->updateJson($_REQUEST['provider'], null);
$json = $s->getJson();

$response = array(
    'error' => empty($saved),
    'saved' => $saved,
    'msg' => '',
    'json'=> json_decode($json)
);

echo json_encode($response);
?>
