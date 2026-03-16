<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
require_once dirname(__FILE__) . '/../videos/configuration.php';
require_once '../objects/Encoder.php';
if (empty($_GET['base64Url'])){
    die(json_encode(['error' => true, 'msg' => 'Missing base64Url']));
}
$link = base64_decode($_GET['base64Url']);

// SSRF protection: validate URL and block access to private/internal network addresses
if (!filter_var($link, FILTER_VALIDATE_URL) || !preg_match('/^https?:\/\//i', $link)) {
    error_log("getLinkInfo: Invalid URL rejected: {$link}");
    die(json_encode(['error' => true, 'msg' => 'Invalid URL']));
}
$_ssrfParsed = parse_url($link);
$_ssrfHost = isset($_ssrfParsed['host']) ? $_ssrfParsed['host'] : '';
if (empty($_ssrfHost) || preg_match('/^(localhost|.*\.local)$/i', $_ssrfHost)) {
    error_log("getLinkInfo: SSRF attempt blocked for host: {$_ssrfHost}");
    die(json_encode(['error' => true, 'msg' => 'Invalid URL']));
}
$_ssrfIP = filter_var($_ssrfHost, FILTER_VALIDATE_IP) ? $_ssrfHost : gethostbyname($_ssrfHost);
if (ip_is_private($_ssrfIP)) {
    error_log("getLinkInfo: SSRF attempt blocked - host '{$_ssrfHost}' resolves to private IP '{$_ssrfIP}'");
    die(json_encode(['error' => true, 'msg' => 'Invalid URL']));
}
unset($_ssrfParsed, $_ssrfHost, $_ssrfIP);
//echo base64_decode($_GET['base64Url']), "<br>";
//$r = Encoder::sendFile("{$global['systemRootPath']}videos/1_tmpFile.mp4", 1, "mp4");var_dump($r);return;
$obj = new stdClass();
$obj->imageJPGLink = "{$global['webSiteRootURL']}getImage/". $_GET['base64Url']."/jpg";
//$data = file_get_contents($obj->imageJPGLink);
//$obj->imageJPG = 'data:image/jpg;base64,' . base64_encode($data);


$obj->imageGIFLink = "{$global['webSiteRootURL']}getImage/". $_GET['base64Url']."/gif";
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
