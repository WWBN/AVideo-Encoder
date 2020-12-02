<?php

require_once dirname(__FILE__) . '/../videos/configuration.php';
require_once $global['systemRootPath'] . 'objects/Encoder.php';
require_once $global['systemRootPath'] . 'objects/Login.php';
require_once $global['systemRootPath'] . 'objects/Streamer.php';
header("Content-Type: text/plain");
header("Access-Control-Allow-Origin: *");
$outputPath = "{$global['systemRootPath']}{$_REQUEST['path']}";
$jsonFile = "{$outputPath}.obj.log";
$encFile = "{$outputPath}enc_watermarked.key";
echo file_get_contents($encFile);exit;
if(empty($_REQUEST['protectionToken'])){
    die("protectionToken not found");
}
$json = json_decode(file_get_contents($jsonFile));
if (is_object($json)) {
    if ((!empty($_REQUEST['protectionToken']) && $_REQUEST['protectionToken'] === $json->protectionToken) || !empty($_REQUEST['isMobile'])) {
        echo file_get_contents($encFile);
        exit;
    } else {
        error_log("watermarked.key.php {$_REQUEST['protectionToken']} !== {$json->protectionToken}");
        die("protectionToken does not match");
    }
} else {
    error_log("watermarked.key.php JSON not found {$jsonFile}");
    die("json error");
}

if(!empty($_REQUEST['protectionToken'])){
    error_log("watermarked.key.php {$encFile} ({$_REQUEST['protectionToken']})");
}
if(!empty($_REQUEST['isMobile'])){
    error_log("watermarked.key.php {$encFile} IsMobile");
}
die("nothing to say");