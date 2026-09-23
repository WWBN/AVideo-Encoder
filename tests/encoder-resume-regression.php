<?php
// php tests/encoder-resume-regression.php [encoder root]
// Real probes, ZIP creation and send orchestration; isolated DB and HTTP boundaries.
if (PHP_SAPI !== 'cli') {
    exit(1);
}
class ObjectYPT
{
    public static $rows = [];
    public function __construct($id) { $this->load($id); }
    protected function load($id)
    {
        if (!isset(self::$rows[$id])) { return false; }
        foreach (self::$rows[$id] as $key => $value) { $this->$key = $value; }
        return true;
    }
    public function save()
    {
        self::$rows[$this->id] = get_object_vars($this);
        return $this->id;
    }
}
class Login {}
class Streamer {}
class Configuration
{
    public static $autodelete = false;
    public function getAutodelete() { return self::$autodelete; }
}
class Format
{
    public function __construct($id) {}
    public function getOrder() { return 99; }
    public function getExtension() { return 'mp4'; }
}
$global = [
    'systemRootPath' => rtrim($argv[1] ?? dirname(__DIR__), '/\\') . '/',
    'webSiteRootURL' => 'http://localhost/',
    'docker_vars' => __DIR__ . '/nonexistent-docker-vars',
    'multiResolutionOrder' => [], 'sendAll' => []
];
require_once $global['systemRootPath'] . 'objects/Encoder.php';
class ResumeFixture extends Encoder
{
    public static $dispatches = 0;
    public static $launchFails = false;
    public static $transferFails = false;
    public static $confirmationFails = false;
    public static $confirmed = false;
    public static $deleted = false;
    public static $transfers = 0;
    public static $failureCode = '';
    protected function dispatchOutputResume()
    {
        self::$dispatches++;
        return self::$launchFails ? '' : (string) getmypid();
    }
    public static function sendFileChunk($file, $return_vars, $format, $encoder = null, $resolution = '', $try = 0, $fileId = null, $startChunk = 0)
    {
        self::$transfers++;
        checkResume(is_file($file), 'transfer receives an existing encoded file');
        checkResume($encoder->getStatus() === 'transferring', 'transfer status is persisted');
        if ($format === 'zip') {
            $zip = new ZipArchive();
            checkResume($zip->open($file, ZipArchive::CHECKCONS) === true, 'transfer receives a valid ZIP');
            checkResume($zip->locateName('index.m3u8') !== false, 'ZIP contains the HLS playlist');
            $zip->close();
        }
        return (object) ['error' => self::$transferFails, 'msg' => 'fixture response',
            'response' => (object) ['video_id' => 0, 'video_id_hash' => '', 'code' => self::$failureCode]];
    }
    protected function notifyVideoIsDone($fail = 0)
    {
        checkResume($this->getStatus() === 'transferring' && !self::$deleted,
            'send(false) defers completion and deletion until confirmation');
        self::$confirmed = true;
        return (object) ['error' => self::$confirmationFails, 'code' => self::$failureCode];
    }
    public function delete()
    {
        checkResume(self::$confirmed && !self::$confirmationFails && $this->getStatus() === 'done',
            'automatic cleanup only follows confirmed success');
        self::$deleted = true;
        return true;
    }
}
function checkResume($condition, $message)
{
    if (!$condition) { throw new RuntimeException($message); }
    echo "PASS: {$message}\n";
}
function resumeRow($id, $format = 29)
{
    ObjectYPT::$rows[$id] = ['id' => $id, 'streamers_id' => 1, 'formats_id' => $format,
        'title' => 'resume fixture', 'status' => 'error', 'status_obs' => 'Corrupted output',
        'return_vars' => json_encode(['videos_id' => $id + 100]), 'worker_pid' => 0, 'worker_ppid' => 0, 'retry_count' => 0];
    ResumeFixture::$confirmed = ResumeFixture::$deleted = false;
    return new ResumeFixture($id);
}
function mediaFixture($args)
{
    exec(removeUserAgentIfNotURL(get_ffmpeg(true) . ' -v error -y ' . $args) . ' 2>&1', $output, $code);
    if ($code) { throw new RuntimeException(implode("\n", $output)); }
}
$testDir = sys_get_temp_dir() . '/encoder-resume-test-' . bin2hex(random_bytes(8));
mkdir($testDir, 0700);
mkdir($testDir . '/videos');
$global['systemRootPath'] = $testDir . '/';
$global['logfile'] = $testDir . '/test.log';
ini_set('error_log', $global['logfile']);
try {
    $encoder = resumeRow(71);
    $zipFile = Encoder::getTmpFileName(71, 'zip');
    $hls = substr($zipFile, 0, -4);
    mkdir($hls);
    $source = $testDir . '/source.mp4';
    mediaFixture('-f lavfi -i color=size=160x90:rate=25 -t 2 -an -c:v libx264 ' . escapeshellarg($source));
    mediaFixture('-i ' . escapeshellarg($source) . ' -c copy -hls_time 1 -hls_list_size 0 ' . escapeshellarg($hls . '/index.m3u8'));
    $result = $encoder->startOutputResume();
    checkResume(!empty($result['started']) && $encoder->getStatus() === 'packing', 'valid HLS starts packing without encoding');
    checkResume($encoder->getRetry_count() === Encoder::MAX_AUTO_RETRIES, 'manual transfer cannot fall back into automatic re-encoding');
    $duplicate = (new ResumeFixture(71))->startOutputResume();
    checkResume($duplicate['error'] && ResumeFixture::$dispatches === 1, 'duplicate request does not launch another worker');
    checkResume($encoder->resumeOutputTransfer() && $encoder->getStatus() === 'done', 'packing and transfer complete successfully');
    checkResume(is_file($zipFile) && !ResumeFixture::$deleted, 'files remain when autodelete is disabled');
    $hash = hash_file('sha256', $zipFile);
    touch($zipFile, 1000000000);

    $encoder = resumeRow(71);
    Configuration::$autodelete = true;
    $encoder->startOutputResume();
    checkResume($encoder->resumeOutputTransfer() && ResumeFixture::$deleted, 'autodelete runs after confirmation');
    clearstatcache(true, $zipFile);
    checkResume(hash_file('sha256', $zipFile) === $hash && filemtime($zipFile) === 1000000000, 'existing valid ZIP is reused without repacking');

    foreach (['transferFails', 'confirmationFails'] as $failure) {
        $encoder = resumeRow(71);
        ResumeFixture::${$failure} = true;
        ResumeFixture::$failureCode = $failure === 'transferFails' ? 'destination_unavailable' : 'completion_in_progress';
        $encoder->startOutputResume();
        checkResume(!$encoder->resumeOutputTransfer() && $encoder->getStatus() === 'error', $failure . ' produces retryable error');
        checkResume(is_file($zipFile) && !ResumeFixture::$deleted && hash_file('sha256', $zipFile) === $hash, $failure . ' preserves encoded output');
        checkResume($encoder->getWorker_ppid() === 0, 'failed transfer clears worker PID');
        checkResume(strpos($encoder->getStatus_obs(), $failure === 'transferFails' ? 'removed or this account cannot edit' : 'still processing') !== false,
            'specific cause survives send and resume error handling');
        checkResume(strpos($encoder->getStatus_obs(), 'Video #171:') !== false, 'status identifies the destination');
        ResumeFixture::${$failure} = false;
        ResumeFixture::$failureCode = '';
        $transfers = ResumeFixture::$transfers;
        ResumeFixture::$confirmed = false;
        $encoder = new ResumeFixture(71);
        $encoder->startOutputResume();
        checkResume($encoder->resumeOutputTransfer(), $failure . ' retry completes');
        checkResume(ResumeFixture::$transfers === $transfers + ($failure === 'transferFails' ? 1 : 0),
            $failure === 'transferFails' ? 'failed upload is transferred again' : 'confirmation retry does not resend or repack the received output');
    }
    $vars = json_decode($encoder->getReturn_vars());
    checkResume(isset($vars->output_transfer), 'successful upload receipt is persisted');
    $vars->videos_id++;
    $encoder->setReturn_vars(json_encode($vars));
    $encoder->save();
    ResumeFixture::$deleted = false;
    $transfers = ResumeFixture::$transfers;
    $encoder->startOutputResume();
    checkResume($encoder->resumeOutputTransfer() && ResumeFixture::$transfers > $transfers,
        'a different destination video cannot reuse an old upload receipt');
    $encoder->setStatus(Encoder::STATUS_QUEUE, false);
    checkResume(!isset(json_decode($encoder->getReturn_vars())->output_transfer), 'reencode invalidates the old upload receipt');
    file_put_contents($zipFile, 'broken archive');
    $encoder = resumeRow(71);
    $encoder->startOutputResume();
    checkResume($encoder->resumeOutputTransfer(), 'broken ZIP is rebuilt from valid HLS');

    $encoder = resumeRow(72, 1);
    $mp4 = Encoder::getTmpFileName(72, 'mp4');
    copy($source, $mp4);
    $encoder->startOutputResume();
    checkResume($encoder->getStatus() === 'transferring' && $encoder->resumeOutputTransfer(), 'MP4 resumes directly with no packaging');

    $encoder = resumeRow(72, 1);
    ResumeFixture::$launchFails = true;
    try {
        $encoder->startOutputResume();
        throw new LogicException('launch failure should throw');
    } catch (RuntimeException $expected) {
        checkResume($encoder->getStatus() === 'error' && is_file($mp4), 'worker launch failure preserves files and resets status');
    }
    ResumeFixture::$launchFails = false;
    file_put_contents($mp4, 'corrupted output');
    $getDurationFromFile = [];
    $encoder = resumeRow(72, 1);
    $before = ResumeFixture::$dispatches;
    $result = $encoder->startOutputResume();
    checkResume($result['error'] && $encoder->getStatus() === 'error' && ResumeFixture::$dispatches === $before, 'invalid media never starts a transfer');

    $lock = fopen(sys_get_temp_dir() . '/encoder_resume.' . md5($global['systemRootPath']) . '.71.lock', 'c');
    flock($lock, LOCK_EX);
    $encoder = resumeRow(71);
    checkResume($encoder->startOutputResume()['error'], 'simultaneous request is blocked by the per-job lock');
    flock($lock, LOCK_UN);
    fclose($lock);
} catch (Throwable $error) {
    fwrite(STDERR, 'FAIL: ' . $error->getMessage() . "\n" . $error->getTraceAsString() . "\n");
    $failed = true;
} finally {
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($testDir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
    rmdir($testDir);
    foreach ([71, 72] as $id) {
        $lockFile = sys_get_temp_dir() . '/encoder_resume.' . md5($global['systemRootPath']) . '.' . $id . '.lock';
        if (is_file($lockFile)) { unlink($lockFile); }
    }
}
exit(empty($failed) ? 0 : 1);
