<?php

/**
 * Periodic health checks for the encoding queue. install/cron.php calls run() every minute.
 *
 * Each run:
 *  1. requeues transient errors (Encoder::requeueTransientErrors(), which otherwise only runs
 *     when something else starts the queue);
 *  2. tracks how long every job has been waiting, processing or in error;
 *  3. requeues jobs whose worker process died (or fails them after MAX_AUTO_RETRIES);
 *  4. asks the job's own Streamer to e-mail the video owner (slow, failed, still failed);
 *  5. restarts the queue when jobs wait and nothing is running;
 *  6. e-mails the Encoder admins (streamers.isAdmin = 1) about low disk, a stalled queue or
 *     an error spike;
 *  7. records a heartbeat, its own failures and the Streamers that did not accept alerts, which
 *     view/monitorStatus.php shows to the Encoder admin.
 *
 * Every alert goes to the Streamer that submitted the job, through Encoder::sendToStreamer(),
 * so one Encoder keeps serving several independent Streamers. The cron belongs to the Encoder
 * installation (any folder, see install/installCron.php and objects/EncoderCron.php), never to one Streamer.
 *
 * Optional overrides in videos/configuration.php, using the keys of defaults():
 *   $global['encoderMonitor'] = ['processingAlertMinutes' => 120, 'adminAlerts' => false];
 */
require_once $global['systemRootPath'] . 'objects/Encoder.php';

class EncoderMonitor
{
    const GROUP_WAITING = 'waiting';
    const GROUP_PROCESSING = 'processing';
    const GROUP_ERROR = 'error';

    const ALERT_WAITING = 'waiting';
    const ALERT_WAITING_REMINDER = 'waiting_reminder';
    const ALERT_PROCESSING = 'processing';
    const ALERT_PROCESSING_REMINDER = 'processing_reminder';
    const ALERT_ERROR = 'error';
    const ALERT_ERROR_REMINDER = 'error_reminder';

    const SYSTEM_DISK_LOW = 'disk_low';
    const SYSTEM_QUEUE_STALLED = 'queue_stalled';
    const SYSTEM_ERROR_SPIKE = 'error_spike';

    const REASON_DOWNLOAD = 'download_failed';
    const REASON_CONVERSION = 'conversion_failed';
    const REASON_TRANSFER = 'transfer_failed';
    const REASON_WORKER = 'worker_stopped';
    const REASON_NO_VIDEO = 'video_not_found';
    const REASON_UNKNOWN = 'unknown';

    const STREAMER_ENDPOINT = 'objects/aVideoEncoderAlert.json.php';
    const STATE_HEARTBEAT = 'heartbeat';
    const STATE_LAST_ERROR = 'last_error';
    const STATE_STREAMER_ISSUE = 'streamer_issue_';

    const ISSUE_UNREACHABLE = 'unreachable';
    const ISSUE_NO_ENDPOINT = 'no_endpoint';
    const ISSUE_REJECTED = 'rejected';
    const WORKER_STOPPED_MSG = 'Worker process stopped unexpectedly';

    public static function defaults()
    {
        return [
            'ownerAlerts' => true,             // e-mail the video owner through the Streamer
            'adminAlerts' => true,             // e-mail the Encoder admins about system problems
            'recoverDeadWorkers' => true,      // requeue jobs whose worker process died
            'kickQueue' => true,               // start the queue when jobs wait and nothing runs
            'processingAlertMinutes' => 60,    // first alert for a job still processing (0 = off)
            'waitingAlertMinutes' => 1440,     // first alert for a job still waiting (0 = off)
            'errorReminderMinutes' => 1440,    // "still in error" alert
            'reminderIntervalMinutes' => 1440, // repeat while nothing changes (0 = no repeats)
            'retentionDays' => 7,              // stop alerting after this long in one state
            'failedSendRetryMinutes' => 60,    // retry an alert the Streamer did not accept
            'maxAlertsPerRun' => 10,           // spreads the first run after installation
            'maxRunSeconds' => 50,             // stop sending alerts after this, the next run continues
            'deadWorkerGraceMinutes' => 10,    // the worker must be gone this long
            'diskMinFreePercent' => 5,
            'diskMinFreeGB' => 2,
            'queueStalledMinutes' => 30,
            'errorSpikeCount' => 5,            // errors within the last hour
            'systemAlertIntervalMinutes' => 360,
            'heartbeatStaleMinutes' => 10,
        ];
    }

    public static function getConfig()
    {
        global $global;
        $cfg = self::defaults();
        if (!empty($global['encoderMonitor']) && is_array($global['encoderMonitor'])) {
            foreach ($global['encoderMonitor'] as $key => $value) {
                if (!array_key_exists($key, $cfg)) {
                    continue;
                }
                $cfg[$key] = is_bool($cfg[$key]) ? !empty($value) : max(0, intval($value));
            }
        }
        return $cfg;
    }

    public static function getGroup($status)
    {
        switch ($status) {
            case Encoder::STATUS_QUEUE:
            case Encoder::STATUS_DOWNLOADED:
                return self::GROUP_WAITING;
            case Encoder::STATUS_DOWNLOADING:
            case Encoder::STATUS_ENCODING:
            case Encoder::STATUS_PACKING:
            case Encoder::STATUS_FIXING:
            case Encoder::STATUS_TRANSFERRING:
                return self::GROUP_PROCESSING;
            case Encoder::STATUS_ERROR:
                return self::GROUP_ERROR;
        }
        return '';
    }

