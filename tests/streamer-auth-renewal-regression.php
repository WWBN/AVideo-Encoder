<?php
// php tests/streamer-auth-renewal-regression.php [encoder root]
// Exercise renewal with in-memory persistence and a controlled streamer response.
if (PHP_SAPI !== 'cli') { exit(1); }
class ObjectYPT
{
    public static $rows = [];
    public static $beforeLoad;
    public function __construct($id) { $this->load($id); }
    protected function load($id)
    {
        if (self::$beforeLoad) { call_user_func(self::$beforeLoad, $id); }
        if (!isset(self::$rows[$id])) { return false; }
        foreach (self::$rows[$id] as $key => $value) { $this->$key = $value; }
        return true;
    }
}
class Configuration
{
    public function getVersion() { return '8.2'; }
}
require_once rtrim($argv[1] ?? dirname(__DIR__), '/\\') . '/objects/Streamer.php';
class AuthFixture extends Streamer
{
    public static $reply;
    public static $requests = [];
    public static $writes = 0;
    protected function requestAuthentication($credential, $encoded)
    {
        self::$requests[] = [$this->getUser(), $this->getSiteURL(), $credential, $encoded];
        return self::$reply;
    }
    public function save()
    {
        self::$writes++;
        ObjectYPT::$rows[$this->id] = get_object_vars($this);
        return $this->id;
    }
}
function authExpect($condition, $message)
{
    if (!$condition) { throw new RuntimeException($message); }
    echo "PASS: {$message}\n";
}
ObjectYPT::$rows = [
    1 => ['id' => 1, 'user' => 'alice', 'siteURL' => 'https://one.example/', 'pass' => 'old-token'],
    2 => ['id' => 2, 'user' => 'bob', 'siteURL' => 'https://two.example/', 'pass' => 'other-token']
];
$_SESSION = ['login' => (object) ['isAdmin' => true, 'streamers_id' => 99]];
$sessionBefore = serialize($_SESSION);
try {
    AuthFixture::$reply = (object) ['isLogged' => false];
    $result = (new AuthFixture(1))->refreshAuthentication();
    authExpect(!empty($result['authentication_required']) && $result['account'] === 'alice', 'expired credential requests the correct account password');
    authExpect(AuthFixture::$requests[0] === ['alice', 'https://one.example/', 'old-token', true], 'preflight authenticates only to the saved streamer');
    authExpect(AuthFixture::$writes === 0, 'failed authentication does not change saved credentials');
    $result = (new AuthFixture(1))->refreshAuthentication('wrong-password');
    authExpect($result['error'] && !empty($result['authentication_required']), 'incorrect password keeps renewal available');
    authExpect(strpos(json_encode($result), 'wrong-password') === false, 'response never echoes the submitted password');

    foreach ([false, (object) ['unrelated' => true]] as $reply) {
        AuthFixture::$reply = $reply;
        $result = (new AuthFixture(1))->refreshAuthentication();
        authExpect($result['error'] && empty($result['authentication_required']), 'network or malformed response is not presented as an expired password');
    }
    AuthFixture::$reply = (object) ['isLogged' => true, 'canUpload' => false, 'user' => 'alice', 'pass' => 'new-token'];
    authExpect((new AuthFixture(1))->refreshAuthentication()['error'], 'authenticated account still needs upload permission');
    AuthFixture::$reply->canUpload = true;
    AuthFixture::$reply->user = 'bob';
    authExpect((new AuthFixture(1))->refreshAuthentication()['error'], 'another account cannot replace this account credentials');
    AuthFixture::$reply->user = 'alice';
    $result = (new AuthFixture(1))->refreshAuthentication('new-password');
    authExpect(!$result['error'] && ObjectYPT::$rows[1]['pass'] === 'new-token', 'successful login stores the site-issued credential');
    authExpect(end(AuthFixture::$requests) === ['alice', 'https://one.example/', 'new-password', false], 'submitted password uses normal unencoded login');
    authExpect(strpos(json_encode(ObjectYPT::$rows), 'new-password') === false, 'raw password is never persisted');
    authExpect(ObjectYPT::$rows[2]['pass'] === 'other-token', 'renewing one streamer leaves other streamers unchanged');
    authExpect(serialize($_SESSION) === $sessionBefore, 'renewal preserves the encoder admin session');
    authExpect(strpos(json_encode($result), 'new-token') === false, 'site-issued credential is not returned to the browser');

    $fixture = new AuthFixture(1);
    $writes = AuthFixture::$writes;
    ObjectYPT::$beforeLoad = function($id) { ObjectYPT::$rows[$id]['siteURL'] = 'https://changed.example/'; };
    authExpect($fixture->refreshAuthentication()['error'] && AuthFixture::$writes === $writes, 'concurrent streamer reassignment prevents credential overwrite');
} catch (Throwable $error) {
    fwrite(STDERR, 'FAIL: ' . $error->getMessage() . "\n");
    exit(1);
}
