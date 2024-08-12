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
$streamers_id = Login::getStreamerId();
$s = new Streamer(Login::getStreamerId());
$jsonString = $s->getJson();
if(empty($jsonString)){
    $json = array();
}else{
    $json = json_decode($jsonString, true);
}

//var_dump($json['youtube']['json']["restream.ypt.me"]);exit;
$data = array();
if(!empty($json['youtube'])){
    $data[] = array(
        'streamers_id'=>$streamers_id,
        'provider'=>'youtube',
        'profile'=>$json['youtube']['name'],
        'expires_at_human'=>$json['youtube']['json']["restream.ypt.me"]["expires"]["expires_at_human"],
    );
}


$response = array(
    'data' => $data,
    'draw' => intval(@$_REQUEST['draw']),
    'recordsTotal' => 1,
    'recordsFiltered' => 1,
);

echo json_encode($response);

?>