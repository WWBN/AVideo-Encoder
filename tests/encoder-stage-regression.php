<?php
// Standalone regression test: php tests/encoder-stage-regression.php
// Uses the real Encoder with an in-memory persistence boundary, no production DB,
// streamer requests or media processing. Run on Linux/PHP with pcntl enabled.
if (PHP_SAPI !== 'cli' || !function_exists('pcntl_fork')) {
    fwrite(STDERR, "This test requires CLI PHP with pcntl.\n");
    exit(1);
}

class ObjectYPT
{
    public static $rows = [];
    public static $reads = [];
    public static $writes = [];
    public static $beforeLoad;

    public function __construct($id)
    {
        $this->load($id);
    }

    protected function load($id)
    {
        if (self::$beforeLoad) {
            call_user_func(self::$beforeLoad, $id);
        }
        if (!isset(self::$rows[$id])) {
            return false;
        }
        self::$reads[] = self::$rows[$id];
        foreach (self::$rows[$id] as $key => $value) {
            $this->$key = $value;
        }
        return true;
    }

    public function save()
    {
        self::$rows[$this->id] = get_object_vars($this);
        self::$writes[] = self::$rows[$this->id];
        return $this->id;
    }
}

// Prevent these dependencies from loading installation settings. No video ID is
// present in test rows, so setStreamerLog returns before making an HTTP request.
class Login {}
class Streamer {}

$global = [
    'systemRootPath' => rtrim($argv[1] ?? dirname(__DIR__), '/\\') . '/',
    'docker_vars' => __DIR__ . '/nonexistent-docker-vars'
];
ini_set('error_log', '/dev/null');
ob_start();
require_once $global['systemRootPath'] . 'objects/Encoder.php';

function expectStage($condition, $message)
{
    if (!$condition) {
        ob_end_clean();
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

ObjectYPT::$rows[135] = [
    'id' => 135, 'streamers_id' => 1, 'title' => 'stage regression fixture',
    'status' => 'encoding', 'status_obs' => 'Preparing video encoding...',
    'return_vars' => '{}', 'worker_pid' => 0, 'worker_ppid' => getmypid()
];
$stale = new Encoder(135);
$preparation = new Encoder(135);
Encoder::initStages(8);
for ($stage = 1; $stage <= 8; $stage++) {
    Encoder::nextStage($preparation, 'Processing stage');
    $latestRead = end(ObjectYPT::$reads);
    expectStage($latestRead['status_obs'] === "Step {$stage}/8: Processing stage",
        'notification must reload the new stage, not the previous one');
}

ObjectYPT::$writes = [];
$output = [];
$exitCode = 0;
$stale->exec('exit 7', $output, $exitCode);
expectStage($exitCode === 7, 'child exit code must be preserved');
expectStage(count(ObjectYPT::$writes) === 2, 'worker PID must be saved at start and completion');
foreach (ObjectYPT::$writes as $write) {
    expectStage($write['status_obs'] === 'Step 8/8: Processing stage',
        'worker bookkeeping must never restore an old stage');
}
expectStage(ObjectYPT::$writes[0]['worker_pid'] > 0, 'running worker PID must be recorded');
expectStage(end(ObjectYPT::$writes)['worker_pid'] === 0, 'completed worker PID must be cleared');

// Simulate another writer changing the row while the child is running.
$loads = 0;
ObjectYPT::$beforeLoad = function($id) use (&$loads) {
    if (++$loads === 2) {
        ObjectYPT::$rows[$id]['status_obs'] = 'Updated while child was running';
    }
};
$stale->exec('exit 0', $output, $exitCode);
expectStage(ObjectYPT::$rows[135]['status_obs'] === 'Updated while child was running',
    'completion must preserve updates made while the child ran');

// A deleted queue entry must not be resurrected at completion.
$loads = 0;
ObjectYPT::$beforeLoad = function($id) use (&$loads) {
    if (++$loads === 2) {
        unset(ObjectYPT::$rows[$id]);
    }
};
$stale->exec('exit 0', $output, $exitCode);
expectStage(!isset(ObjectYPT::$rows[135]), 'completion must not recreate a deleted row');
ObjectYPT::$beforeLoad = null;
ObjectYPT::$writes = [];
$stale->exec('exit 0', $output, $exitCode);
expectStage($exitCode === 1 && !ObjectYPT::$writes, 'a missing queue entry must not start a worker');

$row = end(ObjectYPT::$reads);
$row['status'] = 'error';
ObjectYPT::$rows[135] = $row;
$stale->exec('exit 0', $output, $exitCode);
expectStage($exitCode === 1 && !ObjectYPT::$writes, 'a changed status must prevent a stale instance from starting');
expectStage(ObjectYPT::$rows[135]['status'] === 'error', 'a newer error status must be preserved');

ob_end_clean();
echo "PASS: stage notifications, stale worker state, exit codes, concurrent updates and deleted rows\n";
