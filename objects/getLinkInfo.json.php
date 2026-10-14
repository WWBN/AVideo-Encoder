<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
require_once dirname(__FILE__) . '/../videos/configuration.php';
require_once '../objects/Encoder.php';
if (empty($_GET['base64Url'])){
    die(json_encode(['error' => true, 'msg' => 'Missing base64Url']));
}
$link = decodeBase64UrlParameter($_GET['base64Url']);
$link = $link === false ? false : getExternalHttpUrlForShell($link, 'getLinkInfo');
if ($link === false) {
    die(json_encode(['error' => true, 'msg' => 'Invalid URL']));
}
//echo base64_decode($_GET['base64Url']), "<br>";
//$r = Encoder::sendFile("{$global['systemRootPath']}videos/1_tmpFile.mp4", 1, "mp4");var_dump($r);return;
$base64Url = base64_encode($link);
$obj = new stdClass();
$obj->imageJPGLink = "{$global['webSiteRootURL']}getImage/". $base64Url."/jpg";
//$data = file_get_contents($obj->imageJPGLink);
//$obj->imageJPG = 'data:image/jpg;base64,' . base64_encode($data);


$obj->imageGIFLink = "{$global['webSiteRootURL']}getImage/". $base64Url."/gif";
//$data = file_get_contents($obj->imageGIFLink);
//$obj->imageGIF = 'data:image/gif;base64,' . base64_encode($data);

$obj->streamers_id =Login::getStreamerId();

$title = Encoder::getTitleFromLink($link, $obj->streamers_id);

$obj->msg = $title['output'];
$obj->title = $title['output'];
if ($title['error']){
    $obj->title = false;
}

$obj->duration = Encoder::getDurationFromLink($link, $obj->streamers_id);
$obj->description = Encoder::getDescriptionFromLink($link, $obj->streamers_id);

$obj->thumbs64 = base64_encode(Encoder::getThumbsFromLink($link, $obj->streamers_id));

$resp = json_encode($obj);
echo $resp;