    public static function getMonitoredStatuses()
    {
        return [
            Encoder::STATUS_QUEUE, Encoder::STATUS_DOWNLOADED, Encoder::STATUS_DOWNLOADING,
            Encoder::STATUS_ENCODING, Encoder::STATUS_PACKING, Encoder::STATUS_FIXING,
            Encoder::STATUS_TRANSFERRING, Encoder::STATUS_ERROR,
        ];
    }

    public static function getMonitorFields()
    {
        return ['state', 'state_since', 'alerts_sent', 'last_alert_type', 'last_alert_at', 'last_attempt_at', 'last_result', 'dead_since'];
    }

    public static function minutesBetween($from, $to)
    {
        $fromTs = empty($from) ? false : strtotime($from);
        $toTs = empty($to) ? false : strtotime($to);
        if ($fromTs === false || $toTs === false) {
            return 0;
        }
        return max(0, intval(floor(($toTs - $fromTs) / 60)));
    }

    /**
     * Decides the monitor row and the alert (if any) for one job. Pure: no database, no network.
     *
     * @param array $job encoder_queue row (id, status, modified)
     * @param array|null $monitor previous encoder_queue_monitor row
     * @param string $now database NOW()
     * @return array ['monitor' => array, 'alert' => string, 'minutes' => int]
     */
    public static function decideJob(array $job, $monitor, $now, array $cfg, $autoRetryPending = false)
    {
        $group = self::getGroup($job['status']);
        $row = [];
        if (is_array($monitor) && !empty($monitor['state'])) {
            foreach (self::getMonitorFields() as $field) {
                $row[$field] = isset($monitor[$field]) ? $monitor[$field] : null;
            }
            $row['encoder_queue_id'] = intval($job['id']);
        }
        if (empty($row) || $row['state'] !== $group) {
            // modified is the save that changed the status, so it never makes a job look older
            // than it is. The last alert is carried over so a requeue cannot restart the clock
            // for waiting/processing reminders.
            $row = [
                'encoder_queue_id' => intval($job['id']),
                'state' => $group,
                'state_since' => empty($job['modified']) ? $now : $job['modified'],
                'alerts_sent' => 0,
                'last_alert_type' => empty($row['last_alert_type']) ? null : $row['last_alert_type'],
                'last_alert_at' => empty($row['last_alert_at']) ? null : $row['last_alert_at'],
                'last_attempt_at' => null,
                'last_result' => null,
                'dead_since' => null,
            ];
        }
        $minutes = self::minutesBetween($row['state_since'], $now);
        $decision = ['monitor' => $row, 'alert' => '', 'minutes' => $minutes];

        if ($minutes > $cfg['retentionDays'] * 1440) {
            return $decision;
        }
        $lastResult = (string) $row['last_result'];
        if (!empty($row['last_attempt_at']) && strpos($lastResult, 'failed') === 0
            && self::minutesBetween($row['last_attempt_at'], $now) < $cfg['failedSendRetryMinutes']) {
            return $decision;
        }

        $sent = intval($row['alerts_sent']);
        $interval = $cfg['reminderIntervalMinutes'];
        $sinceLast = empty($row['last_alert_at']) ? null : self::minutesBetween($row['last_alert_at'], $now);
        $quietLongEnough = $sinceLast === null || $interval === 0 || $sinceLast >= $interval;
        $reminderDue = $sent > 0 && $interval > 0 && $sinceLast !== null && $sinceLast >= $interval;

        switch ($group) {
            case self::GROUP_PROCESSING:
            case self::GROUP_WAITING:
                $threshold = $group === self::GROUP_PROCESSING ? $cfg['processingAlertMinutes'] : $cfg['waitingAlertMinutes'];
                if ($threshold <= 0) {
                    break;
                }
                if ($sent === 0 && $minutes >= $threshold && $quietLongEnough) {
                    $decision['alert'] = $group === self::GROUP_PROCESSING ? self::ALERT_PROCESSING : self::ALERT_WAITING;
                } elseif ($reminderDue) {
                    $decision['alert'] = $group === self::GROUP_PROCESSING ? self::ALERT_PROCESSING_REMINDER : self::ALERT_WAITING_REMINDER;
                }
                break;
            case self::GROUP_ERROR:
                if ($autoRetryPending && !empty($cfg['kickQueue'])) {
                    break; // requeueTransientErrors() will try again; do not report it yet
                }
                $reminderAfter = $cfg['errorReminderMinutes'];
                if ($sent === 0) {
                    $decision['alert'] = ($reminderAfter > 0 && $minutes >= $reminderAfter) ? self::ALERT_ERROR_REMINDER : self::ALERT_ERROR;
                } elseif ($reminderDue && $reminderAfter > 0 && $minutes >= $reminderAfter) {
                    $decision['alert'] = self::ALERT_ERROR_REMINDER;
                }
                break;
        }
        return $decision;
    }

