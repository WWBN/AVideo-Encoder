<?php
// Admin Monitor tab content (see view/monitorTab.php). Also returned by
// view/monitorDashboard.refresh.php, so it renders only markup: the script lives in monitorTab.php.
if (!Login::isAdmin()) {
    return;
}
require_once $global['systemRootPath'] . 'objects/EncoderMonitor.php';
require_once $global['systemRootPath'] . 'objects/EncoderCron.php';
require_once $global['systemRootPath'] . 'objects/Streamer.php';

$tr = function ($msg) {
    return __($msg, true); // escaped once, by $esc, on output
};
$esc = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
try {
    $monitor = EncoderMonitor::getDashboardData();
} catch (\Throwable $th) {
    _error_log('monitorDashboard: ' . $th->getMessage());
    ?>
    <div id="encoderMonitorDashboard" class="alert alert-danger" data-health-tone="danger">
        <i class="fas fa-exclamation-triangle" aria-hidden="true"></i> <?php echo $esc($tr('Could not read the monitor status. Check the Encoder log.')); ?>
    </div>
    <?php
    return;
}
$cfg = $monitor['config'];
$stats = $monitor['stats'];
$report = $monitor['report'];
$summary = is_array($report['summary']) ? $report['summary'] : null;
$age = $report['heartbeatAge'];

$duration = function ($minutes) use ($tr) {
    $minutes = max(0, intval($minutes));
    if ($minutes < 1) {
        return $tr('less than a minute');
    }
    if ($minutes < 60) {
        return sprintf($tr('%d min'), $minutes);
    }
    if ($minutes < 1440) {
        return sprintf($tr('%d h %d min'), intdiv($minutes, 60), $minutes % 60);
    }
    return sprintf($tr('%d d %d h'), intdiv($minutes, 1440), intdiv($minutes % 1440, 60));
};
$bytes = function ($value) {
    return $value >= 1073741824 ? sprintf('%.1f GB', $value / 1073741824) : sprintf('%.0f MB', $value / 1048576);
};

// [tone, icon, title, description]
$healthStates = [
    'healthy' => ['success', 'fa-heartbeat', $tr('All systems operational'), sprintf($tr('The monitor ran %s ago. Stuck and failed videos are detected and their owners are notified.'), $duration($age))],
    'attention' => ['warning', 'fa-exclamation-triangle', $tr('Running, needs attention'), $tr('The monitor is running, but some of the checks below need a look.')],
    'failing' => ['danger', 'fa-bug', $tr('Last run failed'), $tr('The monitor is running, but its last run reported an error.')],
    'stopped' => ['danger', 'fa-power-off', $tr('Monitor stopped'), sprintf($tr('The monitor has not run for %s. Stuck and failed videos are not detected and nobody is notified.'), $duration($age))],
    'not_installed' => ['danger', 'fa-plug', $tr('Monitor not installed'), $tr('The monitor cron has never run on this Encoder. Install it once on the server as root.')],
    'update' => ['warning', 'fa-database', $tr('Database update pending'), $tr('Apply the pending database update in the Update tab to enable the monitor.')],
];
$health = $healthStates[$monitor['health']];
$healthy = $monitor['health'] === 'healthy';

$diskPercent = $stats['diskTotal'] > 0 ? round($stats['diskFree'] * 100 / $stats['diskTotal'], 1) : null;
$diskLow = in_array(EncoderMonitor::SYSTEM_DISK_LOW, $monitor['systemChecks'], true);
$stalled = in_array(EncoderMonitor::SYSTEM_QUEUE_STALLED, $monitor['systemChecks'], true);
$spike = in_array(EncoderMonitor::SYSTEM_ERROR_SPIKE, $monitor['systemChecks'], true);

$streamerHosts = [];
$streamerHost = function ($streamers_id) use (&$streamerHosts) {
    $streamers_id = intval($streamers_id);
    if (!isset($streamerHosts[$streamers_id])) {
        $streamer = new Streamer($streamers_id);
        $url = (string) $streamer->getSiteURL();
        $host = parse_url($url, PHP_URL_HOST);
        $streamerHosts[$streamers_id] = $host ? $host : ($url !== '' ? $url : 'Streamer #' . $streamers_id);
    }
    return $streamerHosts[$streamers_id];
};

