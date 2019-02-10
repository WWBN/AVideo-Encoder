<?php

require_once dirname(__FILE__) . '/../videos/configuration.php';
require_once '../objects/Encoder.php';
header('Access-Control-Allow-Origin: *');

if (empty($_GET['totalClips'])) {
    $_GET['totalClips'] = 100;
}

$url = base64_decode($_GET['base64Url']);
$parts = explode("?token", $url);
$baseName = md5($parts[0]) ;
$imageFileName = $global['systemRootPath'] . "videos/sprit_{$baseName}.jpg";
//$url = "http://127.0.0.1/YouPHPTube/videos/_YPTuniqid_5a01ef79b04ec6.24051213_HD.mp4";

$tileWidth = $_GET['tileWidth'];
$numberOfTiles = $_GET['totalClips'];

$tileHeight = intval($tileWidth / 16 * 9);

if (!empty($_GET['duration'])) {
    $duration = $_GET['duration'];
} else {
    $duration = Encoder::getDurationFromFile($url);
}

$videoLength = parseDurationToSeconds($duration);

$step = $videoLength / $numberOfTiles;

header("Content-type: image/jpeg");
if (!file_exists($imageFileName)) {
    // display a dummy image
    echo file_get_contents($global['systemRootPath'] . "view/img/creatingImages.jpg");
    //call createsprits
    $command = ("php '{$global['systemRootPath']}objects/createSpiritsFromVideo.php' '$url' '$step' '$tileWidth' '$tileHeight' '$imageFileName' '$numberOfTiles' '$baseName'");
    error_log("getSpritsFromVideo: {$command}");
    exec($command . " > /dev/null 2>/dev/null &");
} else {
    echo file_get_contents($imageFileName);
    unlink($imageFileName);
}