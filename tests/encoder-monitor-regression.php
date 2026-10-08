<?php
// php tests/encoder-monitor-regression.php [encoder root]
// Real EncoderMonitor decisions (alerts, reminders, dead workers, system checks) with no
// database or HTTP; the schema files are checked for consistency with the installer.
if (PHP_SAPI !== 'cli') { exit(1); }
class ObjectYPT {}
class Login {}
class Streamer {}
class Format {}
$global = ['systemRootPath' => rtrim($argv[1] ?? dirname(__DIR__), '/\\') . '/',
    'docker_vars' => __DIR__ . '/nonexistent-docker-vars', 'tablesPrefix' => '',
    'webSiteRootURL' => 'https://encoder.example.test/'];
require_once $global['systemRootPath'] . 'objects/EncoderMonitor.php';

$checks = 0;
function checkMonitor($condition, $message)
{
    global $checks;
    if (!$condition) { throw new RuntimeException($message); }
    ++$checks;
}
function at($minutesAgo, $now = '2026-09-30 12:00:00')
{
    return date('Y-m-d H:i:s', strtotime($now) - $minutesAgo * 60);
}
function job($status, $modifiedMinutesAgo, array $extra = [])
{
    return array_merge(['id' => 77, 'status' => $status, 'modified' => at($modifiedMinutesAgo),
        'worker_pid' => 0, 'worker_ppid' => 0, 'status_obs' => '', 'retry_count' => 0], $extra);
}
function monitor(array $fields)
{
    return array_merge(['state' => '', 'state_since' => null, 'alerts_sent' => 0, 'last_alert_type' => null,
        'last_alert_at' => null, 'last_attempt_at' => null, 'last_result' => null, 'dead_since' => null], $fields);
}
$now = '2026-09-30 12:00:00';
$cfg = EncoderMonitor::defaults();

// Status groups
checkMonitor(EncoderMonitor::getGroup(Encoder::STATUS_QUEUE) === 'waiting', 'queue waits');
checkMonitor(EncoderMonitor::getGroup(Encoder::STATUS_DOWNLOADED) === 'waiting', 'downloaded waits for a slot');
foreach ([Encoder::STATUS_DOWNLOADING, Encoder::STATUS_ENCODING, Encoder::STATUS_PACKING, Encoder::STATUS_FIXING, Encoder::STATUS_TRANSFERRING] as $status) {
    checkMonitor(EncoderMonitor::getGroup($status) === 'processing', $status . ' is processing');
}
checkMonitor(EncoderMonitor::getGroup(Encoder::STATUS_ERROR) === 'error', 'error group');
checkMonitor(EncoderMonitor::getGroup(Encoder::STATUS_DONE) === '', 'done is not monitored');

// Processing: first alert after three hours, then one reminder per day
$d = EncoderMonitor::decideJob(job('encoding', 179), null, $now, $cfg);
checkMonitor($d['alert'] === '' && $d['monitor']['state'] === 'processing', 'no alert before three hours');
checkMonitor($d['monitor']['encoder_queue_id'] === 77 && $d['monitor']['state_since'] === at(179), 'new row starts at the status change');
$d = EncoderMonitor::decideJob(job('encoding', 181), null, $now, $cfg);
checkMonitor($d['alert'] === 'processing' && $d['minutes'] === 181, 'alert after three hours of processing');
$m = monitor(['state' => 'processing', 'state_since' => at(600), 'alerts_sent' => 1, 'last_alert_at' => at(1439), 'last_result' => 'sent']);
checkMonitor(EncoderMonitor::decideJob(job('transferring', 5), $m, $now, $cfg)['alert'] === '', 'no second alert within a day');
$m['last_alert_at'] = at(1440);
$d = EncoderMonitor::decideJob(job('transferring', 5), $m, $now, $cfg);
checkMonitor($d['alert'] === 'processing_reminder', 'daily reminder while still processing');
checkMonitor($d['minutes'] === 600, 'sub-state changes keep the processing clock');
$m = monitor(['state' => 'processing', 'state_since' => at(7 * 1440 + 1), 'alerts_sent' => 7, 'last_alert_at' => at(1500)]);
checkMonitor(EncoderMonitor::decideJob(job('encoding', 5), $m, $now, $cfg)['alert'] === '', 'reminders stop after the retention period');