    /**
     * Whether the processes recorded for a job are gone. Returns null when it cannot be known:
     * no /proc (non-Linux), no recorded PID, or a state that has no worker.
     */
    public static function isWorkerGone(array $job, $pidRunning = null)
    {
        if (self::getGroup($job['status']) !== self::GROUP_PROCESSING) {
            return null;
        }
        if ($pidRunning === null) {
            if (!is_dir('/proc/self')) {
                return null;
            }
            $pidRunning = ['EncoderMonitor', 'isEncoderProcess'];
        }
        $pids = array_filter([intval($job['worker_ppid']), intval($job['worker_pid'])]);
        if (empty($pids)) {
            return null;
        }
        foreach ($pids as $pid) {
            if (call_user_func($pidRunning, $pid)) {
                return false;
            }
        }
        return true;
    }

    // After a container restart PIDs start again from 1, so a recorded PID can now belong to an
    // unrelated process (cron, sh). HTTP workers must count too: setStatus() records the
    // Apache/mod_php or PHP-FPM PID when run.php is invoked over HTTP. Prefer keeping a job
    // with a reused web worker PID over requeueing a conversion that is still running.
    public static function isEncoderProcess($pid)
    {
        if (!isPIDRunning($pid)) {
            return false;
        }
        $cmdline = @file_get_contents("/proc/{$pid}/cmdline");
        if ($cmdline === false || $cmdline === '') {
            return true; // cannot tell (zombie or no permission): assume it is still working
        }
        return self::isWorkerCommand($cmdline);
    }

    public static function isWorkerCommand($cmdline)
    {
        $command = str_replace("\0", ' ', (string) $cmdline);
        return preg_match('/php|ffmpeg|ffprobe|yt-dlp|youtube-dl|python/i', $command) === 1
            || preg_match('/^(?:\S*\/)?(?:apache2|httpd)(?:\s|$)/i', $command) === 1;
    }

    /**
     * @return array ['dead_since' => string|null, 'recover' => bool]
     */
    public static function decideDeadWorker(array $job, array $row, $gone, $now, array $cfg)
    {
        if (empty($cfg['recoverDeadWorkers']) || $gone !== true) {
            return ['dead_since' => null, 'recover' => false];
        }
        if (empty($row['dead_since'])) {
            return ['dead_since' => $now, 'recover' => false];
        }
        $grace = $cfg['deadWorkerGraceMinutes'];
        $recover = self::minutesBetween($row['dead_since'], $now) >= $grace
            && self::minutesBetween($job['modified'], $now) >= $grace;
        return ['dead_since' => $row['dead_since'], 'recover' => $recover];
    }

    // The raw status_obs can hold server paths and FFmpeg commands; owners only get a category.
    public static function getErrorReason($status_obs)
    {
        $msg = strtolower((string) $status_obs);
        if (strpos($msg, strtolower(self::WORKER_STOPPED_MSG)) !== false) {
            return self::REASON_WORKER;
        }
        if (strpos($msg, 'could not get videos_id') !== false) {
            return self::REASON_NO_VIDEO;
        }
        // Encoder::getTransferErrorMessage() always ends with one of these markers.
        foreach (['recheck', 'output files were kept', 'sendtostreamer', 'send message error'] as $needle) {
            if (strpos($msg, $needle) !== false) {
                return self::REASON_TRANSFER;
            }
        }
        if (strpos($msg, 'download') !== false) {
            return self::REASON_DOWNLOAD;
        }
        foreach (['transfer', 'upload'] as $needle) {
            if (strpos($msg, $needle) !== false) {
                return self::REASON_TRANSFER;
            }
        }
        foreach (['execute code', 'ffmpeg', 'encod', 'convert', 'hls'] as $needle) {
            if (strpos($msg, $needle) !== false) {
                return self::REASON_CONVERSION;
            }
        }
        return self::REASON_UNKNOWN;
    }

    /**
     * @return array list of system check names that need an alert now
     */
    public static function decideSystemChecks(array $stats, array $cfg)
    {
        $checks = [];
        if (!empty($stats['diskTotal'])) {
            $percent = $stats['diskFree'] * 100 / $stats['diskTotal'];
            $lowPercent = $cfg['diskMinFreePercent'] > 0 && $percent < $cfg['diskMinFreePercent'];
            $lowBytes = $cfg['diskMinFreeGB'] > 0 && $stats['diskFree'] < $cfg['diskMinFreeGB'] * 1073741824;
            if ($lowPercent || $lowBytes) {
                $checks[] = self::SYSTEM_DISK_LOW;
            }
        }
        if ($cfg['queueStalledMinutes'] > 0 && $stats['waiting'] > 0 && $stats['processing'] === 0
            && $stats['oldestWaitingMinutes'] >= $cfg['queueStalledMinutes']) {
            $checks[] = self::SYSTEM_QUEUE_STALLED;
        }
        if ($cfg['errorSpikeCount'] > 0 && $stats['errorsLastHour'] >= $cfg['errorSpikeCount']) {
            $checks[] = self::SYSTEM_ERROR_SPIKE;
        }
        return $checks;
    }

