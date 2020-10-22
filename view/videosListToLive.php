<?php

require_once dirname(__FILE__) . '/../videos/configuration.php';
require_once $global['systemRootPath'] . 'objects/Encoder.php';
require_once $global['systemRootPath'] . 'objects/Login.php';
require_once $global['systemRootPath'] . 'objects/Streamer.php';

// clear all get 
foreach ($_GET as $key => $value) {
    $_GET[$value] = str_replace('/[^a-z0-9.:/-]/i', '', trim($_GET[$value]));
}

$obj = new stdClass();
$obj->error = true;
$obj->msg = "";
$obj->playlists_id = intval($_GET['playlists_id']);
$obj->APISecret = $_GET['APISecret'];
$obj->liveKey = $_GET['liveKey'];
$obj->webSiteRootURL = $_GET['webSiteRootURL'];
$obj->user = $_GET['webSiteRootURL'];
$obj->pass = $_GET['webSiteRootURL'];
$obj->rtmp = $_GET['rtmp'];

if (empty($obj->playlists_id)) {
    $obj->msg = "playlists_id is empty";
    die(json_encode($obj));
}

if (empty($obj->APISecret)) {
    $obj->msg = "APISecret is empty";
    die(json_encode($obj));
}

if (empty($obj->liveKey)) {
    $obj->msg = "liveKey is empty";
    die(json_encode($obj));
}

if (!empty($_GET['webSiteRootURL']) && !empty($_GET['user']) && !empty($_GET['pass']) && empty($_GET['justLogin'])) {
    error_log("videosListToLive: Login::run");
    Login::run($_GET['user'], $_GET['pass'], $_GET['webSiteRootURL'], true);
}
if (!Login::isLogged()) {
    $obj->msg = "Could not login";
    die(json_encode($obj));
}
if (!Login::canStream()) {
    $obj->msg = "cannot stream";
    die(json_encode($obj));
}

$time = time();
$lockFile = $global['systemRootPath'] . "videos/videoListToLive_" . Login::getStreamerId() . "_";

array_map('unlink', glob($lockFile . "*"));

$lockFile .= $time . ".lock";

file_put_contents($lockFile, $time);

ini_set('max_execution_time', 0);

$index = 0;

echo "Start \n";

$count = 0;
while (1) {
    if (!empty($count) && $index == 0) {// stop do not loop
        echo "Stop: !empty($count) && $index == 0 \n";
        break;
    }
    $count++;
    if (!file_exists($lockFile)) {
        echo "Stop: !file_exists($lockFile) \n";
        break;
    }

    $obj->api = Login::getStreamerURL() . "plugin/API/get.json.php?APIName=video_from_program&playlists_id={$_GET['playlists_id']}&index={$index}&APISecret={$_GET['APISecret']}";

    $json = json_decode(url_get_contents($obj->api));
    if (!empty($json->error)) {
        $obj->msg = "API response: Error: {$json->message}";
        die(json_encode($obj));
    }
    if (empty($json->response->path)) {
        $obj->msg = "API response: Path not found";
        die(json_encode($obj));
    }
    $json->response->path = str_replace(array("'", '"', "<", ">"), array("", "", "", ""), $json->response->path);
    if (!filter_var($json->response->path, FILTER_VALIDATE_URL) || !preg_match("/^http.*/i", $json->response->path)) {
        $obj->msg = "Invalid Path: {$json->response->path}";
        die(json_encode($obj));
    }
    //var_dump($json);
    $index = intval($json->response->nextIndex);
    //echo "Index: $index \n";
    $cmd = get_ffmpeg()." -re -i \"{$json->response->path}\" -c copy -bsf:a aac_adtstoasc -f flv {$_GET['rtmp']}?p={$_GET['pass']}/{$_GET['liveKey']}";
    __exec($cmd);
    ob_flush();
}

echo "Finish \n";

function __exec($cmd) {
    echo $cmd . "\n";
    ob_flush();
    exec($cmd . " 2>&1", $output, $return_val);
    if ($return_val !== 0) {
        echo "\nErro\n";
        //var_dump($output);
        echo "\n";
        return false;
    }
    return true;
}