// The joined row carries queue columns; the normalized row keeps only monitor fields and the job id
$joined = array_merge(job('encoding', 5), monitor(['state' => 'processing', 'state_since' => at(30)]));
$d = EncoderMonitor::decideJob($joined, $joined, $now, $cfg);
checkMonitor($d['monitor']['encoder_queue_id'] === 77 && !isset($d['monitor']['status']), 'monitor row is normalized for saving');

// Waiting: off by default for one day, can be disabled
checkMonitor(EncoderMonitor::decideJob(job('queue', 1439), null, $now, $cfg)['alert'] === '', 'waiting under a day is normal');
checkMonitor(EncoderMonitor::decideJob(job('queue', 1441), null, $now, $cfg)['alert'] === 'waiting', 'waiting over a day alerts');
$off = $cfg;
$off['waitingAlertMinutes'] = 0;
checkMonitor(EncoderMonitor::decideJob(job('queue', 5000), null, $now, $off)['alert'] === '', 'waiting alerts can be disabled');

// Errors: immediately, unless the transient auto-retry will handle it; then after 24h; then daily
checkMonitor(EncoderMonitor::decideJob(job('error', 2), null, $now, $cfg)['alert'] === 'error', 'error alerts right away');
checkMonitor(EncoderMonitor::decideJob(job('error', 2), null, $now, $cfg, true)['alert'] === '', 'pending auto-retry waits');
$manualQueue = $cfg;
$manualQueue['kickQueue'] = false;
checkMonitor(EncoderMonitor::decideJob(job('error', 2), null, $now, $manualQueue, true)['alert'] === 'error', 'disabled automatic queue still reports retryable errors');
checkMonitor(EncoderMonitor::decideJob(job('error', 1800), null, $now, $cfg)['alert'] === 'error_reminder', 'old error found later is a still-in-error alert');
$m = monitor(['state' => 'error', 'state_since' => at(1000), 'alerts_sent' => 1, 'last_alert_at' => at(1000), 'last_result' => 'sent']);
checkMonitor(EncoderMonitor::decideJob(job('error', 1000), $m, $now, $cfg)['alert'] === '', 'no repeat before 24h in error');
$m = monitor(['state' => 'error', 'state_since' => at(1440), 'alerts_sent' => 1, 'last_alert_at' => at(1440), 'last_result' => 'sent']);
checkMonitor(EncoderMonitor::decideJob(job('error', 1440), $m, $now, $cfg)['alert'] === 'error_reminder', 'still in error after 24h');
$m = monitor(['state' => 'error', 'state_since' => at(8 * 1440), 'alerts_sent' => 7, 'last_alert_at' => at(1440)]);
checkMonitor(EncoderMonitor::decideJob(job('error', 8 * 1440), $m, $now, $cfg)['alert'] === '', 'error reminders stop after retention');

// State changes: an error is always reported; a requeue does not restart processing alerts at once
$m = monitor(['state' => 'processing', 'state_since' => at(200), 'alerts_sent' => 1, 'last_alert_type' => 'processing', 'last_alert_at' => at(1), 'last_result' => 'sent']);
$d = EncoderMonitor::decideJob(job('error', 0), $m, $now, $cfg);
checkMonitor($d['alert'] === 'error' && $d['monitor']['alerts_sent'] === 0 && $d['monitor']['last_alert_at'] === at(1), 'error after processing alert is reported');
$m = monitor(['state' => 'error', 'state_since' => at(300), 'alerts_sent' => 1, 'last_alert_type' => 'error', 'last_alert_at' => at(240)]);
checkMonitor(EncoderMonitor::decideJob(job('encoding', 120), $m, $now, $cfg)['alert'] === '', 'requeued job waits a day after the last alert');