    public static function run()
    {
        $startedAt = microtime(true);
        $cfg = self::getConfig();
        $deadline = $startedAt + $cfg['maxRunSeconds'];
        $summary = [
            'jobs' => 0, 'alertsSent' => 0, 'alertsSkipped' => 0, 'alertsFailed' => 0, 'alertsDeferred' => 0,
            'requeuedTransient' => 0, 'workersRequeued' => 0, 'workersFailed' => 0,
            'queueStarted' => false, 'systemAlerts' => [],
            'errors' => 0,
        ];
        if (!self::tablesExist()) {
            _error_log('EncoderMonitor: tables are missing, run the Encoder database update (v8.3)');
            $summary['error'] = 'database update 8.3 pending';
            return $summary;
        }
        $now = self::getDatabaseNow();
        // Heartbeat first: slow Streamer calls below must not make the admin page report a missing cron.
        self::setState(self::STATE_HEARTBEAT, json_encode(['time' => $now, 'summary' => null]));

        if (!empty($cfg['kickQueue']) && self::isDue('transient_requeue_at', $now, 5)) {
            // It loads every error row, and its own backoff is at least 5 minutes anyway.
            $summary['requeuedTransient'] = intval(Encoder::requeueTransientErrors());
            self::setState('transient_requeue_at', $now);
        }

        $jobs = self::getJobs($cfg);
        $summary['jobs'] = count($jobs);
        $superseded = self::getSupersededJobIds($jobs);
        $stats = ['waiting' => 0, 'processing' => 0, 'oldestWaitingMinutes' => 0, 'errorsLastHour' => 0, 'lastError' => ''];
        $position = 0;
        $pending = [];

        // 1. Track every job and recover dead workers. No Streamer e-mail is sent here.
        foreach ($jobs as $job) {
            try {
                $previous = empty($job['state']) ? null : $job;
                $autoRetry = $job['status'] === Encoder::STATUS_ERROR && Encoder::isAutoRetryPending($job['status_obs'], $job['retry_count']);
                $decision = self::decideJob($job, $previous, $now, $cfg, $autoRetry);
                $row = $decision['monitor'];
                $group = $row['state'];

                if ($group === self::GROUP_WAITING) {
                    $position++;
                    $stats['waiting']++;
                    $stats['oldestWaitingMinutes'] = max($stats['oldestWaitingMinutes'], $decision['minutes']);
                } elseif ($group === self::GROUP_PROCESSING) {
                    $stats['processing']++;
                } elseif ($decision['minutes'] <= 60) {
                    $stats['errorsLastHour']++;
                    $stats['lastError'] = (string) $job['status_obs'];
                }

                $dead = self::decideDeadWorker($job, $row, self::isWorkerGone($job), $now, $cfg);
                $row['dead_since'] = $dead['dead_since'];
                if ($dead['recover']) {
                    $result = self::recoverDeadWorker($job);
                    if ($result === 'requeued') {
                        $summary['workersRequeued']++;
                    } elseif ($result === 'failed') {
                        $summary['workersFailed']++;
                    }
                    continue; // next run sees the new status
                }

                if (!empty($decision['alert']) && !empty($cfg['ownerAlerts'])) {
                    if (isset($superseded[intval($job['id'])])) {
                        // The video was sent again; only the newest job speaks for it.
                        $row = self::markAlertHandled($row, $decision['alert'], $now, 'skipped: a newer job exists for this video');
                        $summary['alertsSkipped']++;
                    } else {
                        $pending[] = ['job' => $job, 'row' => $row, 'decision' => $decision, 'position' => $position, 'previous' => $previous];
                        continue;
                    }
                }
                self::saveMonitorRow($row, $previous);
            } catch (\Throwable $th) {
                $summary['errors']++;
                _error_log('EncoderMonitor: job ' . intval($job['id']) . ' ' . $th->getMessage());
            }
        }

        self::deleteFinishedRows();

        // 2. Keep the queue moving before any slow network call.
        if (!empty($cfg['kickQueue']) && $stats['waiting'] > 0 && Encoder::canEncodeNow()) {
            execRun();
            $summary['queueStarted'] = true;
        }

        // 3. Owner alerts. Each Streamer call can block up to the cURL timeout, so stop at the run
        // budget and pause a Streamer that did not answer, instead of stalling every other one.
        $budget = $cfg['maxAlertsPerRun'];
        $paused = [];
        $issues = [];
        foreach ($pending as $item) {
            $row = $item['row'];
            try {
                $streamers_id = intval($item['job']['streamers_id']);
                $outOfTime = microtime(true) >= $deadline;
                if ($budget <= 0 || $outOfTime || self::isStreamerPaused($streamers_id, $now, $cfg, $paused)) {
                    $summary['alertsDeferred']++; // still due, the next run tries again
                } else {
                    $budget--;
                    $response = null;
                    $row = self::sendOwnerAlert($item['job'], $row, $item['decision'], $now, $cfg, $item['position'], $response, $deadline);
                    $result = (string) $row['last_result'];
                    if (strpos($result, 'deferred') === 0) {
                        // The job changed or was deleted since the scan: nothing was sent and the
                        // saved row would describe a stale state. The next scan decides again.
                        $budget++;
                        $summary['alertsDeferred']++;
                        continue;
                    }
                    if ($response !== null) {
                        $issues[$streamers_id] = self::classifyStreamerIssue($response);
                    }
                    if ($result === 'sent') {
                        $summary['alertsSent']++;
                    } elseif (strpos($result, 'failed') === 0) {
                        $summary['alertsFailed']++;
                        if (self::isConnectionFailure($response)) {
                            self::pauseStreamer($streamers_id, $now, $paused);
                        }
                    } else {
                        $summary['alertsSkipped']++;
                    }
                }
                self::saveMonitorRow($row, $item['previous']);
            } catch (\Throwable $th) {
                $summary['errors']++;
                _error_log('EncoderMonitor: alert for job ' . intval($item['job']['id']) . ' ' . $th->getMessage());
            }
        }

        if (!empty($cfg['adminAlerts']) && microtime(true) < $deadline) {
            $summary['systemAlerts'] = self::runSystemChecks($stats, $now, $cfg, $paused, $issues, $deadline);
        }

        foreach ($issues as $streamers_id => $issue) {
            self::setStreamerIssue($streamers_id, $issue);
        }
        $summary['streamerIssues'] = array_filter($issues);
        if ($summary['errors'] > 0) {
            $summary['error'] = 'Monitor could not process ' . $summary['errors'] . ' job or alert operations. See the Encoder log.';
            self::recordCronError($summary['error']);
        } else {
            self::deleteState(self::STATE_LAST_ERROR);
        }
        self::setState(self::STATE_HEARTBEAT, json_encode(['time' => $now, 'summary' => $summary]));
        return $summary;
    }