$groupTones = [EncoderMonitor::GROUP_WAITING => 'warning', EncoderMonitor::GROUP_PROCESSING => 'info', EncoderMonitor::GROUP_ERROR => 'danger'];
$alertLabels = [
    EncoderMonitor::ALERT_WAITING => $tr('Waiting too long'),
    EncoderMonitor::ALERT_WAITING_REMINDER => $tr('Still waiting'),
    EncoderMonitor::ALERT_PROCESSING => $tr('Processing too long'),
    EncoderMonitor::ALERT_PROCESSING_REMINDER => $tr('Still processing'),
    EncoderMonitor::ALERT_ERROR => $tr('Failed'),
    EncoderMonitor::ALERT_ERROR_REMINDER => $tr('Still failed'),
];
$reasonLabels = [
    EncoderMonitor::REASON_DOWNLOAD => $tr('Download failed'),
    EncoderMonitor::REASON_CONVERSION => $tr('Conversion failed'),
    EncoderMonitor::REASON_TRANSFER => $tr('Transfer failed'),
    EncoderMonitor::REASON_WORKER => $tr('Worker stopped'),
    EncoderMonitor::REASON_NO_VIDEO => $tr('Video not found on the site'),
    EncoderMonitor::REASON_UNKNOWN => $tr('Unknown error'),
];
$issueLabels = [
    EncoderMonitor::ISSUE_UNREACHABLE => $tr('Did not answer. Its alerts are paused for an hour.'),
    EncoderMonitor::ISSUE_NO_ENDPOINT => $tr('Does not receive encoder alerts yet. Update AVideo on that site.'),
    EncoderMonitor::ISSUE_REJECTED => $tr('Refused the alert. Check the encoder account and its permissions on that site.'),
];
$systemLabels = [
    EncoderMonitor::SYSTEM_DISK_LOW => $tr('Low disk space'),
    EncoderMonitor::SYSTEM_QUEUE_STALLED => $tr('Queue stalled'),
    EncoderMonitor::SYSTEM_ERROR_SPIKE => $tr('Error spike'),
];