// A failed delivery is retried after an hour, not every minute
$noReminders = $cfg;
$noReminders['reminderIntervalMinutes'] = 0;
$m = monitor(['state' => 'waiting', 'state_since' => at(1800), 'alerts_sent' => 1, 'last_alert_type' => 'waiting', 'last_alert_at' => at(120)]);
checkMonitor(EncoderMonitor::decideJob(job('encoding', 185), $m, $now, $noReminders)['alert'] === 'processing', 'disabling reminders still permits the first alert for a new state');
$m = monitor(['state' => 'processing', 'state_since' => at(1800), 'alerts_sent' => 1, 'last_alert_type' => 'processing', 'last_alert_at' => at(1500)]);
checkMonitor(EncoderMonitor::decideJob(job('encoding', 185), $m, $now, $noReminders)['alert'] === '', 'disabling reminders prevents repeat alerts for the same state');

$m = monitor(['state' => 'error', 'state_since' => at(30), 'last_attempt_at' => at(10), 'last_result' => 'failed: timeout']);
checkMonitor(EncoderMonitor::decideJob(job('error', 30), $m, $now, $cfg)['alert'] === '', 'failed delivery backs off');
$m['last_attempt_at'] = at(60);
checkMonitor(EncoderMonitor::decideJob(job('error', 30), $m, $now, $cfg)['alert'] === 'error', 'failed delivery is retried');

// Delivery results
$apply = new ReflectionMethod('EncoderMonitor', 'applySendResult');
$apply->setAccessible(true);
$row = monitor(['encoder_queue_id' => 77, 'state' => 'error', 'state_since' => at(5)]);
$r = $apply->invoke(null, $row, 'error', $now, (object) ['response' => (object) ['error' => false], 'http_code' => 200]);
checkMonitor($r['last_result'] === 'sent' && $r['alerts_sent'] === 1 && $r['last_alert_at'] === $now, 'accepted alert is counted');
$r = $apply->invoke(null, $row, 'error', $now, (object) ['response' => (object) ['error' => false, 'skipped' => true], 'http_code' => 200, 'msg' => 'Recipient has no valid email']);
checkMonitor(strpos($r['last_result'], 'skipped') === 0 && $r['alerts_sent'] === 1, 'owner without e-mail is not retried every hour');
$r = $apply->invoke(null, $row, 'error', $now, (object) ['response' => null, 'http_code' => 404, 'msg' => 'Response was not an json object']);
checkMonitor(strpos($r['last_result'], 'skipped') === 0 && $r['alerts_sent'] === 1, 'older Streamer without the endpoint waits for the next reminder');
$r = $apply->invoke(null, $row, 'error', $now, (object) ['response' => (object) ['error' => true, 'permanent' => true], 'msg' => 'Unsupported alert']);
checkMonitor(strpos($r['last_result'], 'skipped') === 0, 'permanent refusal is not retried');
$r = $apply->invoke(null, $row, 'error', $now, (object) ['response' => (object) ['error' => true], 'http_code' => 403, 'msg' => 'Permission denied']);
checkMonitor(strpos($r['last_result'], 'failed') === 0 && $r['alerts_sent'] === 0 && $r['last_alert_at'] === null, 'refused alert is retried later');

// Dead workers: only when every recorded process is gone, for the whole grace period
foreach ([
    (object) ['response' => new stdClass(), 'http_code' => 200],
    (object) ['response' => (object) ['error' => false], 'http_code' => 503],
    (object) ['response' => (object) ['error' => false], 'http_code' => 200, 'curl_errno' => 28],
] as $invalidResponse) {
    $r = $apply->invoke(null, $row, 'error', $now, $invalidResponse);
    checkMonitor(strpos($r['last_result'], 'failed') === 0 && $r['alerts_sent'] === 0, 'unconfirmed delivery remains eligible for retry');
    checkMonitor(EncoderMonitor::classifyStreamerIssue($invalidResponse) !== '', 'unconfirmed delivery is visible to the admin');
}

