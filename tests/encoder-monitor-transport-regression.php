<?php
// Real cURL requests to isolated loopback servers; no installation DB or Streamer required.
if (PHP_SAPI !== 'cli') { exit(1); }
class ObjectYPT
{
    public function __construct($id)
    {
        if ($id !== '') {
            $this->id = intval($id);
            $this->status = 'error';
            $this->streamers_id = 3;
            $this->formats_id = 1;
            $this->return_vars = '{"videos_id":171}';
        }
    }
}
class Login {}
class Format {}
class Streamer
{
    public static $urls = [];
    private $id;
    public function __construct($id) { $this->id = $id; }
    public function getSiteURL() { return self::$urls[$this->id]; }
    public function getUser() { return 'fixture'; }
    public function getPass() { return 'fixture'; }
    public static function getTableName() { return 'streamers'; }
}
$global = ['systemRootPath' => rtrim($argv[1] ?? dirname(__DIR__), '/\\') . '/',
    'docker_vars' => __DIR__ . '/nonexistent-docker-vars', 'tablesPrefix' => '',
    'webSiteRootURL' => 'http://encoder.example.test/'];
require_once $global['systemRootPath'] . 'objects/EncoderMonitor.php';

class MonitorStateFixture
{
    public $states = [];
    public $admins = [1, 2];
    public function prepare($sql) { return new MonitorStatementFixture($this, $sql); }
    public function query($sql)
    {
        if (strpos($sql, 'SELECT id FROM streamers') !== 0) { throw new RuntimeException('Unexpected SQL'); }
        return new MonitorResultFixture($this->admins);
    }
}
class MonitorResultFixture
{
    private $ids;
    public function __construct($ids) { $this->ids = $ids; }
    public function fetch_assoc()
    {
        return empty($this->ids) ? null : ['id' => array_shift($this->ids)];
    }
}
class MonitorStatementFixture
{
    private $db;
    private $sql;
    private $params;
    private $result;
    public function __construct($db, $sql) { $this->db = $db; $this->sql = $sql; }
    public function bind_param($types, &...$params) { $this->params = $params; }
    public function bind_result(&$value) { $this->result =& $value; }
    public function execute()
    {
        if (strpos($this->sql, 'INSERT INTO encoder_monitor_state') === 0) {
            $this->db->states[$this->params[0]] = $this->params[1];
        } elseif (strpos($this->sql, 'SELECT value FROM encoder_monitor_state') !== 0) {
            throw new RuntimeException('Unexpected statement');
        }
        return true;
    }
    public function fetch()
    {
        $this->result = $this->db->states[$this->params[0]] ?? null;
        return $this->result !== null;
    }
    public function close() {}
}
$checks = 0;
function checkTransport($condition, $message)
{
    global $checks;
    if (!$condition) { throw new RuntimeException($message); }
    ++$checks;
}
function startMonitorServer($delay)
{
    // Port 0 lets the OS choose a free port. Writing it to stdout is the readiness handshake.
    $code = '$server = stream_socket_server("tcp://127.0.0.1:0", $errno, $error);'
        . 'if (!$server) { exit(1); }'
        . 'echo stream_socket_get_name($server, false), PHP_EOL; flush();'
        . '$client = stream_socket_accept($server, 10); if (!$client) { exit(2); }'
        . '$length = 0; while (($line = fgets($client)) !== false && trim($line) !== "") {'
        . 'if (preg_match("/^Content-Length: (\\d+)/i", $line, $m)) { $length = (int) $m[1]; }}'
        . 'while ($length > 0) { $data = fread($client, $length); if ($data === false || $data === "") { break; } $length -= strlen($data); }'
        . 'usleep(' . intval($delay * 1000000) . ');'
        . '$body = \'{"error":false}\';'
        . '@fwrite($client, "HTTP/1.1 200 OK\r\nContent-Type: application/json\r\nConnection: close\r\nContent-Length: " . strlen($body) . "\r\n\r\n" . $body);'
        . 'fclose($client); fclose($server);';
    $process = proc_open([PHP_BINARY, '-r', $code], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($process)) { throw new RuntimeException('Could not start loopback fixture'); }
    fclose($pipes[0]);
    $address = trim((string) fgets($pipes[1]));
    if (!preg_match('/^127\.0\.0\.1:\d+$/', $address)) {
        proc_terminate($process);
        throw new RuntimeException('Loopback fixture did not become ready');
    }
    return [$process, $pipes, 'http://' . $address . '/'];
}
$servers = [];
try {
    $servers[] = startMonitorServer(1.5);
    $servers[] = startMonitorServer(0);
    Streamer::$urls = [1 => $servers[0][2], 2 => $servers[1][2]];
    $encoder = new Encoder('');
    $encoder->setStreamers_id(1);
    $encoder->setReturn_vars('{}');

    $expired = Encoder::sendToStreamerWithDeadline(EncoderMonitor::STREAMER_ENDPOINT, ['type' => 'system'], new stdClass(), $encoder, microtime(true) - 1);
    checkTransport($expired->error && $expired->curl_errno === 28 && $expired->http_code === 0, 'expired deadline does not perform HTTP');

    $db = new MonitorStateFixture();
    $global['mysqli'] = $db;
    $cfg = EncoderMonitor::defaults();
    $cfg['diskMinFreeGB'] = 0;
    $cfg['diskMinFreePercent'] = 0;
    $stats = ['waiting' => 1, 'processing' => 0, 'oldestWaitingMinutes' => 60, 'errorsLastHour' => 0, 'lastError' => ''];
    $paused = [];
    $issues = [];
    $runChecks = new ReflectionMethod('EncoderMonitor', 'runSystemChecks');
    $runChecks->setAccessible(true);
    $now = date('Y-m-d H:i:s');
    $started = microtime(true);
    $deadline = $started + 0.25;
    $args = [$stats, $now, $cfg, &$paused, &$issues, $deadline];
    $sent = $runChecks->invokeArgs(null, $args);
    $elapsed = microtime(true) - $started;
    checkTransport($elapsed < 1.2, 'slow HTTP call is capped by the remaining run budget');
    checkTransport($sent === [] && ($issues[1] ?? '') === 'unreachable', 'timed-out admin alert is recorded');
    checkTransport(!isset($issues[2]), 'no second admin request starts after the deadline');
    checkTransport(isset($db->states['system_alert_queue_stalled_1']) && !isset($db->states['system_alert_queue_stalled_2']), 'only attempted sites are throttled');

    // Fresh run: the first site is on cooldown, but the deferred second site is still due.
    $paused = [];
    $issues = [];
    $args = [$stats, $now, $cfg, &$paused, &$issues, microtime(true) + 2];
    $sent = $runChecks->invokeArgs(null, $args);
    checkTransport($sent === ['queue_stalled'] && isset($issues[2]) && $issues[2] === '', 'next run delivers to the deferred admin');
    checkTransport(!isset($issues[1]), 'already attempted admin is not contacted again during cooldown');

    $servers[] = startMonitorServer(1.5);
    Streamer::$urls[3] = $servers[2][2];
    $ownerJob = ['id' => 71, 'status' => 'error', 'status_obs' => 'conversion failed', 'return_vars' => '{"videos_id":171}'];
    $ownerRow = ['encoder_queue_id' => 71, 'state' => 'error', 'alerts_sent' => 0];
    $response = null;
    $sendOwner = new ReflectionMethod('EncoderMonitor', 'sendOwnerAlert');
    $sendOwner->setAccessible(true);
    $started = microtime(true);
    $args = [$ownerJob, $ownerRow, ['alert' => 'error', 'minutes' => 1], $now, $cfg, 0, &$response, $started + 0.25];
    $ownerResult = $sendOwner->invokeArgs(null, $args);
    checkTransport(microtime(true) - $started < 1.2 && $response->curl_errno === 28, 'owner alert also uses the remaining run budget');
    checkTransport($ownerResult['alerts_sent'] === 0 && strpos($ownerResult['last_result'], 'failed:') === 0, 'timed-out owner alert remains eligible for retry');

    $servers[] = startMonitorServer(0.4);
    Streamer::$urls[4] = $servers[3][2];
    $encoder->setStreamers_id(4);
    $started = microtime(true);
    $response = Encoder::sendToStreamer(EncoderMonitor::STREAMER_ENDPOINT, ['type' => 'system'], new stdClass(), $encoder);
    checkTransport(!$response->error && $response->http_code === 200 && microtime(true) - $started >= 0.35, 'existing callers keep their normal transport timeout');
} finally {
    foreach ($servers as [$process, $pipes]) {
        proc_terminate($process);
        foreach ($pipes as $pipe) { if (is_resource($pipe)) { fclose($pipe); } }
        proc_close($process);
    }
}
echo "PASS: {$checks} encoder monitor transport checks\n";