    /**
     * Why a Streamer did not accept an alert, '' when it did. Shown to the Encoder admin because
     * each Streamer can run a different AVideo version or be offline.
     */
    public static function classifyStreamerIssue($response)
    {
        if (self::isConnectionFailure($response)) {
            return self::ISSUE_UNREACHABLE;
        }
        if (intval(@$response->http_code) === 404) {
            return self::ISSUE_NO_ENDPOINT;
        }
        if (!self::isAlertAccepted($response)) {
            return self::ISSUE_REJECTED;
        }
        return '';
    }

    /**
     * Records why install/cron.php could not run, for the admin page. Never throws.
     */
    public static function recordCronError($message)
    {
        try {
            if (self::tablesExist()) {
                self::setState(self::STATE_LAST_ERROR, json_encode(['message' => substr((string) $message, 0, 500)]));
            }
        } catch (\Throwable $th) {
            _error_log('EncoderMonitor: could not record the cron error: ' . $th->getMessage());
        }
    }

    /**
     * Everything the admin page needs to tell whether the cron runs correctly.
     */
    public static function getStatusReport()
    {
        $report = ['tablesMissing' => !self::tablesExist(), 'heartbeatAge' => -1, 'summary' => null, 'lastError' => null, 'streamerIssues' => []];
        if ($report['tablesMissing']) {
            return $report;
        }
        $res = self::query("SELECT name, value, TIMESTAMPDIFF(MINUTE, modified, NOW()) AS age FROM " . self::stateTable()
            . " WHERE name IN ('" . self::STATE_HEARTBEAT . "', '" . self::STATE_LAST_ERROR . "') OR name LIKE 'streamer\\_issue\\_%'");
        while ($row = $res->fetch_assoc()) {
            $value = json_decode((string) $row['value'], true);
            $age = intval($row['age']);
            if ($row['name'] === self::STATE_HEARTBEAT) {
                $report['heartbeatAge'] = $age;
                $report['summary'] = is_array($value) && isset($value['summary']) ? $value['summary'] : null;
            } elseif ($row['name'] === self::STATE_LAST_ERROR) {
                $report['lastError'] = ['message' => is_array($value) ? (string) @$value['message'] : '', 'age' => $age];
            } elseif ($age <= 1440 && is_array($value) && !empty($value['issue'])) {
                $report['streamerIssues'][] = [
                    'streamers_id' => intval(substr($row['name'], strlen(self::STATE_STREAMER_ISSUE))),
                    'issue' => (string) $value['issue'],
                    'age' => $age,
                ];
            }
        }
        return $report;
    }

    /**
     * Jobs that are not the newest job of their video on the same Streamer. The Streamer queues a
     * new job when a video is sent again, and the old failed row stays in the queue.
     *
     * @return array job id => newer job id
     */
    public static function getSupersededJobIds(array $jobs)
    {
        $newest = [];
        $byJob = [];
        foreach ($jobs as $job) {
            $vars = json_decode((string) $job['return_vars']);
            if (!is_object($vars) || empty($vars->videos_id)) {
                continue;
            }
            $key = intval($job['streamers_id']) . ':' . intval($vars->videos_id);
            $id = intval($job['id']);
            $byJob[$id] = $key;
            if (empty($newest[$key]) || $id > $newest[$key]) {
                $newest[$key] = $id;
            }
        }
        $superseded = [];
        foreach ($byJob as $id => $key) {
            if ($newest[$key] !== $id) {
                $superseded[$id] = $newest[$key];
            }
        }
        return $superseded;
    }

    public static function isConnectionFailure($response)
    {
        return !is_object($response) || !empty($response->curl_errno) || empty($response->http_code);
    }

    private static function isAlertAccepted($response)
    {
        return !self::isConnectionFailure($response)
            && intval($response->http_code) >= 200 && intval($response->http_code) < 300
            && isset($response->response) && is_object($response->response)
            && isset($response->response->error) && $response->response->error === false;
    }