$running = function ($pid) { return $pid === 500; };
checkMonitor(EncoderMonitor::isWorkerGone(job('encoding', 30, ['worker_ppid' => 400, 'worker_pid' => 401]), $running) === true, 'all processes gone');
checkMonitor(EncoderMonitor::isWorkerGone(job('encoding', 30, ['worker_ppid' => 500, 'worker_pid' => 401]), $running) === false, 'parent alive keeps the job');
checkMonitor(EncoderMonitor::isWorkerGone(job('encoding', 30), $running) === null, 'unknown without PIDs');
checkMonitor(EncoderMonitor::isWorkerGone(job('queue', 30, ['worker_ppid' => 400]), $running) === null, 'waiting jobs have no worker');
$row = monitor(['state' => 'processing']);
$dead = EncoderMonitor::decideDeadWorker(job('encoding', 30), $row, true, $now, $cfg);
checkMonitor($dead['dead_since'] === $now && !$dead['recover'], 'first sighting only starts the grace period');
$row['dead_since'] = at(10);
checkMonitor(EncoderMonitor::decideDeadWorker(job('encoding', 30), $row, true, $now, $cfg)['recover'], 'recover after the grace period');
checkMonitor(!EncoderMonitor::decideDeadWorker(job('encoding', 2), $row, true, $now, $cfg)['recover'], 'a recent save means it may still be working');
checkMonitor(EncoderMonitor::decideDeadWorker(job('encoding', 30), $row, false, $now, $cfg)['dead_since'] === null, 'a live worker resets the grace period');
$off = $cfg;
$off['recoverDeadWorkers'] = false;
checkMonitor(!EncoderMonitor::decideDeadWorker(job('encoding', 30), $row, true, $now, $off)['recover'], 'recovery can be disabled');

// After a container restart a recorded PID may belong to an unrelated process
checkMonitor(EncoderMonitor::isWorkerCommand("/usr/local/bin/php\0-f\0/var/www/html/view/run.php\0"), 'run.php worker');
checkMonitor(EncoderMonitor::isWorkerCommand("ffmpeg\0-i\0/tmp/in.mp4\0"), 'ffmpeg child');
checkMonitor(EncoderMonitor::isWorkerCommand("/usr/bin/python3\0/usr/local/bin/yt-dlp\0"), 'downloader');
checkMonitor(EncoderMonitor::isWorkerCommand("apache2\0-DFOREGROUND\0"), 'Apache/mod_php may be running an HTTP encode');
checkMonitor(EncoderMonitor::isWorkerCommand("/usr/sbin/httpd\0-k\0start\0"), 'httpd may be running an HTTP encode');
checkMonitor(EncoderMonitor::isWorkerCommand("php-fpm: pool www"), 'PHP-FPM HTTP worker');
checkMonitor(!EncoderMonitor::isWorkerCommand("/usr/sbin/cron\0-f\0"), 'cron reused the PID');
checkMonitor(!EncoderMonitor::isWorkerCommand("/bin/sh\0-c\0cron\0"), 'shell reused the PID');
checkMonitor(!EncoderMonitor::isWorkerCommand("/bin/sleep\0apache2\0"), 'an argument naming Apache is not a web worker');
$httpWorker = job('encoding', 30, ['worker_ppid' => 400]);
$gone = EncoderMonitor::isWorkerGone($httpWorker, function ($pid) {
    return $pid === 400 && EncoderMonitor::isWorkerCommand("/usr/sbin/apache2\0-DFOREGROUND\0");
});
checkMonitor($gone === false, 'live HTTP encode is not a dead worker');
checkMonitor(!EncoderMonitor::decideDeadWorker($httpWorker, monitor(['dead_since' => at(20)]), $gone, $now, $cfg)['recover'], 'long-running HTTP encode is not requeued');

