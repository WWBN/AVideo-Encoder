<?php
if (PHP_SAPI !== 'cli') { exit(1); }
class ObjectYPT {
    public static $rows = [];
    public function __construct($id) { $this->load($id); }
    protected function load($id) {
        if (!isset(self::$rows[$id])) { return false; }
        foreach (self::$rows[$id] as $key => $value) { $this->$key = $value; }
        return true;
    }
    public function save() { self::$rows[$this->id] = get_object_vars($this); return $this->id; }
}
class Login {}
class Streamer {}
class Format {}
$global = ['systemRootPath' => rtrim($argv[1] ?? dirname(__DIR__), '/\\') . '/', 'docker_vars' => __DIR__ . '/missing'];
require $global['systemRootPath'] . 'objects/Encoder.php';
require $global['systemRootPath'] . 'objects/EncoderMonitor.php';
class RecoveryFixture extends Encoder {
    public static $reply, $duringRequest, $requests = [], $resumes = 0, $hasOutput = true;
    protected function requestJobStatus($withCredentials = false) {
        self::$requests[] = [$this->getStreamers_id(), $withCredentials];
        if (self::$duringRequest) { call_user_func(self::$duringRequest, $this->getId()); }
        return clone self::$reply;
    }
    public function startOutputResume() {
        ++self::$resumes;
        if (!self::$hasOutput) { return ['error' => true, 'files' => []]; }
        $this->setStatus('transferring', false);
        $this->save();
        return ['error' => false, 'started' => true];
    }
}
$checks = 0;
$testLog = tempnam(sys_get_temp_dir(), 'encoder-recovery-test-');
ini_set('error_log', $testLog);
ob_start();
function expectRecovery($condition, $message) {
    global $checks;
    if (!$condition) { throw new RuntimeException($message); }
    ++$checks;
}
function jobRecovery($id, $streamer = 1, $extra = []) {
    ObjectYPT::$rows[$id] = array_merge(['id' => $id, 'streamers_id' => $streamer, 'status' => 'error',
        'status_obs' => 'Execute code error 2', 'return_vars' => json_encode(['videos_id' => $id + 100]),
        'retry_count' => 0, 'downloadedFileName' => '', 'fileURI' => ''], $extra);
    return new RecoveryFixture($id);
}
try {
    RecoveryFixture::$reply = (object) ['error' => false, 'code' => 'ready', 'video_id_hash' => 'fresh-job-token'];
    $job = jobRecovery(1);
    expectRecovery($job->recoverJobAutomatically(), 'valid retained output resumes automatically');
    expectRecovery(RecoveryFixture::$resumes === 1 && $job->getStatus() === 'transferring', 'recovery resumes transfer instead of re-encoding');
    expectRecovery(json_decode($job->getReturn_vars())->video_id_hash === 'fresh-job-token', 'renewed authorization stays with this job');
    $other = jobRecovery(2, 2);
    expectRecovery($other->recoverJobAutomatically(), 'a second independent streamer recovers');
    expectRecovery(RecoveryFixture::$requests === [[1, false], [2, false]], 'each job contacts only its own streamer without account password retries');

    foreach (['destination_deleted', 'destination_access_denied', 'authentication_required'] as $code) {
        $job = jobRecovery(3);
        RecoveryFixture::$reply = (object) ['error' => true, 'code' => $code];
        $resumes = RecoveryFixture::$resumes;
        expectRecovery(!$job->recoverJobAutomatically(), $code . ' does not transfer or encode');
        $state = json_decode($job->getReturn_vars())->recovery;
        expectRecovery($state->terminal && $state->code === $code && RecoveryFixture::$resumes === $resumes, $code . ' remains explicit and terminal');
        $calls = count(RecoveryFixture::$requests);
        $job->recoverJobAutomatically();
        expectRecovery(count(RecoveryFixture::$requests) === $calls, 'terminal failure does not hammer site login');
    }
    $job = jobRecovery(4);
    RecoveryFixture::$reply = (object) ['error' => true, 'code' => 'authentication_throttled'];
    expectRecovery(!$job->recoverJobAutomatically(), 'rate limit defers recovery');
    $state = json_decode($job->getReturn_vars())->recovery;
    expectRecovery(!$state->terminal && $state->next_attempt_at > time(), 'transient failure schedules a future retry');
    $calls = count(RecoveryFixture::$requests);
    $job->recoverJobAutomatically();
    expectRecovery(count(RecoveryFixture::$requests) === $calls, 'backoff prevents repeated requests');

    $job = jobRecovery(5);
    RecoveryFixture::$reply = (object) ['error' => true, 'code' => 'status_endpoint_unavailable'];
    expectRecovery(!$job->recoverJobAutomatically() && json_decode($job->getReturn_vars())->recovery->legacy, 'older streamer keeps the legacy recovery path');

    RecoveryFixture::$reply = (object) ['error' => false, 'code' => 'ready'];
    $job = jobRecovery(10);
    expectRecovery(!$job->recoverJobAutomatically() && !json_decode($job->getReturn_vars())->recovery->terminal,
        'an incomplete ready response cannot grant job authorization');
    RecoveryFixture::$reply = (object) ['error' => false, 'code' => 'ready', 'video_id_hash' => 'fresh-job-token'];
    $job = jobRecovery(6, 1, ['return_vars' => json_encode(['videos_id' => 106, 'failure' => ['stage' => 'encoding', 'exit_code' => 1]])]);
    $resumes = RecoveryFixture::$resumes;
    expectRecovery(!$job->recoverJobAutomatically() && RecoveryFixture::$resumes === $resumes, 'nonzero ffmpeg exit never uploads a partial playable output');
    $job = jobRecovery(7, 1, ['fileURI' => 'https://one.example/original.mp4']);
    RecoveryFixture::$hasOutput = false;
    expectRecovery($job->recoverJobAutomatically() && $job->getStatus() === 'queue', 'missing output automatically requeues an available original');
    $job = jobRecovery(8);
    expectRecovery(!$job->recoverJobAutomatically() && json_decode($job->getReturn_vars())->recovery->code === 'output_missing', 'missing source and output is explicit');
    $job = jobRecovery(12, 1, ['fileURI' => 'https://one.example/original.mp4',
        'return_vars' => json_encode(['videos_id' => 112, 'failure' => ['stage' => 'encoding', 'exit_code' => 1, 'message' => 'Connection timed out']])]);
    $resumes = RecoveryFixture::$resumes;
    expectRecovery($job->recoverJobAutomatically() && $job->getStatus() === 'queue' && RecoveryFixture::$resumes === $resumes,
        'a transient FFmpeg input failure re-encodes the source without transferring partial output');
    $job = jobRecovery(13, 1, ['return_vars' => json_encode(['videos_id' => 113,
        'failure' => ['stage' => 'transfer', 'code' => 'transfer_too_large']])]);
    expectRecovery(!$job->recoverJobAutomatically() && json_decode($job->getReturn_vars())->recovery->terminal
        && strpos($job->getStatus_obs(), 'HTTP 413') !== false, 'an upload size limit is explicit and never creates an endless transfer loop');
    expectRecovery(Encoder::getRecoveryMessage('recovering_output') === '', 'old recovery state does not hide current transfer diagnostics');
    foreach (['status' => 'queue', 'streamers_id' => 99] as $field => $newValue) {
        $job = jobRecovery(9);
        RecoveryFixture::$duringRequest = function ($id) use ($field, $newValue) { ObjectYPT::$rows[$id][$field] = $newValue; };
        $resumes = RecoveryFixture::$resumes;
        expectRecovery(!$job->recoverJobAutomatically() && ObjectYPT::$rows[9][$field] === $newValue
            && RecoveryFixture::$resumes === $resumes, 'concurrent ' . $field . ' change is preserved');
        RecoveryFixture::$duringRequest = null;
    }
    $job = jobRecovery(11, 1, ['return_vars' => json_encode(['videos_id' => 111,
        'recovery' => ['code' => 'destination_deleted', 'terminal' => true]])]);
    $alert = new ReflectionMethod('EncoderMonitor', 'sendOwnerAlert');
    $alert->setAccessible(true);
    $response = null;
    $row = $alert->invokeArgs(null, [array_merge(ObjectYPT::$rows[11], ['id' => 11]),
        ['alerts_sent' => 0], ['alert' => 'error'], date('Y-m-d H:i:s'), [], 0, &$response, microtime(true) + 5]);
    expectRecovery($row['last_result'] === 'skipped: destination video was deleted' && $response === null,
        'a deleted destination never retries the saved login to send owner alerts');
    ob_end_clean();
    unlink($testLog);
    echo 'PASS: ' . $checks . ' automatic recovery checks' . PHP_EOL;
} catch (Throwable $error) {
    ob_end_clean();
    unlink($testLog);
    fwrite(STDERR, 'FAIL: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
