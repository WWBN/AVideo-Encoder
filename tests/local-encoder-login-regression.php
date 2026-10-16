<?php
namespace LocalEncoderLoginRegression;

$source = file_get_contents(__DIR__ . '/../objects/LocalEncoderPromotion.php');
eval('namespace ' . __NAMESPACE__ . ';' . substr($source, 5));
class Login {
    public static $logged = true, $upload = true, $site = 'https://a.test/subsite/', $user = 'alice', $pass = 'test-token-a';
    public static function isLogged() { return self::$logged; }
    public static function canUpload() { return self::$upload; }
    public static function getStreamerURL() { return self::$site; }
    public static function getStreamerUser() { return self::$user; }
    public static function getStreamerPass() { return self::$pass; }
}
function getAdvancedCustomizedObjectData() { return (object) array('showLocalEncoder' => $GLOBALS['localEncoderLoginEnabled']); }
function getExternalHttpUrlForShell($url, $context) { return $url; }
function getSelfUserAgent() { return 'AVideoEncoder regression'; }
function curl_init($url) { $GLOBALS['localEncoderLoginHTTP'][] = array('url' => $url); return count($GLOBALS['localEncoderLoginHTTP']) - 1; }
function curl_setopt_array($handle, $options) { $GLOBALS['localEncoderLoginHTTP'][$handle]['options'] = $options; }
function curl_exec($handle) { return $GLOBALS['localEncoderLoginBody']; }
function curl_getinfo($handle, $kind) { return $GLOBALS['localEncoderLoginStatus']; }
function curl_close($handle) {}
function verify($condition, $message) { if (!$condition) { throw new \RuntimeException($message); } $GLOBALS['localEncoderLoginChecks']++; }
$GLOBALS['localEncoderLoginHTTP'] = array();
$GLOBALS['localEncoderLoginEnabled'] = true;
$GLOBALS['localEncoderLoginStatus'] = 200;
$GLOBALS['localEncoderLoginBody'] = json_encode(array('error' => false, 'code' => str_repeat('b', 64)));
$GLOBALS['localEncoderLoginChecks'] = 0;
$global = array('disableSSLVerification' => false);
$challenge = str_repeat('a', 43);
verify(EncoderLocalEncoderPromotion::getLaunchURL('https://encoder.test/nested/', Login::$site, getAdvancedCustomizedObjectData()) === 'https://encoder.test/nested/view/localEncoder.php', 'Launch page must retain the Encoder subdirectory');
foreach (array(array('https://a.test/subsite/', 'alice', 'test-token-a'), array('https://b.test/', 'bob', 'test-token-b')) as $account) {
    list(Login::$site, Login::$user, Login::$pass) = $account;
    verify(EncoderLocalEncoderPromotion::requestLoginCode($challenge)['error'] === false, 'Valid current account must authorize');
    $call = end($GLOBALS['localEncoderLoginHTTP']);
    verify($call['url'] === Login::$site . 'plugin/CustomizeAdvanced/localEncoderLogin.json.php', 'Never use another Streamer destination');
    parse_str($call['options'][CURLOPT_POSTFIELDS], $posted);
    verify($posted === array('action' => 'issue', 'challenge' => $challenge, 'user' => Login::$user, 'pass' => Login::$pass, 'encodedPass' => '1'), 'Preserve exactly the current Streamer credentials and POST contract');
    verify($call['options'][CURLOPT_POST] && !$call['options'][CURLOPT_FOLLOWLOCATION], 'Never put credentials in GET or redirect them');
    verify($call['options'][CURLOPT_SSL_VERIFYPEER] && $call['options'][CURLOPT_SSL_VERIFYHOST] === 2, 'Respect normal TLS verification');
}
$count = count($GLOBALS['localEncoderLoginHTTP']);
verify(EncoderLocalEncoderPromotion::requestLoginCode(array()) === false, 'Malformed challenge must fail');
Login::$upload = false;
verify(EncoderLocalEncoderPromotion::requestLoginCode($challenge) === false, 'No upload permission');
Login::$upload = true;
Login::$logged = false;
verify(EncoderLocalEncoderPromotion::requestLoginCode($challenge) === false, 'No authenticated account');
Login::$logged = true;
$GLOBALS['localEncoderLoginEnabled'] = false;
verify(EncoderLocalEncoderPromotion::requestLoginCode($challenge) === false, 'Admin disabled feature');
verify(count($GLOBALS['localEncoderLoginHTTP']) === $count, 'Rejected requests must not send credentials');
$GLOBALS['localEncoderLoginEnabled'] = true;
$GLOBALS['localEncoderLoginStatus'] = 302;
verify(EncoderLocalEncoderPromotion::requestLoginCode($challenge) === false, 'Never accept redirected authorization');
$GLOBALS['localEncoderLoginStatus'] = 200;
$GLOBALS['localEncoderLoginBody'] = json_encode(array('error' => true, 'msg' => 'private-error'));
verify(EncoderLocalEncoderPromotion::requestLoginCode($challenge) === false, 'Do not expose server error data');
echo 'Local Encoder login: ' . $GLOBALS['localEncoderLoginChecks'] . " checks passed\n";