// A video sent again gets a new job; the old failed job must not keep mailing the owner
$jobs = [
    ['id' => 10, 'streamers_id' => 1, 'return_vars' => '{"videos_id":5}'],
    ['id' => 11, 'streamers_id' => 2, 'return_vars' => '{"videos_id":5}'],
    ['id' => 12, 'streamers_id' => 1, 'return_vars' => '{"videos_id":5}'],
    ['id' => 13, 'streamers_id' => 1, 'return_vars' => '{"videos_id":6}'],
    ['id' => 14, 'streamers_id' => 1, 'return_vars' => ''],
];
checkMonitor(EncoderMonitor::getSupersededJobIds($jobs) === [10 => 12], 'only the older job of the same video and Streamer is superseded');

// A Streamer that does not answer is paused; one that answers with an error is not
checkMonitor(EncoderMonitor::isConnectionFailure((object) ['curl_errno' => 28, 'http_code' => 0]), 'timeout pauses');
checkMonitor(EncoderMonitor::isConnectionFailure((object) ['msg' => 'exception']), 'no HTTP answer pauses');
checkMonitor(!EncoderMonitor::isConnectionFailure((object) ['curl_errno' => 0, 'http_code' => 403]), 'refusal is not a connection failure');
checkMonitor($cfg['maxRunSeconds'] > 0 && $cfg['maxRunSeconds'] < 60, 'a run finishes before the next cron minute');

// Owners get a category, never the raw message with server paths or commands
$reasons = [
    'Encoder::run: Max tries reached Could not download the file' => 'download_failed',
    'Execute code error 2 [/var/www/html/videos/1_tmpFile.mp4] [1 == 1] "x" Code: ffmpeg -i /var/www/x' => 'conversion_failed',
    'Video #5221: Timed out during transfer. Check the connection and site logs, then use Recheck. Output files were kept.' => 'transfer_failed',
    'sendToStreamer cURL error (7): Failed to connect => objects/aVideoEncoder.json.php' => 'transfer_failed',
    'We could not get videos_id check the streamer logs' => 'video_not_found',
    EncoderMonitor::WORKER_STOPPED_MSG . ' and the automatic retry limit was reached.' => 'worker_stopped',
    'Transfer worker stopped unexpectedly. Output files were kept; use Recheck to retry.' => 'transfer_failed',
    'something else' => 'unknown',
];
foreach ($reasons as $message => $reason) {
    checkMonitor(EncoderMonitor::getErrorReason($message) === $reason, 'reason for: ' . $message);
}

// System checks
$stats = ['diskFree' => 50 * 1073741824, 'diskTotal' => 100 * 1073741824, 'waiting' => 0, 'processing' => 0, 'oldestWaitingMinutes' => 0, 'errorsLastHour' => 0];
checkMonitor(EncoderMonitor::decideSystemChecks($stats, $cfg) === [], 'healthy encoder');
checkMonitor(EncoderMonitor::decideSystemChecks(array_merge($stats, ['diskFree' => 4 * 1073741824]), $cfg) === ['disk_low'], 'low by percent');
checkMonitor(EncoderMonitor::decideSystemChecks(array_merge($stats, ['diskFree' => 1073741824, 'diskTotal' => 10 * 1073741824]), $cfg) === ['disk_low'], 'low by size');
checkMonitor(EncoderMonitor::decideSystemChecks(array_merge($stats, ['waiting' => 3, 'oldestWaitingMinutes' => 31]), $cfg) === ['queue_stalled'], 'stalled queue');
checkMonitor(EncoderMonitor::decideSystemChecks(array_merge($stats, ['waiting' => 3, 'processing' => 1, 'oldestWaitingMinutes' => 300]), $cfg) === [], 'a busy queue is not stalled');
checkMonitor(EncoderMonitor::decideSystemChecks(array_merge($stats, ['errorsLastHour' => 5]), $cfg) === ['error_spike'], 'error spike');

