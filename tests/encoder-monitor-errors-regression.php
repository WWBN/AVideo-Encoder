<?php
// Exercise a complete monitor run with failed and successful persistence, without a live DB.
if (PHP_SAPI !== 'cli') { exit(1); }
class ObjectYPT {}
class Login {}
class Streamer {}
class Format {}
$global = ['systemRootPath' => rtrim($argv[1] ?? dirname(__DIR__), '/\\') . '/',
    'docker_vars' => __DIR__ . '/nonexistent-docker-vars', 'tablesPrefix' => '',
    'webSiteRootURL' => 'http://encoder.example.test/',
    'encoderMonitor' => ['kickQueue' => false, 'ownerAlerts' => false, 'adminAlerts' => false]];
require_once $global['systemRootPath'] . 'objects/EncoderMonitor.php';
class ErrorResultFixture
{
    private $rows;
    public function __construct($rows) { $this->rows = $rows; }
    public function fetch_assoc() { return array_shift($this->rows); }
}
class ErrorDatabaseFixture
{
    public $states = [];
    public $failWrites = true;
    public $closedFailedStatements = 0;
    public function prepare($sql) { return new ErrorStatementFixture($this, $sql); }
    public function query($sql)
    {
        if ($sql === 'SELECT NOW() AS now') {
            return new ErrorResultFixture([['now' => date('Y-m-d H:i:s')]]);
        }
        if (strpos($sql, 'DELETE m FROM') === 0) { return true; }
        if (strpos($sql, 'SELECT q.id,') === 0) {
            return new ErrorResultFixture([['id' => 77, 'status' => 'queue', 'status_obs' => '',
                'streamers_id' => 1, 'return_vars' => '{}', 'worker_ppid' => 0, 'worker_pid' => 0,
                'retry_count' => 0, 'modified' => date('Y-m-d H:i:s'), 'state' => null]]);
        }
        throw new RuntimeException('Unexpected query');
    }
}
class ErrorStatementFixture
{
    public $errno = 1114;
    public $error = 'Fixture table is full';
    private $db;
    private $sql;
    private $params;
    private $result;
    private $failed = false;
    public function __construct($db, $sql) { $this->db = $db; $this->sql = $sql; }
    public function bind_param($types, &...$params) { $this->params = $params; }
    public function bind_result(&$result) { $this->result =& $result; }
    public function execute()
    {
        if (strpos($this->sql, 'INSERT INTO encoder_queue_monitor') === 0) {
            $this->failed = $this->db->failWrites;
            return !$this->failed;
        }
        if (strpos($this->sql, 'INSERT INTO encoder_monitor_state') === 0) {
            $this->db->states[$this->params[0]] = $this->params[1];
        } elseif (strpos($this->sql, 'DELETE FROM encoder_monitor_state') === 0) {
            unset($this->db->states[$this->params[0]]);
        } elseif (strpos($this->sql, 'SELECT COUNT(*) FROM information_schema.TABLES') !== 0) {
            throw new RuntimeException('Unexpected statement');
        }
        return true;
    }
    public function fetch() { $this->result = 2; return true; }
    public function close()
    {
        if ($this->failed) { $this->db->closedFailedStatements++; }
    }
}
function checkErrors($condition, $message)
{
    if (!$condition) { throw new RuntimeException($message); }
}
$db = new ErrorDatabaseFixture();
$global['mysqli'] = $db;
$result = EncoderMonitor::run();
checkErrors($result['errors'] === 1 && !empty($result['error']), 'failed persistence must fail the run summary');
checkErrors($db->closedFailedStatements === 1, 'failed statement is still closed');
checkErrors(isset($db->states['last_error']), 'partial failure remains visible to the administrator');
$heartbeat = json_decode($db->states['heartbeat'], true);
checkErrors($heartbeat['summary']['errors'] === 1, 'heartbeat must report the partial failure');
$db->failWrites = false;
$result = EncoderMonitor::run();
checkErrors($result['errors'] === 0 && empty($result['error']), 'successful subsequent run recovers');
checkErrors(!isset($db->states['last_error']), 'only a successful run clears the error notice');
echo "PASS: 6 encoder monitor error checks\n";
