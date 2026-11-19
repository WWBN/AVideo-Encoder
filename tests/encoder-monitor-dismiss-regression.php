<?php
// Real monitor persistence and report behavior with an in-memory database boundary.
if (PHP_SAPI !== 'cli') { exit(1); }
class ObjectYPT {}
class Login {}
class Streamer { public static function getTableName() { return 'streamers'; } }
class Format {}
$global = ['systemRootPath' => dirname(__DIR__) . '/', 'tablesPrefix' => '',
    'docker_vars' => __DIR__ . '/nonexistent-docker-vars'];
require_once $global['systemRootPath'] . 'objects/EncoderMonitor.php';

class DismissResult
{
    private $rows;
    public function __construct($rows) { $this->rows = $rows; }
    public function fetch_assoc() { return array_shift($this->rows); }
}
class DismissDatabase
{
    public $states = [];
    public $beforeDismissWrite;
    public function prepare($sql) { return new DismissStatement($this, $sql); }
    public function query($sql)
    {
        if (strpos($sql, 'SELECT name, value,') === 0) {
            $rows = [];
            foreach ($this->states as $name => $value) {
                if (in_array($name, ['heartbeat', 'last_error'], true) || strpos($name, 'streamer_issue_') === 0) {
                    $rows[] = ['name' => $name, 'value' => $value, 'age' => 10];
                }
            }
            return new DismissResult($rows);
        }
        if (strpos($sql, 'SELECT id FROM streamers') === 0) {
            return new DismissResult([['id' => 1], ['id' => 2]]);
        }
        if (strpos($sql, 'SELECT q.id,') === 0) { return new DismissResult([]); }
        throw new RuntimeException('Unexpected query: ' . $sql);
    }
}
class DismissStatement
{
    private $db, $sql, $params, $result;
    public function __construct($db, $sql) { $this->db = $db; $this->sql = $sql; }
    public function bind_param($types, &...$params) { $this->params = $params; }
    public function bind_result(&$result) { $this->result =& $result; }
    public function execute()
    {
        if (strpos($this->sql, 'INSERT INTO encoder_monitor_state') === 0) {
            if (strpos($this->params[0], 'streamer_issue_dismissed_') === 0 && $this->db->beforeDismissWrite) {
                $callback = $this->db->beforeDismissWrite;
                $this->db->beforeDismissWrite = null;
                $callback();
            }
            $this->db->states[$this->params[0]] = $this->params[1];
        } elseif (strpos($this->sql, 'DELETE FROM encoder_monitor_state') === 0) {
            unset($this->db->states[$this->params[0]]);
        } elseif (strpos($this->sql, 'SELECT value FROM encoder_monitor_state') !== 0
            && strpos($this->sql, 'SELECT COUNT(*) FROM information_schema.TABLES') !== 0) {
            throw new RuntimeException('Unexpected statement');
        }
        return true;
    }
    public function fetch()
    {
        if (strpos($this->sql, 'SELECT value FROM encoder_monitor_state') === 0) {
            $this->result = $this->db->states[$this->params[0]] ?? null;
            return $this->result !== null;
        }
        $this->result = 2;
        return true;
    }
    public function close() {}
}
$checks = 0;
function checkDismiss($condition, $message)
{
    global $checks;
    if (!$condition) { throw new RuntimeException($message); }
    $checks++;
}
$db = new DismissDatabase();
$global['mysqli'] = $db;
$legacy = '{"issue":"unreachable"}';
$db->states = ['streamer_issue_1' => $legacy, 'streamer_issue_2' => $legacy,
    'streamer_unreachable_1' => '2026-11-19 14:27:00', 'unrelated' => 'preserved'];
$report = EncoderMonitor::getStatusReport();
checkDismiss(count($report['streamerIssues']) === 2, 'legacy failures remain visible before dismissal');
$version = $report['streamerIssues'][0]['version'];
checkDismiss($version !== $report['streamerIssues'][1]['version'], 'versions are scoped to each account');
checkDismiss(!EncoderMonitor::dismissStreamerIssue(2, $version), 'one account version cannot dismiss another');
checkDismiss(!EncoderMonitor::dismissStreamerIssue(0, $version), 'invalid account refused');
checkDismiss(!EncoderMonitor::dismissStreamerIssue(1, []), 'array version refused');
checkDismiss(!EncoderMonitor::dismissStreamerIssue(1, 'invalid'), 'malformed version refused');
checkDismiss(!EncoderMonitor::dismissStreamerIssue(99, str_repeat('a', 64)), 'missing notice refused');
checkDismiss(EncoderMonitor::dismissStreamerIssue(1, $version), 'current notice can be dismissed');
checkDismiss($db->states['streamer_issue_1'] === $legacy, 'failure diagnostics are preserved');
checkDismiss($db->states['streamer_unreachable_1'] === '2026-11-19 14:27:00', 'dismissal does not alter the retry pause');
checkDismiss($db->states['unrelated'] === 'preserved', 'unrelated state is preserved');
$report = EncoderMonitor::getStatusReport();
checkDismiss(count($report['streamerIssues']) === 1 && $report['streamerIssues'][0]['streamers_id'] === 2,
    'only the chosen account is removed from active notices');
checkDismiss(count($report['dismissedStreamerIssues']) === 1 && $report['dismissedStreamerIssues'][0]['streamers_id'] === 1,
    'dismissed occurrence remains available in history');
checkDismiss(EncoderMonitor::dismissStreamerIssue(1, $version), 'repeated dismissal is idempotent');

$record = new ReflectionMethod('EncoderMonitor', 'setStreamerIssue');
$record->setAccessible(true);
$record->invoke(null, 1, 'unreachable');
$report = EncoderMonitor::getStatusReport();
$current = array_values(array_filter($report['streamerIssues'], function ($issue) { return $issue['streamers_id'] === 1; }))[0];
checkDismiss($current['version'] !== $version, 'same failure code creates a new occurrence even in the same second');
checkDismiss(!EncoderMonitor::dismissStreamerIssue(1, $version), 'a stale page cannot dismiss a new failure');
$db->beforeDismissWrite = function () use ($record) { $record->invoke(null, 1, 'unreachable'); };
checkDismiss(EncoderMonitor::dismissStreamerIssue(1, $current['version']), 'an acknowledged occurrence can race with a new failure');
$report = EncoderMonitor::getStatusReport();
checkDismiss(count($report['streamerIssues']) === 2, 'a failure arriving during acknowledgement remains visible');
$record->invoke(null, 1, '');
$report = EncoderMonitor::getStatusReport();
checkDismiss(count($report['streamerIssues']) === 1 && empty($report['dismissedStreamerIssues']),
    'successful delivery still clears the original notice normally');
echo "PASS: {$checks} monitor dismissal checks\n";