// Settings overrides
$global['encoderMonitor'] = ['processingAlertMinutes' => '120', 'adminAlerts' => 0, 'unknownKey' => 1, 'maxAlertsPerRun' => -3];
$custom = EncoderMonitor::getConfig();
checkMonitor($custom['processingAlertMinutes'] === 120 && $custom['adminAlerts'] === false, 'overrides are typed');
checkMonitor(!isset($custom['unknownKey']) && $custom['maxAlertsPerRun'] === 0, 'unknown keys ignored, negatives clamped');
unset($global['encoderMonitor']);

// Auto-retry helper mirrors requeueTransientErrors()
checkMonitor(Encoder::isAutoRetryPending('sendToStreamer cURL error (28): Operation timed out', 0), 'timeout is retried');
checkMonitor(!Encoder::isAutoRetryPending('sendToStreamer cURL error (28): Operation timed out', Encoder::MAX_AUTO_RETRIES), 'retry cap');
checkMonitor(!Encoder::isAutoRetryPending('Execute code error 2', 0), 'permanent errors are not retried');

// Schema: migration, fresh install and installer agree
$root = $global['systemRootPath'];
$migration = file_get_contents($root . 'update/updateDb.v8.3.sql');
$install = file_get_contents($root . 'install/database.sql');
foreach (['encoder_queue_monitor', 'encoder_monitor_state'] as $table) {
    $pattern = '/CREATE TABLE IF NOT EXISTS `' . $table . '` \(.*?ENGINE = InnoDB;/s';
    checkMonitor(preg_match($pattern, $migration, $a) && preg_match($pattern, $install, $b), $table . ' is defined in both schemas');
    checkMonitor(preg_replace('/\s+/', ' ', $a[0]) === preg_replace('/\s+/', ' ', $b[0]), $table . ' definitions match');
}
$migrationSql = preg_replace('/^\s*--.*$/m', '', $migration);
checkMonitor(stripos($migrationSql, 'CONSTRAINT') === false, 'update.php does not prefix constraint names, so none are named');
checkMonitor(strpos($migration, "version = '8.3'") !== false, 'migration records its version');
$installer = file_get_contents($root . 'install/installer.php');
checkMonitor(strpos($installer, "'encoder_queue_monitor','encoder_monitor_state'") !== false, 'installer verifies the new tables');
checkMonitor(preg_match("/^define\('INSTALLER_SCHEMA_VERSION', '([0-9.]+)'\);/m", $installer, $schema) === 1, 'installer defines INSTALLER_SCHEMA_VERSION');
checkMonitor(strpos($installer, '$version = INSTALLER_SCHEMA_VERSION;') !== false, 'fresh installs record INSTALLER_SCHEMA_VERSION');
$migrationVersions = array_map(function ($file) { return substr(basename($file, '.sql'), 10); }, glob($root . 'update/updateDb.v*.sql'));
usort($migrationVersions, 'version_compare');
checkMonitor(end($migrationVersions) === $schema[1], 'INSTALLER_SCHEMA_VERSION is the newest migration');
checkMonitor(strpos(file_get_contents($root . 'update/updateDb.v' . $schema[1] . '.sql'), "version = '" . $schema[1] . "'") !== false, 'the newest migration records INSTALLER_SCHEMA_VERSION');

