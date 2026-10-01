<?php
// Revalidate the actual process and refreshed worker row before dead-worker recovery.
if (PHP_SAPI !== 'cli') { exit(1); }
class ObjectYPT
{
    public static $row;
    public function __construct($id)
    {
        foreach (self::$row as $key => $value) { $this->$key = $value; }
    }
}
class Login {}
class Streamer {}
class Format {}
$global = ['systemRootPath' => rtrim($argv[1] ?? dirname(__DIR__), '/\\') . '/',
    'docker_vars' => __DIR__ . '/nonexistent-docker-vars', 'tablesPrefix' => '',
    'webSiteRootURL' => 'http://encoder.example.test/'];
require_once $global['systemRootPath'] . 'objects/EncoderMonitor.php';
$job = ['id' => 71, 'status' => 'encoding', 'worker_ppid' => getmypid(), 'worker_pid' => 0];
ObjectYPT::$row = $job;
$recover = new ReflectionMethod('EncoderMonitor', 'recoverDeadWorker');
$recover->setAccessible(true);
if ($recover->invoke(null, $job) !== 'changed') {
    throw new RuntimeException('A currently live or unknown worker must not be recovered from a stale decision');
}
ObjectYPT::$row['worker_pid'] = getmypid();
if ($recover->invoke(null, $job) !== 'changed') {
    throw new RuntimeException('A replaced child worker must not be recovered from a stale snapshot');
}
ObjectYPT::$row = $job;
ObjectYPT::$row['status'] = 'done';
if ($recover->invoke(null, $job) !== 'changed') {
    throw new RuntimeException('A job that completed while monitoring must not be recovered');
}
$send = new ReflectionMethod('EncoderMonitor', 'sendOwnerAlert');
$send->setAccessible(true);
foreach (['queue', 'encoding', 'done', null] as $currentStatus) {
    // The queue can resume or delete the job after the monitor builds its pending alerts.
    ObjectYPT::$row = array_merge($job, ['status' => $currentStatus]);
    $pending = array_merge($job, ['status' => 'error', 'status_obs' => 'conversion failed', 'return_vars' => '{"videos_id":171}']);
    $row = ['encoder_queue_id' => 71, 'state' => 'error', 'alerts_sent' => 0];
    $response = null;
    $args = [$pending, $row, ['alert' => 'error', 'minutes' => 1], date('Y-m-d H:i:s'), EncoderMonitor::defaults(), 0, &$response, microtime(true) - 1];
    $result = $send->invokeArgs(null, $args);
    if ($response !== null || $result['alerts_sent'] !== 0 || strpos($result['last_result'], 'deferred:') !== 0) {
        throw new RuntimeException('A stale error alert must not be sent or consume the next alert');
    }
}
// A job deleted by autodelete during the run makes the foreign key refuse its monitor row.
class DeletedJobDb
{
    public $jobExists = false;
    public $errno = 1452;
    public $error = 'Cannot add or update a child row: a foreign key constraint fails';
    public function prepare($sql) { return new DeletedJobStatement(); }
    public function query($sql) { return new DeletedJobResult($this->jobExists ? [['id' => 71]] : []); }
}
class DeletedJobStatement
{
    public $errno = 1452;
    public $error = 'Cannot add or update a child row: a foreign key constraint fails';
    public function bind_param($types, ...$params) {}
    public function execute() { return false; }
    public function close() {}
}
class DeletedJobResult
{
    private $rows;
    public function __construct($rows) { $this->rows = $rows; }
    public function fetch_assoc() { return array_shift($this->rows); }
}
$global['mysqli'] = new DeletedJobDb();
$save = new ReflectionMethod('EncoderMonitor', 'saveMonitorRow');
$save->setAccessible(true);
$monitorRow = ['encoder_queue_id' => 71, 'state' => 'error', 'state_since' => '2026-10-01 10:00:00', 'alerts_sent' => 0,
    'last_alert_type' => null, 'last_alert_at' => null, 'last_attempt_at' => null, 'last_result' => null, 'dead_since' => null];
if ($save->invoke(null, $monitorRow, null) !== false) {
    throw new RuntimeException('A deleted job must not turn into a monitor failure');
}
$global['mysqli']->jobExists = true;
$reported = false;
try {
    $save->invoke(null, $monitorRow, null);
} catch (RuntimeException $expected) {
    $reported = true;
}
if (!$reported) {
    throw new RuntimeException('A real write failure must still be reported');
}
echo "PASS: 9 encoder monitor worker checks\n";
