<?php
header('Content-Type: application/json');
require_once './Login.php';
require_once './Streamer.php';
global $global;
$object = new stdClass();
if (empty($_POST['user']) || empty($_POST['pass'])) {
    error_log('login.json: blank credentials user/pass');
    $object->error = 'User and Password can not be blank';
    die(json_encode($object));
}
if (empty(getExternalHttpUrlForShell($_POST['siteURL'], 'objects/login.json.php siteURL'))) {
    error_log('login.json: blocked by URL safety validation siteURL=(' . $_POST['siteURL'] . ') allowPrivateNetworkURLs=' . (!empty($global['allowPrivateNetworkURLs']) ? 'true' : 'false'));
    $object->error = 'Invalid streamer site URL';
    die(json_encode($object));
}
if (!Streamer::isURLAllowed($_POST['siteURL'])) {
    error_log('login.json: blocked by Streamer::isURLAllowed siteURL=(' . $_POST['siteURL'] . ')');
    $object->error = 'This streamer site is not allowed';
    die(json_encode($object));
}
error_log('login.json: Login::run');
Login::run($_POST['user'], $_POST['pass'], $_POST['siteURL'], $_POST['encodedPass']);
if (!empty($_SESSION['login'])) {
    error_log('login.json: session login created isLogged=' . (!empty($_SESSION['login']->isLogged) ? 'true' : 'false') . ' canUpload=' . (!empty($_SESSION['login']->canUpload) ? 'true' : 'false') . ' streamer=' . (!empty($_SESSION['login']->streamer) ? 'true' : 'false') . ' error=' . (!empty($_SESSION['login']->error) ? $_SESSION['login']->error : 'none'));
    $json = json_encode($_SESSION['login']);
} else {
    error_log('login.json: session login object missing after Login::run');
    $object->error = 'Your site is banned';
    die(json_encode($object));
}

header('Content-length: ' .  strlen($json));
echo $json;