// Cron entries are generated for the real installation folder; several Encoders can share a server
require_once $root . 'objects/EncoderCron.php';
$path = 'PATH=' . EncoderCron::PATH . ' ';
$a = EncoderCron::getFileName('/var/www/html/');
$b = EncoderCron::getFileName('/srv/encoders/site b');
checkMonitor($a !== $b && preg_match('/^avideo-encoder-[0-9a-f]{8}$/', $a) === 1, 'one cron.d file per folder, valid cron.d name');
checkMonitor($a === EncoderCron::getFileName('/var/www/html'), 'trailing slash does not change the name');
checkMonitor(EncoderCron::shellQuote("/srv/it's here") === "'/srv/it'\\''s here'", 'POSIX quoting survives quotes');
$file = EncoderCron::buildFile('/srv/encoders/site b/', '/usr/local/bin/php', 'www-data');
checkMonitor(strpos($file, "\r") === false && substr($file, -1) === "\n", 'cron.d content uses LF and ends with a newline');
checkMonitor(strpos($file, "* * * * * www-data {$path}'/usr/local/bin/php' '/srv/encoders/site b/install/cron.php' > /dev/null 2>&1\n") !== false, 'runs every minute as the web user from its own folder');
checkMonitor(strpos(EncoderCron::PATH, '/usr/local/bin') !== false, 'run.php started by cron finds tools in /usr/local/bin');
checkMonitor(strpos(EncoderCron::buildFile('/srv/100% encoder', '/srv/php%/php', 'www-data'), "'/srv/php\\%/php' '/srv/100\\% encoder/install/cron.php'") !== false, 'cron.d escapes percent in both executable and script paths');
checkMonitor(strpos(file_get_contents($root . 'deploy/docker-entrypoint'), 'install/installCron.php --user=www-data') !== false, 'Docker image installs the cron through the same generator');
$installCron = file_get_contents($root . 'install/installCron.php');
checkMonitor(strpos($installCron, 'configuration.php\';') === false && strpos($installCron, "require_once \$configFile") === false, 'the root installer never loads the Encoder configuration');
checkMonitor(preg_match('/\b(exec|shell_exec|system|passthru|proc_open|popen)\s*\(/', $installCron) === 0, 'no user crontab fallback: the web server user can never install a cron');
checkMonitor(strpos($installCron, 'if (!$isRoot) {') !== false && strpos($installCron, 'if (!$isRoot) {') < strpos($installCron, 'file_put_contents('), 'only root writes the cron.d entry');
$cronScript = file_get_contents($root . 'install/cron.php');
checkMonitor(strpos($cronScript, 'posix_geteuid') < strpos($cronScript, 'videos/configuration.php\''), 'cron.php refuses root before loading the configuration');

// Per-site delivery problems shown to the Encoder admin
checkMonitor(EncoderMonitor::classifyStreamerIssue((object) ['curl_errno' => 7, 'http_code' => 0]) === 'unreachable', 'site offline');
checkMonitor(EncoderMonitor::classifyStreamerIssue((object) ['curl_errno' => 0, 'http_code' => 404]) === 'no_endpoint', 'older AVideo version');
checkMonitor(EncoderMonitor::classifyStreamerIssue((object) ['curl_errno' => 0, 'http_code' => 403, 'response' => (object) ['error' => true]]) === 'rejected', 'account refused');
checkMonitor(EncoderMonitor::classifyStreamerIssue((object) ['curl_errno' => 0, 'http_code' => 200, 'response' => (object) ['error' => false, 'skipped' => true]]) === '', 'skipped is still delivered');

// Monitor tab: admin only, and every dynamic value is escaped once
$refresh = file_get_contents($root . 'view/monitorDashboard.refresh.php');
checkMonitor(strpos($refresh, 'if (!Login::isAdmin())') !== false && strpos($refresh, 'if (!Login::isAdmin())') < strpos($refresh, "include \$global['systemRootPath'] . 'view/monitorDashboard.php'"),'refresh endpoint checks admin before rendering');
foreach (['view/monitorDashboard.php', 'view/monitorTab.php'] as $view) {
    $source = file_get_contents($root . $view);
    checkMonitor(strpos($source, "if (!Login::isAdmin()) {\n    return;") !== false || strpos($source, "if (!Login::isAdmin()) {\r\n    return;") !== false, $view . ' renders only for admins');
}
$dashboard = file_get_contents($root . 'view/monitorDashboard.php');
checkMonitor(substr_count($dashboard, '__(') === 1 && strpos($dashboard, 'return __($msg, true);') !== false, 'dashboard translates only through $tr, escaped by $esc');
checkMonitor(!preg_match('/echo\s+\$job\[|echo\s+\$report\[|echo\s+\$summary\[\'(?!alerts|jobs|requeued|workers)/', $dashboard), 'job, report and summary text is never echoed unescaped');

echo "PASS: {$checks} encoder monitor checks\n";