    private static function isStreamerPaused($streamers_id, $now, array $cfg, array &$paused)
    {
        if (!isset($paused[$streamers_id])) {
            $paused[$streamers_id] = !self::isDue('streamer_unreachable_' . $streamers_id, $now, $cfg['failedSendRetryMinutes']);
        }
        return $paused[$streamers_id];
    }

    private static function pauseStreamer($streamers_id, $now, array &$paused)
    {
        _error_log("EncoderMonitor: streamer {$streamers_id} did not answer, pausing its alerts");
        self::setState('streamer_unreachable_' . $streamers_id, $now);
        $paused[$streamers_id] = true;
    }

    private static function isDue($stateName, $now, $minutes)
    {
        $last = self::getState($stateName);
        return empty($last) || self::minutesBetween($last, $now) >= $minutes;
    }

    private static function recoverDeadWorker(array $job)
    {
        $encoder = new Encoder($job['id']);
        // Act only if nothing changed since the job was read.
        if ($encoder->getStatus() !== $job['status']
            || intval($encoder->getWorker_ppid()) !== intval($job['worker_ppid'])
            || intval($encoder->getWorker_pid()) !== intval($job['worker_pid'])
            || self::isWorkerGone($job) !== true) {
            return 'changed';
        }
        $retry = intval($encoder->getRetry_count());
        if ($retry < Encoder::MAX_AUTO_RETRIES) {
            $attempt = $retry + 1;
            _error_log("EncoderMonitor: worker for job {$job['id']} is gone (status {$job['status']}), requeue attempt {$attempt}/" . Encoder::MAX_AUTO_RETRIES);
            $encoder->setStatus(Encoder::STATUS_QUEUE);
            $encoder->setStatus_obs(self::WORKER_STOPPED_MSG . "; requeued automatically (attempt {$attempt}/" . Encoder::MAX_AUTO_RETRIES . ")");
            // Same counter and cap as requeueTransientErrors(), so a job that keeps crashing stops.
            $encoder->setRetry_count($attempt);
            $encoder->save();
            return 'requeued';
        }
        _error_log("EncoderMonitor: worker for job {$job['id']} is gone and the retry limit was reached");
        if (in_array($job['status'], [Encoder::STATUS_PACKING, Encoder::STATUS_TRANSFERRING], true)) {
            // Same outcome as a failed Recheck (Encoder::resumeOutputTransfer()): the encoded files
            // stay on disk, so an admin can Recheck instead of the owner uploading again.
            $encoder->setStatus(Encoder::STATUS_ERROR, false);
            $encoder->setStatus_obs('Transfer worker stopped unexpectedly. Output files were kept; use Recheck to retry.', false);
            $encoder->save();
            return 'failed';
        }
        Encoder::setStatusError($job['id'], self::WORKER_STOPPED_MSG . ' and the automatic retry limit was reached. Please send the video again.', true);
        return 'failed';
    }

    private static function sendOwnerAlert(array $job, array $row, array $decision, $now, array $cfg, $position, &$response, $deadline)
    {
        $encoder = new Encoder($job['id']);
        if ($encoder->getStatus() !== $job['status']) {
            // execRun() or another worker may have resumed/completed this job since the scan.
            // Leave the alert due so the next scan can decide from the current state.
            $row['last_result'] = 'deferred: job changed before delivery';
            return $row;
        }
        $returnVars = json_decode((string) $job['return_vars']);
        $row['last_attempt_at'] = $now;
        if (!is_object($returnVars) || empty($returnVars->videos_id)) {
            // The Streamer has no video for this job yet, so there is no owner to notify.
            return self::markAlertHandled($row, $decision['alert'], $now, 'skipped: no videos_id');
        }
        $fields = [
            'type' => $decision['alert'],
            'videos_id' => intval($returnVars->videos_id),
            'queue_id' => intval($job['id']),
            'queue_status' => $job['status'],
            'minutes' => $decision['minutes'],
            'queue_position' => $row['state'] === self::GROUP_WAITING ? $position : 0,
            'reason' => $row['state'] === self::GROUP_ERROR ? self::getErrorReason($job['status_obs']) : '',
            'retention_days' => $cfg['retentionDays'],
        ];
        $response = Encoder::sendToStreamerWithDeadline(self::STREAMER_ENDPOINT, $fields, $returnVars, $encoder, $deadline);
        return self::applySendResult($row, $decision['alert'], $now, $response);
    }

    private static function applySendResult(array $row, $alert, $now, $response)
    {
        $body = (is_object($response) && isset($response->response) && is_object($response->response)) ? $response->response : null;
        $msg = is_object($response) && !empty($response->msg) ? (string) $response->msg : '';
        if (self::isAlertAccepted($response)) {
            return self::markAlertHandled($row, $alert, $now, empty($body->skipped) ? 'sent' : 'skipped: ' . $msg);
        }
        if (is_object($response) && intval(@$response->http_code) === 404) {
            // Older Streamer without the alert endpoint: try again at the next reminder only.
            return self::markAlertHandled($row, $alert, $now, 'skipped: streamer has no alert endpoint (update the streamer)');
        }
        if (!empty($body->permanent)) {
            return self::markAlertHandled($row, $alert, $now, 'skipped: ' . $msg);
        }
        $row['last_result'] = substr('failed: ' . $msg, 0, 255);
        _error_log('EncoderMonitor: alert ' . $alert . ' for job ' . intval($row['encoder_queue_id']) . ' was not accepted: ' . $msg);
        return $row;
    }