$webUser = 'www-data';
if (function_exists('posix_getpwuid') && function_exists('posix_geteuid')) {
    $webUserInfo = posix_getpwuid(posix_geteuid());
    if (!empty($webUserInfo['name']) && $webUserInfo['name'] !== 'root') {
        $webUser = $webUserInfo['name'];
    }
}
$cronFile = '/etc/cron.d/' . EncoderCron::getFileName($global['systemRootPath']);
$commands = [
    $tr('Install (once, as root)') => 'sudo ' . getPHP() . ' ' . $global['systemRootPath'] . 'install/installCron.php',
    $tr('Show the installed entry') => 'cat ' . $cronFile,
    $tr('Run once now, as the web server user') => 'sudo -u ' . $webUser . ' ' . getPHP() . ' ' . $global['systemRootPath'] . 'install/cron.php',
];
$settings = [
    $tr('Owner e-mails') => $cfg['ownerAlerts'] ? $tr('On') : $tr('Off'),
    $tr('Admin e-mails') => $cfg['adminAlerts'] ? $tr('On') : $tr('Off'),
    $tr('Alert when processing for') => $cfg['processingAlertMinutes'] ? $duration($cfg['processingAlertMinutes']) : $tr('Off'),
    $tr('Alert when waiting for') => $cfg['waitingAlertMinutes'] ? $duration($cfg['waitingAlertMinutes']) : $tr('Off'),
    $tr('Reminder interval') => $cfg['reminderIntervalMinutes'] ? $duration($cfg['reminderIntervalMinutes']) : $tr('Off'),
    $tr('Stop reminding after') => sprintf($tr('%d days'), $cfg['retentionDays']),
    $tr('Recover dead workers') => $cfg['recoverDeadWorkers'] ? sprintf($tr('After %s'), $duration($cfg['deadWorkerGraceMinutes'])) : $tr('Off'),
    $tr('Low disk below') => sprintf('%d%% / %d GB', $cfg['diskMinFreePercent'], $cfg['diskMinFreeGB']),
    $tr('Queue stalled after') => $cfg['queueStalledMinutes'] ? $duration($cfg['queueStalledMinutes']) : $tr('Off'),
    $tr('Error spike at') => $cfg['errorSpikeCount'] ? sprintf($tr('%d errors per hour'), $cfg['errorSpikeCount']) : $tr('Off'),
    $tr('Reported as stopped after') => $duration($cfg['heartbeatStaleMinutes']),
];
?>
<div id="encoderMonitorDashboard" class="version-dashboard version-dashboard-compact monitor-dashboard" data-health-tone="<?php echo $esc($health[0]); ?>">
    <div class="version-toolbar">
        <p class="version-eyebrow"><i class="fas fa-heartbeat" aria-hidden="true"></i> Encoder <span aria-hidden="true">/</span> <?php echo $esc($tr('Monitor')); ?></p>
        <span class="version-source text-muted">
            <i class="fas fa-sync-alt" aria-hidden="true"></i> <?php echo $esc($tr('Updates every 30 seconds')); ?>
            &middot; <?php echo $esc(date('H:i:s')); ?>
        </span>
    </div>

    <section class="panel panel-default version-hero" aria-label="<?php echo $esc($tr('Monitor status')); ?>">
        <div class="version-hero-glow text-<?php echo $esc($health[0]); ?>" aria-hidden="true"></div>
        <div class="version-hero-main">
            <div class="version-hero-copy" role="status" aria-live="polite">
                <p class="version-eyebrow text-<?php echo $esc($health[0]); ?>"><?php echo $esc($tr('Monitor status')); ?></p>
                <h2 class="version-hero-title"><?php echo $esc($health[2]); ?></h2>
                <p class="version-hero-description text-muted"><?php echo $esc($health[3]); ?></p>
                <?php if (!empty($report['lastError'])) { ?>
                    <p class="monitor-error"><i class="fas fa-bug" aria-hidden="true"></i> <code><?php echo $esc($report['lastError']['message']); ?></code></p>
                <?php } ?>
                <div class="version-actions">
                    <button type="button" class="btn btn-default" id="encoderMonitorRefresh"><i class="fas fa-sync-alt" aria-hidden="true"></i> <?php echo $esc($tr('Refresh')); ?></button>
                    <a class="btn btn-<?php echo $healthy ? 'default' : $esc($health[0]); ?>" href="https://github.com/WWBN/AVideo-Encoder/blob/master/install/README.md#monitor-cron" target="_blank" rel="noopener noreferrer"><i class="fas fa-book-open" aria-hidden="true"></i> <?php echo $esc($tr('Monitor guide')); ?></a>
                </div>
            </div>
            <div class="version-status-seal text-<?php echo $esc($health[0]); ?><?php echo $healthy ? ' monitor-pulse' : ''; ?>" aria-hidden="true">
                <div class="version-status-ring"><i class="fas <?php echo $esc($health[1]); ?>"></i></div>
            </div>
        </div>
        <div class="monitor-strip">
            <div>
                <p class="version-eyebrow text-muted"><?php echo $esc($tr('Last run')); ?></p>
                <p class="monitor-strip-value"><?php echo $esc($age < 0 ? $tr('Never') : sprintf($tr('%s ago'), $duration($age))); ?></p>
            </div>
            <div>
                <p class="version-eyebrow text-muted"><?php echo $esc($tr('Schedule')); ?></p>
                <p class="monitor-strip-value"><?php echo $esc($tr('Every minute')); ?></p>
            </div>
            <div>
                <p class="version-eyebrow text-muted"><?php echo $esc($tr('Jobs checked')); ?></p>
                <p class="monitor-strip-value"><?php echo $summary ? intval($summary['jobs']) : '&ndash;'; ?></p>
            </div>
            <div>
                <p class="version-eyebrow text-muted"><?php echo $esc($tr('Alerts sent')); ?></p>
                <p class="monitor-strip-value"><?php echo $summary ? intval($summary['alertsSent']) : '&ndash;'; ?></p>
            </div>
        </div>
    </section>

    <div class="monitor-kpis">
        <section class="panel panel-default monitor-kpi">
            <p class="version-eyebrow text-muted"><i class="fas fa-hourglass-half" aria-hidden="true"></i> <?php echo $esc($tr('Waiting')); ?></p>
            <p class="monitor-kpi-value <?php echo $stalled ? 'text-danger' : ''; ?>"><?php echo intval($stats['waiting']); ?></p>
            <p class="monitor-kpi-note text-muted">
                <?php echo $esc($stats['waiting'] ? sprintf($tr('Oldest for %s'), $duration($stats['oldestWaitingMinutes'])) : $tr('Nothing in the queue')); ?>
            </p>
            <?php if ($stalled) { ?><span class="version-pill text-danger"><?php echo $esc($tr('Queue stalled')); ?></span><?php } ?>
        </section>
        <section class="panel panel-default monitor-kpi">
            <p class="version-eyebrow text-muted"><i class="fas fa-cog" aria-hidden="true"></i> <?php echo $esc($tr('Processing')); ?></p>
            <p class="monitor-kpi-value"><?php echo intval($stats['processing']); ?></p>
            <p class="monitor-kpi-note text-muted"><?php echo $esc($tr('Downloading, encoding or transferring')); ?></p>
        </section>
        <section class="panel panel-default monitor-kpi">
            <p class="version-eyebrow text-muted"><i class="fas fa-times-circle" aria-hidden="true"></i> <?php echo $esc($tr('Errors')); ?></p>
            <p class="monitor-kpi-value <?php echo $spike ? 'text-danger' : ''; ?>"><?php echo intval($stats['errors']); ?></p>
            <p class="monitor-kpi-note text-muted"><?php echo $esc(sprintf($tr('%d in the last hour, last %d days kept'), $stats['errorsLastHour'], $cfg['retentionDays'])); ?></p>
            <?php if ($spike) { ?><span class="version-pill text-danger"><?php echo $esc($tr('Error spike')); ?></span><?php } ?>
        </section>
        <section class="panel panel-default monitor-kpi">
            <p class="version-eyebrow text-muted"><i class="fas fa-hdd" aria-hidden="true"></i> <?php echo $esc($tr('Disk free')); ?></p>
            <?php if ($diskPercent === null) { ?>
                <p class="monitor-kpi-value">&ndash;</p>
                <p class="monitor-kpi-note text-muted"><?php echo $esc($tr('Unavailable')); ?></p>
            <?php } else { ?>
                <p class="monitor-kpi-value <?php echo $diskLow ? 'text-danger' : ''; ?>"><?php echo $esc($diskPercent); ?><small>%</small></p>
                <div class="progress monitor-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo $esc(100 - $diskPercent); ?>" aria-label="<?php echo $esc($tr('Disk used')); ?>">
                    <div class="progress-bar progress-bar-<?php echo $diskLow ? 'danger' : ($diskPercent < 20 ? 'warning' : 'success'); ?>" style="width: <?php echo $esc(100 - $diskPercent); ?>%"></div>
                </div>
                <p class="monitor-kpi-note text-muted"><?php echo $esc(sprintf($tr('%s free of %s'), $bytes($stats['diskFree']), $bytes($stats['diskTotal']))); ?></p>
            <?php } ?>
        </section>
    </div>

    <div class="version-cards">
        <section class="panel panel-default version-card">
            <div class="version-card-heading">
                <h3><i class="fas fa-clipboard-check" aria-hidden="true"></i> <?php echo $esc($tr('Last run')); ?></h3>
                <?php if ($summary) { ?>
                    <span class="version-pill text-<?php echo empty($summary['error']) ? 'success' : 'danger'; ?>"><?php echo $esc(empty($summary['error']) ? $tr('Completed') : $tr('Completed with errors')); ?></span>
                <?php } ?>
            </div>
            <?php if (!$summary) { ?>
                <p class="text-muted"><?php echo $esc($tr('No completed run yet.')); ?></p>
            <?php } else { ?>
                <dl class="monitor-facts">
                    <div><dt><?php echo $esc($tr('Alerts sent')); ?></dt><dd><?php echo intval($summary['alertsSent']); ?></dd></div>
                    <div><dt><?php echo $esc($tr('Alerts not accepted')); ?></dt><dd class="<?php echo !empty($summary['alertsFailed']) ? 'text-danger' : ''; ?>"><?php echo intval($summary['alertsFailed']); ?></dd></div>
                    <div><dt><?php echo $esc($tr('Alerts postponed')); ?></dt><dd><?php echo intval($summary['alertsDeferred']); ?></dd></div>
                    <div><dt><?php echo $esc($tr('Alerts skipped')); ?></dt><dd><?php echo intval($summary['alertsSkipped']); ?></dd></div>
                    <div><dt><?php echo $esc($tr('Errors retried')); ?></dt><dd><?php echo intval($summary['requeuedTransient']); ?></dd></div>
                    <div><dt><?php echo $esc($tr('Dead workers requeued')); ?></dt><dd><?php echo intval($summary['workersRequeued']); ?></dd></div>
                    <div><dt><?php echo $esc($tr('Dead workers failed')); ?></dt><dd class="<?php echo !empty($summary['workersFailed']) ? 'text-danger' : ''; ?>"><?php echo intval($summary['workersFailed']); ?></dd></div>
                    <div><dt><?php echo $esc($tr('Queue started')); ?></dt><dd><?php echo $esc(!empty($summary['queueStarted']) ? $tr('Yes') : $tr('No')); ?></dd></div>
                </dl>
                <?php if (!empty($summary['systemAlerts'])) { ?>
                    <p class="monitor-chips">
                        <?php foreach ((array) $summary['systemAlerts'] as $systemAlert) { ?>
                            <span class="version-pill text-warning"><i class="fas fa-envelope" aria-hidden="true"></i> <?php echo $esc(isset($systemLabels[$systemAlert]) ? $systemLabels[$systemAlert] : $systemAlert); ?></span>
                        <?php } ?>
                    </p>
                <?php } ?>
            <?php } ?>
        </section>
        <section class="panel panel-default version-card">
            <div class="version-card-heading">
                <h3><i class="fas fa-globe" aria-hidden="true"></i> <?php echo $esc($tr('AVideo sites')); ?></h3>
                <span class="version-pill text-<?php echo empty($report['streamerIssues']) ? 'success' : 'warning'; ?>">
                    <?php echo $esc(empty($report['streamerIssues']) ? $tr('All accepted alerts') : sprintf($tr('%d with problems'), count($report['streamerIssues']))); ?>
                </span>
            </div>
            <?php if (empty($report['streamerIssues'])) { ?>
                <p class="text-muted"><?php echo $esc($tr('Every site this Encoder notified in the last 24 hours accepted the alerts for its video owners.')); ?></p>
            <?php } else { ?>
                <ul class="monitor-list">
                    <?php foreach ($report['streamerIssues'] as $issue) { ?>
                        <li>
                            <strong><?php echo $esc($streamerHost($issue['streamers_id'])); ?></strong>
                            <span class="text-muted"><?php echo $esc(isset($issueLabels[$issue['issue']]) ? $issueLabels[$issue['issue']] : $issueLabels[EncoderMonitor::ISSUE_REJECTED]); ?></span>
                            <small class="text-muted"><?php echo $esc(sprintf($tr('%s ago'), $duration($issue['age']))); ?></small>
                        </li>
                    <?php } ?>
                </ul>
            <?php } ?>
        </section>
    </div>

    <section class="panel panel-default version-card monitor-jobs">
        <div class="version-card-heading">
            <h3><i class="fas fa-binoculars" aria-hidden="true"></i> <?php echo $esc($tr('Jobs being watched')); ?></h3>
            <span class="version-pill text-muted"><?php echo $esc(sprintf($tr('%d jobs'), $monitor['jobsTotal'])); ?></span>
        </div>
        <?php if (empty($monitor['jobs'])) { ?>
            <div class="monitor-empty text-muted">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                <?php echo $esc($tr('No videos waiting, processing or failed recently.')); ?>
            </div>
        <?php } else { ?>
            <div class="table-responsive">
                <table class="table table-hover monitor-table">
                    <caption class="sr-only"><?php echo $esc($tr('Jobs being watched')); ?></caption>
                    <thead>
                        <tr>
                            <th scope="col"><?php echo $esc($tr('Video')); ?></th>
                            <th scope="col"><?php echo $esc($tr('Status')); ?></th>
                            <th scope="col"><?php echo $esc($tr('In this state')); ?></th>
                            <th scope="col"><?php echo $esc($tr('Owner alerts')); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($monitor['jobs'] as $job) {
                            $tone = isset($groupTones[$job['group']]) ? $groupTones[$job['group']] : 'muted';
                            $isError = $job['group'] === EncoderMonitor::GROUP_ERROR;
                            $result = (string) $job['last_result'];
                            ?>
                            <tr>
                                <td>
                                    <span class="monitor-title"><?php echo $esc($job['title'] !== null && $job['title'] !== '' ? $job['title'] : $tr('Untitled')); ?></span>
                                    <small class="text-muted">#<?php echo intval($job['id']); ?> &middot; <?php echo $esc($streamerHost($job['streamers_id'])); ?></small>
                                </td>
                                <td>
                                    <span class="version-pill text-<?php echo $esc($tone); ?>"><?php echo $esc($tr(ucfirst((string) $job['status']))); ?></span>
                                    <?php if ($isError) { ?>
                                        <small class="text-muted monitor-reason" title="<?php echo $esc($job['status_obs']); ?>"><?php echo $esc($reasonLabels[EncoderMonitor::getErrorReason($job['status_obs'])]); ?></small>
                                        <?php if (Encoder::isAutoRetryPending($job['status_obs'], $job['retry_count'])) { ?>
                                            <small class="text-info"><?php echo $esc(sprintf($tr('Retrying automatically (%d/%d)'), $job['retry_count'], Encoder::MAX_AUTO_RETRIES)); ?></small>
                                        <?php } ?>
                                    <?php } ?>
                                    <?php if (!empty($job['dead_since'])) { ?>
                                        <small class="text-danger"><?php echo $esc($tr('Worker process not found')); ?></small>
                                    <?php } ?>
                                </td>
                                <td><?php echo $esc($duration($job['minutes'])); ?></td>
                                <td>
                                    <?php if (empty($job['alerts_sent']) && $result === '') { ?>
                                        <span class="text-muted">&ndash;</span>
                                    <?php } else { ?>
                                        <span class="monitor-alert-count"><?php echo intval($job['alerts_sent']); ?></span>
                                        <?php if (!empty($job['last_alert_type'])) { ?>
                                            <small class="text-muted"><?php echo $esc(isset($alertLabels[$job['last_alert_type']]) ? $alertLabels[$job['last_alert_type']] : $job['last_alert_type']); ?></small>
                                        <?php } ?>
                                        <?php if ($result !== '') {
                                            $resultTone = $result === 'sent' ? 'success' : (strpos($result, 'failed') === 0 ? 'danger' : 'muted');
                                            $resultLabel = $result === 'sent' ? $tr('Delivered') : (strpos($result, 'failed') === 0 ? $tr('Not accepted') : $tr('Skipped'));
                                            ?>
                                            <span class="version-pill text-<?php echo $resultTone; ?>" title="<?php echo $esc($result); ?>"><?php echo $esc($resultLabel); ?></span>
                                        <?php } ?>
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <?php if ($monitor['jobsTotal'] > count($monitor['jobs'])) { ?>
                <p class="text-muted small"><?php echo $esc(sprintf($tr('Showing the %d most relevant of %d jobs. The Queue Log tab lists all of them.'), count($monitor['jobs']), $monitor['jobsTotal'])); ?></p>
            <?php } ?>
        <?php } ?>
    </section>

    <details class="version-explanation" <?php echo $healthy ? '' : 'open'; ?>>
        <summary><i class="fas fa-terminal" aria-hidden="true"></i> <?php echo $esc($tr('Installation and troubleshooting')); ?></summary>
        <p class="text-muted"><?php echo $esc($tr('The cron is installed only by root, in /etc/cron.d. It runs every minute as the web server user, never as root. In Docker, run the commands inside the encoder container.')); ?></p>
        <?php foreach ($commands as $label => $command) { ?>
            <div class="monitor-command">
                <span class="version-eyebrow text-muted"><?php echo $esc($label); ?></span>
                <div class="monitor-command-line">
                    <pre><?php echo $esc($command); ?></pre>
                    <button type="button" class="btn btn-default btn-sm" data-monitor-copy="<?php echo $esc($command); ?>" aria-label="<?php echo $esc($tr('Copy')); ?>" title="<?php echo $esc($tr('Copy')); ?>"><i class="far fa-copy" aria-hidden="true"></i></button>
                </div>
            </div>
        <?php } ?>
    </details>

    <details class="version-explanation">
        <summary><i class="fas fa-sliders-h" aria-hidden="true"></i> <?php echo $esc($tr('Active settings')); ?></summary>
        <dl class="monitor-facts monitor-settings">
            <?php foreach ($settings as $label => $value) { ?>
                <div><dt><?php echo $esc($label); ?></dt><dd><?php echo $esc($value); ?></dd></div>
            <?php } ?>
        </dl>
        <p class="text-muted"><?php echo $esc($tr('Change them in videos/configuration.php, for example:')); ?></p>
        <pre>$global['encoderMonitor'] = ['processingAlertMinutes' => 120, 'adminAlerts' => false];</pre>
    </details>
</div>
