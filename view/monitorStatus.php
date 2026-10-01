<?php
// Admin-only health notice for the monitor cron (install/cron.php): not installed, stopped,
// failing, or unable to reach some of the AVideo sites this Encoder serves.
if (!Login::isAdmin()) {
    return;
}
require_once $global['systemRootPath'] . 'objects/EncoderMonitor.php';
try {
    $monitorReport = EncoderMonitor::getStatusReport();
} catch (\Throwable $th) {
    _error_log('monitorStatus: ' . $th->getMessage());
    return;
}
$monitorConfig = EncoderMonitor::getConfig();
$monitorAge = $monitorReport['heartbeatAge'];
$monitorStopped = !$monitorReport['tablesMissing'] && ($monitorAge < 0 || $monitorAge >= $monitorConfig['heartbeatStaleMinutes']);

$monitorInstall = 'sudo ' . getPHP() . ' ' . $global['systemRootPath'] . 'install/installCron.php';
$monitorIssueTexts = [
    EncoderMonitor::ISSUE_UNREACHABLE => __('did not answer. Its alerts are paused for an hour.'),
    EncoderMonitor::ISSUE_NO_ENDPOINT => __('does not receive encoder alerts yet. Update AVideo on that site.'),
    EncoderMonitor::ISSUE_REJECTED => __('refused the alert. Check the encoder account and its permissions on that site.'),
];
?>
<?php if ($monitorReport['tablesMissing']) { ?>
    <div class="alert alert-warning" id="encoderMonitorStatus">
        <strong><i class="fas fa-heartbeat" aria-hidden="true"></i> <?php echo __('Encoder monitor'); ?>:</strong>
        <?php echo __('Run the pending database update in the Update tab to enable stuck and failed video alerts.'); ?>
        <a href="#monitor" data-monitor-open class="alert-link monitor-open-link"><?php echo __('Open the monitor'); ?> <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
    </div>
<?php } ?>
<?php if ($monitorStopped || !empty($monitorReport['lastError'])) { ?>
    <div class="alert alert-danger" id="encoderMonitorStatus">
        <strong><i class="fas fa-heartbeat" aria-hidden="true"></i> <?php echo __('Encoder monitor'); ?>:</strong>
        <?php if ($monitorStopped && $monitorAge < 0) { ?>
            <?php echo __('The encoder cron is not installed.'); ?>
        <?php } elseif ($monitorStopped) { ?>
            <?php printf(__('The encoder cron has not run for %s minutes.'), intval($monitorAge)); ?>
        <?php } ?>
        <?php if (!empty($monitorReport['lastError'])) { ?>
            <?php printf(__('Its last run failed %s minutes ago:'), intval($monitorReport['lastError']['age'])); ?>
            <code><?php echo htmlspecialchars($monitorReport['lastError']['message'], ENT_QUOTES, 'UTF-8'); ?></code>
        <?php } ?>
        <?php echo __('Stuck or failed videos are not detected, and their owners on every AVideo site served by this encoder are not notified.'); ?>
        <?php if ($monitorStopped) { ?>
            <hr style="margin: 10px 0;">
            <?php echo __('Run this once on the server as root. In Docker, run it inside the encoder container:'); ?>
            <pre style="margin: 5px 0; white-space: pre-wrap;"><?php echo htmlspecialchars($monitorInstall, ENT_QUOTES, 'UTF-8'); ?></pre>
            <?php echo __('If it is already installed, check that the cron service is running and that it runs as the web server user, not root.'); ?>
        <?php } ?>
        <a href="#monitor" data-monitor-open class="alert-link monitor-open-link"><?php echo __('Open the monitor'); ?> <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
    </div>
<?php } ?>
<?php if (!empty($monitorReport['streamerIssues'])) { ?>
    <div class="alert alert-warning">
        <strong><i class="fas fa-envelope" aria-hidden="true"></i> <?php echo __('Encoder alerts'); ?>:</strong>
        <?php echo __('These AVideo sites did not accept the alerts for their video owners in the last 24 hours.'); ?>
        <ul style="margin: 5px 0 0;">
            <?php foreach ($monitorReport['streamerIssues'] as $monitorIssue) {
                $monitorStreamer = new Streamer($monitorIssue['streamers_id']);
                $monitorSite = $monitorStreamer->getSiteURL();
                if (empty($monitorSite)) {
                    $monitorSite = 'Streamer #' . intval($monitorIssue['streamers_id']);
                }
                $monitorText = isset($monitorIssueTexts[$monitorIssue['issue']]) ? $monitorIssueTexts[$monitorIssue['issue']] : $monitorIssueTexts[EncoderMonitor::ISSUE_REJECTED];
                ?>
                <li><strong><?php echo htmlspecialchars($monitorSite, ENT_QUOTES, 'UTF-8'); ?></strong> <?php echo $monitorText; ?></li>
            <?php } ?>
        </ul>
        <a href="#monitor" data-monitor-open class="alert-link monitor-open-link"><?php echo __('Open the monitor'); ?> <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
    </div>
<?php } ?>