    private static function markAlertHandled(array $row, $alert, $now, $result)
    {
        $row['alerts_sent'] = intval($row['alerts_sent']) + 1;
        $row['last_alert_type'] = $alert;
        $row['last_alert_at'] = $now;
        $row['last_attempt_at'] = $now;
        $row['last_result'] = substr($result, 0, 255);
        return $row;
    }

    private static function runSystemChecks(array $stats, $now, array $cfg, array &$paused, array &$issues, $deadline)
    {
        global $global;
        $dir = $global['systemRootPath'] . 'videos/';
        $free = @disk_free_space($dir);
        $total = @disk_total_space($dir);
        $stats['diskFree'] = $free === false ? 0 : $free;
        $stats['diskTotal'] = $total === false ? 0 : $total;

        $sent = [];
        foreach (self::decideSystemChecks($stats, $cfg) as $check) {
            $stateName = 'system_alert_' . $check;
            $last = self::getState($stateName);
            if (!empty($last) && self::minutesBetween($last, $now) < $cfg['systemAlertIntervalMinutes']) {
                continue;
            }
            $fields = [
                'type' => 'system',
                'check' => $check,
                'disk_free' => intval($stats['diskFree']),
                'disk_total' => intval($stats['diskTotal']),
                'waiting' => $stats['waiting'],
                'processing' => $stats['processing'],
                'oldest_waiting_minutes' => $stats['oldestWaitingMinutes'],
                'errors_last_hour' => $stats['errorsLastHour'],
                'last_error' => substr(strip_tags($stats['lastError']), 0, 200),
                'encoder_url' => $global['webSiteRootURL'],
            ];
            $delivered = false;
            $attempted = false;
            foreach (self::getAdminStreamerIds() as $streamers_id) {
                if (microtime(true) >= $deadline) {
                    if ($delivered) {
                        $sent[] = $check;
                    }
                    return $sent;
                }
                $streamerStateName = $stateName . '_' . $streamers_id;
                if (!self::isDue($streamerStateName, $now, $cfg['systemAlertIntervalMinutes'])
                    || self::isStreamerPaused($streamers_id, $now, $cfg, $paused)) {
                    continue;
                }
                // Unsaved job object, used only so sendToStreamer() picks this Streamer's URL and
                // credentials. The alert endpoint never returns videos_id, so nothing is saved.
                $encoder = new Encoder('');
                $encoder->setStreamers_id($streamers_id);
                $encoder->setReturn_vars('{}');
                $attempted = true;
                $response = Encoder::sendToStreamerWithDeadline(self::STREAMER_ENDPOINT, $fields, new stdClass(), $encoder, $deadline);
                // Per-site throttling lets an interrupted run continue with the other admins.
                self::setState($streamerStateName, $now);
                $issues[$streamers_id] = self::classifyStreamerIssue($response);
                if (self::isAlertAccepted($response)) {
                    $delivered = true;
                } else {
                    _error_log("EncoderMonitor: system alert {$check} was not accepted by streamer {$streamers_id}: " . (is_object($response) ? @$response->msg : ''));
                    if (self::isConnectionFailure($response)) {
                        self::pauseStreamer($streamers_id, $now, $paused);
                    }
                }
            }
            if ($attempted) {
                _error_log("EncoderMonitor: system alert {$check} " . json_encode($fields));
            }
            // The legacy global timestamp is read above for existing installations; new
            // attempts are throttled per Streamer, including refused or unreachable sites.
            if ($delivered) {
                $sent[] = $check;
            }
        }
        return $sent;
    }

    private static function getAdminStreamerIds()
    {
        $ids = [];
        $res = self::query("SELECT id FROM " . Streamer::getTableName() . " WHERE isAdmin = 1 ORDER BY id ASC");
        while ($res && ($row = $res->fetch_assoc())) {
            $ids[] = intval($row['id']);
        }
        return $ids;
    }

    private static function monitorTable()
    {
        global $global;
        return $global['tablesPrefix'] . 'encoder_queue_monitor';
    }

    private static function stateTable()
    {
        global $global;
        return $global['tablesPrefix'] . 'encoder_monitor_state';
    }

    private static function query($sql)
    {
        global $global;
        $res = $global['mysqli']->query($sql);
        if ($res === false) {
            throw new RuntimeException('EncoderMonitor query failed: (' . $global['mysqli']->errno . ') ' . $global['mysqli']->error);
        }
        return $res;
    }

