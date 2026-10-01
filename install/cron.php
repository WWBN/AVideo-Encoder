<?php

// Encoder maintenance and alert cron (see objects/EncoderMonitor.php). It must run every minute
// as the web server user, never as root, so the files it creates stay manageable by the Encoder.
// Install it for this folder with: sudo php install/installCron.php
if (php_sapi_name() !== 'cli') {
    die('Command Line only');
}

$allowRoot = in_array('--allow-root', $argv, true);
if (!$allowRoot && function_exists('posix_geteuid') && posix_geteuid() === 0) {
    // Checked before loading the configuration: as root it could create root-owned log or
    // session files, and Encoder::run() started from here would create root-owned videos.
    // The admin page reports the cron as stopped and asks to run it as the web server user.
    fwrite(STDERR, 'install/cron.php was started as root. Run it as the web server user (sudo php install/installCron.php installs it that way).' . PHP_EOL);
    exit(1);
}

require_once dirname(__FILE__) . '/../videos/configuration.php';

if (!isCommandLineInterface()) {
    return die('Command Line only');
}

require_once $global['systemRootPath'] . 'objects/EncoderMonitor.php';

$lockFile = sys_get_temp_dir() . '/encoder_cron.' . md5($global['webSiteRootURL']) . '.lock';
$lock = fopen($lockFile, 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
    // The previous run is still sending alerts; skip this minute instead of overlapping.
    echo 'EncoderMonitor: previous run still active' . PHP_EOL;
    exit(0);
}

$exitCode = 0;
try {
    $summary = EncoderMonitor::run();
    echo json_encode($summary) . PHP_EOL;
    if (!empty($summary['error'])) {
        $exitCode = 1;
    }
} catch (\Throwable $th) {
    _error_log('EncoderMonitor: ' . $th->getMessage());
    EncoderMonitor::recordCronError('The last run stopped with an error: ' . $th->getMessage());
    fwrite(STDERR, 'EncoderMonitor failed, see the Encoder log' . PHP_EOL);
    $exitCode = 1;
}
flock($lock, LOCK_UN);
fclose($lock);
if (!empty($global['mysqli'])) {
    $global['mysqli']->close();
}
exit($exitCode);
