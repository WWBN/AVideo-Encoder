<?php

// Installs the Encoder monitor cron (install/cron.php) for THIS installation folder. Several
// Encoders can share a server, so each folder gets its own entry.
//
//   sudo php install/installCron.php [--user=www-data]   writes /etc/cron.d/avideo-encoder-<hash>
//   php install/installCron.php --print                   only shows what would be installed
//
// Only root installs it. The job runs as the web server user (never root: install/cron.php is
// writable by that user), on the same host/container as the encoding workers.
// This script often runs as root (Docker entrypoint, sudo), so it never loads the Encoder
// configuration: that could create root-owned log or session files the web server cannot use.
if (php_sapi_name() !== 'cli') {
    die('Command Line only');
}
require_once dirname(__DIR__) . '/objects/EncoderCron.php';

$rootPath = str_replace('\\', '/', dirname(__DIR__)) . '/';
if (!file_exists($rootPath . 'videos/configuration.php')) {
    fwrite(STDERR, 'Install the Encoder first: ' . $rootPath . 'videos/configuration.php does not exist.' . PHP_EOL);
    exit(1);
}

$options = getopt('', ['user:', 'print']);
$php = PHP_BINARY;
$isRoot = function_exists('posix_geteuid') && posix_geteuid() === 0;

$user = isset($options['user']) ? (string) $options['user'] : '';
if ($user === '' && function_exists('posix_getpwuid')) {
    // The web server user normally owns the videos folder.
    $owner = @posix_getpwuid(@fileowner($rootPath . 'videos'));
    $user = (!empty($owner['name']) && $owner['name'] !== 'root') ? $owner['name'] : 'www-data';
}
if ($user === '') {
    $user = 'www-data';
}
if (!preg_match('/^[a-z_][a-z0-9_.-]*$/i', $user) || $user === 'root') {
    fwrite(STDERR, 'Invalid --user. Use the web server user, never root.' . PHP_EOL);
    exit(1);
}

$fileName = EncoderCron::getFileName($rootPath);
$cronFile = EncoderCron::buildFile($rootPath, $php, $user);

if (isset($options['print'])) {
    echo "/etc/cron.d/{$fileName}:" . PHP_EOL . $cronFile;
    exit(0);
}

// Security: only root installs crons. The web server user must never be able to add one, so there
// is no crontab fallback; the root-owned cron.d entry runs the job as $user.
if (!$isRoot) {
    fwrite(STDERR, "Only root can install the Encoder cron. Run: sudo {$php} {$rootPath}install/installCron.php" . PHP_EOL);
    exit(1);
}
if (!is_dir('/etc/cron.d')) {
    fwrite(STDERR, '/etc/cron.d does not exist. Install a cron service that reads /etc/cron.d and try again.' . PHP_EOL);
    exit(1);
}
$target = '/etc/cron.d/' . $fileName;
$temporary = $target . '.tmp';
if (file_put_contents($temporary, $cronFile) === false || !chmod($temporary, 0644) || !rename($temporary, $target)) {
    @unlink($temporary);
    fwrite(STDERR, 'Could not write ' . $target . PHP_EOL);
    exit(1);
}
echo "Installed {$target} (runs as {$user}):" . PHP_EOL . $cronFile;
echo 'The cron service must be running. The warning on the Encoder admin page disappears after the first run.' . PHP_EOL;