    private static function execute($sql, $types, array $params)
    {
        global $global;
        $stmt = $global['mysqli']->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException('EncoderMonitor prepare failed: (' . $global['mysqli']->errno . ') ' . $global['mysqli']->error);
        }
        try {
            $stmt->bind_param($types, ...$params);
            if (!$stmt->execute()) {
                throw new RuntimeException('EncoderMonitor execute failed: (' . $stmt->errno . ') ' . $stmt->error);
            }
            return true;
        } finally {
            $stmt->close();
        }
    }

    private static function tablesExist()
    {
        global $global;
        static $exists = null;
        if ($exists !== null) {
            return $exists;
        }
        $stmt = $global['mysqli']->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN (?, ?)');
        $monitor = self::monitorTable();
        $state = self::stateTable();
        $stmt->bind_param('ss', $monitor, $state);
        $stmt->execute();
        $count = 0;
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
        $exists = intval($count) === 2;
        return $exists;
    }

    private static function getDatabaseNow()
    {
        $row = self::query('SELECT NOW() AS now')->fetch_assoc();
        return $row['now'];
    }

    private static function getJobs(array $cfg)
    {
        $active = array_diff(self::getMonitoredStatuses(), [Encoder::STATUS_ERROR]);
        $in = "'" . implode("','", $active) . "'";
        $days = max(1, intval($cfg['retentionDays']));
        $sql = "SELECT q.id, q.status, q.status_obs, q.streamers_id, q.return_vars, q.worker_pid, q.worker_ppid,"
            . " q.retry_count, q.priority, q.created, q.modified,"
            . " m.state, m.state_since, m.alerts_sent, m.last_alert_type, m.last_alert_at, m.last_attempt_at,"
            . " m.last_result, m.dead_since"
            . " FROM " . Encoder::getTableName() . " q"
            . " LEFT JOIN " . self::monitorTable() . " m ON m.encoder_queue_id = q.id"
            . " WHERE q.status IN ({$in})"
            . " OR (q.status = '" . Encoder::STATUS_ERROR . "' AND q.modified > NOW() - INTERVAL {$days} DAY)"
            // Same order as Encoder::getNext(), so the position counts the jobs ahead.
            . " ORDER BY q.priority ASC, q.id ASC";
        $res = self::query($sql);
        $rows = [];
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }

    private static function saveMonitorRow(array $row, $previous)
    {
        if (is_array($previous)) {
            $changed = false;
            foreach (self::getMonitorFields() as $field) {
                if ((string) $row[$field] !== (string) $previous[$field]) {
                    $changed = true;
                    break;
                }
            }
            if (!$changed) {
                return true;
            }
        }
        $sql = "INSERT INTO " . self::monitorTable()
            . " (encoder_queue_id, state, state_since, alerts_sent, last_alert_type, last_alert_at, last_attempt_at, last_result, dead_since, modified)"
            . " VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
            . " ON DUPLICATE KEY UPDATE state = VALUES(state), state_since = VALUES(state_since), alerts_sent = VALUES(alerts_sent),"
            . " last_alert_type = VALUES(last_alert_type), last_alert_at = VALUES(last_alert_at), last_attempt_at = VALUES(last_attempt_at),"
            . " last_result = VALUES(last_result), dead_since = VALUES(dead_since), modified = NOW()";
        $id = intval($row['encoder_queue_id']);
        $alertsSent = intval($row['alerts_sent']);
        try {
            return self::execute($sql, 'ississsss', [
                $id, $row['state'], $row['state_since'], $alertsSent, $row['last_alert_type'],
                $row['last_alert_at'], $row['last_attempt_at'], $row['last_result'], $row['dead_since'],
            ]);
        } catch (\Throwable $th) {
            // A finished job is deleted by autodelete (the default) at any moment; the foreign key
            // then refuses the row. That is normal, not a monitor failure for the admin page.
            if (!self::jobExists($id)) {
                return false;
            }
            throw $th;
        }
    }

    private static function jobExists($id)
    {
        $res = self::query('SELECT id FROM ' . Encoder::getTableName() . ' WHERE id = ' . intval($id) . ' LIMIT 1');
        return (bool) $res->fetch_assoc();
    }

    private static function deleteFinishedRows()
    {
        $in = "'" . implode("','", self::getMonitoredStatuses()) . "'";
        self::query("DELETE m FROM " . self::monitorTable() . " m JOIN " . Encoder::getTableName() . " q ON q.id = m.encoder_queue_id WHERE q.status NOT IN ({$in})");
    }

    private static function getState($name)
    {
        global $global;
        $stmt = $global['mysqli']->prepare('SELECT value FROM ' . self::stateTable() . ' WHERE name = ? LIMIT 1');
        $stmt->bind_param('s', $name);
        $stmt->execute();
        $value = null;
        $stmt->bind_result($value);
        $found = $stmt->fetch();
        $stmt->close();
        return $found ? $value : null;
    }

    private static function deleteState($name)
    {
        return self::execute('DELETE FROM ' . self::stateTable() . ' WHERE name = ?', 's', [$name]);
    }

    private static function setStreamerIssue($streamers_id, $issue)
    {
        $name = self::STATE_STREAMER_ISSUE . intval($streamers_id);
        if (empty($issue)) {
            return self::deleteState($name);
        }
        return self::setState($name, json_encode(['issue' => $issue]));
    }

    private static function setState($name, $value)
    {
        $sql = 'INSERT INTO ' . self::stateTable() . ' (name, value, modified) VALUES (?, ?, NOW())'
            . ' ON DUPLICATE KEY UPDATE value = VALUES(value), modified = NOW()';
        return self::execute($sql, 'ss', [$name, $value]);
    }
}
