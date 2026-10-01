<?php

// Installs the Encoder monitor cron (install/cron.php) for THIS installation folder. Several
// Encoders can share a server, so each folder gets its own entry.
//
//   sudo php install/installCron.php [--user=www-data]   writes /etc/cron.d/avideo-encoder-<hash>
//   php install/installCron.php                           adds the line to the current user's crontab
//   php install/installCron.php --print                   only shows what would be installed
//
// The cron must run as the web server user, on the same host/container as the encoding workers.
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
$userLine = EncoderCron::buildUserLine($rootPath, $php);

if (isset($options['print'])) {
    echo "As root, /etc/cron.d/{$fileName}:" . PHP_EOL . $cronFile . PHP_EOL;
    echo "Or in the crontab of {$user} (crontab -e):" . PHP_EOL . $userLine . PHP_EOL;
    exit(0);
}

if ($isRoot) {
    if (!is_dir('/etc/cron.d')) {
        fwrite(STDERR, '/etc/cron.d does not exist. Add this line to the crontab of ' . $user . ' instead:' . PHP_EOL . $userLine . PHP_EOL);
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
} else {
    $me = function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid()) : null;
    if (!empty($me['name']) && $me['name'] !== $user) {
        // A crontab entry runs as its owner; the cron must run as the web server user.
        fwrite(STDERR, "The cron must run as {$user}, but this is {$me['name']}. Run: sudo {$php} {$rootPath}install/installCron.php" . PHP_EOL);
        exit(1);
    }
    $current = [];
    exec('crontab -l 2>/dev/null', $current);
    foreach ($current as $line) {
        if (strpos($line, $fileName) !== false) {
            echo 'Already installed in this user\'s crontab:' . PHP_EOL . $line . PHP_EOL;
            exit(0);
        }
    }
    $current[] = $userLine;
    $temporary = tempnam(sys_get_temp_dir(), 'encoder-cron');
    file_put_contents($temporary, implode("\n", $current) . "\n");
    $output = [];
    $code = 0;
    exec('crontab ' . escapeshellarg($temporary) . ' 2>&1', $output, $code);
    @unlink($temporary);
    if ($code !== 0) {
        fwrite(STDERR, 'crontab failed: ' . implode(' ', $output) . PHP_EOL . 'Run as root instead: sudo ' . $php . ' ' . $rootPath . 'install/installCron.php' . PHP_EOL);
        exit(1);
    }
    echo 'Added to this user\'s crontab:' . PHP_EOL . $userLine . PHP_EOL;
}
echo 'The cron service must be running. The warning on the Encoder admin page disappears after the first run.' . PHP_EOL;
