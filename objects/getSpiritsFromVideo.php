<?php

require_once dirname(__FILE__) . '/../videos/configuration.php';
require_once '../objects/Encoder.php';
header('Access-Control-Allow-Origin: *');

if (empty($_GET['totalClips'])) {
    $_GET['totalClips'] = 100;
}

$url = decodeBase64UrlParameter(isset($_GET['base64Url']) ? $_GET['base64Url'] : '');
$url = $url === false ? false : getExternalHttpUrlForShell($url, 'getSpiritsFromVideo');
if ($url === false) {
    die();
}

$parts = explode("?token", $url);
$baseName = md5($parts[0]);
$imageFileName = $global['systemRootPath'] . "videos/sprit_{$baseName}.jpg";
//$url = "http://127.0.0.1/AVideo/videos/_YPTuniqid_5a01ef79b04ec6.24051213_HD.mp4";

$tileWidth = intval($_GET['tileWidth']);
$numberOfTiles = intval($_GET['totalClips']);

$tileHeight = intval($tileWidth / 16 * 9);

if (!empty($_GET['duration'])) {
    $duration = $_GET['duration'];
} else {
    $duration = Encoder::getDurationFromFile($url);
}

$videoLength = parseDurationToSeconds($duration);

$step = $videoLength / $numberOfTiles;
error_log("getSpritsFromVideo: step=$step videoLength=$videoLength numberOfTiles=$numberOfTiles");
//var_dump($_REQUEST);exit;
header("Content-type: image/jpeg");
$failedFileName = "{$imageFileName}.failed";
if (!file_exists($imageFileName) && empty($_REQUEST['sync']) && file_exists($failedFileName) && time() - filemtime($failedFileName) < 3600) {
    // the last try could not read the video: answer nothing (not the placeholder) so the Streamer stops waiting
    error_log("getSpritsFromVideo: last try failed, wait before trying again {$url}");
} else if (!file_exists($imageFileName)) {
    // display a dummy image
    if(empty($_REQUEST['sync'])){
        echo url_get_contents($global['systemRootPath'] . "view/img/creatingImages.jpg");
    }
    //call createsprits
    $urlEscaped = escapeshellarg($url);
    $stepEscaped = escapeshellarg($step);
    $tileWidthEscaped = escapeshellarg($tileWidth);
    $tileHeightEscaped = escapeshellarg($tileHeight);
    $imageFileNameEscaped = escapeshellarg($imageFileName);
    $numberOfTilesEscaped = escapeshellarg($numberOfTiles);
    $baseNameEscaped = escapeshellarg($baseName);
    $command = (getPHP()." \"{$global['systemRootPath']}objects/createSpiritsFromVideo.php\" {$urlEscaped} {$stepEscaped} {$tileWidthEscaped} {$tileHeightEscaped} {$imageFileNameEscaped} {$numberOfTilesEscaped} {$baseNameEscaped}");
    error_log("getSpritsFromVideo: {$command}");
    if(!empty($_REQUEST['sync'])){
        exec($command." 1");
        echo url_get_contents($imageFileName);
        //var_dump($imageFileName);
        @unlink($imageFileName);
    }else{
        execAsync($command);
    }
} else {
    echo url_get_contents($imageFileName);
    unlink($imageFileName);
}
// delete old sprits files
$files = array_merge(glob($global['systemRootPath'] . "videos/sprit_*.jpg"), glob($global['systemRootPath'] . "videos/sprit_*.failed"));
$now = time();
foreach ($files as $file) {
    if (is_file($file)) {
        if ($now - filemtime($file) >= 86400) { // 1 day
            unlink($file);
        }
    }
}
// frames of runs that never finished (older versions also kept the finished ones)
foreach (glob($global['systemRootPath'] . "videos/thumbs_*", GLOB_ONLYDIR) as $dir) {
    if ($now - filemtime($dir) >= 86400) {
        rrmdir($dir);
    }
}
