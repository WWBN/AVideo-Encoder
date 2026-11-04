<?php
// The queue and cron run on command-line PHP binaries that can lack extensions the web PHP has
// (e.g. /usr/bin/php switched to a newer PHP). The Encoder must name the missing extensions and
// the Ubuntu command that installs them, instead of dying with "undefined function".
if (PHP_SAPI !== 'cli') { exit(1); }
$root = rtrim($argv[1] ?? dirname(__DIR__), '/\\') . '/';
require $root . 'objects/PHPRequirements.php';
require $root . 'objects/EncoderCron.php';
$checks = 0;
function requirementsExpect($condition, $message) {
    global $checks;
    if (!$condition) { throw new RuntimeException($message); }
    ++$checks;
}
$dir = sys_get_temp_dir() . '/encoder-php-requirements-' . bin2hex(random_bytes(6));
mkdir($dir);
try {
    $all = PHPRequirements::EXTENSIONS;
    requirementsExpect(PHPRequirements::getMissing($all) === [], 'nothing missing when every extension is loaded');
    $withoutMb = array_values(array_diff($all, ['mbstring']));
    requirementsExpect(PHPRequirements::getMissing(array_map('strtoupper', $withoutMb)) === ['mbstring'], 'extension names are compared case-insensitively');

    $report = PHPRequirements::buildReport('/usr/bin/php8.5', '8.5.12', $withoutMb, true);
    requirementsExpect($report['version'] === '8.5', 'package version is major.minor');
    requirementsExpect($report['installCommand'] === 'sudo apt-get install -y php8.5-mbstring', 'Ubuntu install command for the CLI version');
    requirementsExpect($report['enableCommand'] === 'sudo phpenmod -v 8.5 mbstring', 'enable command for an installed but disabled extension');
    requirementsExpect($report['blocking'] === [], 'mbstring alone does not stop the queue');
    $message = PHPRequirements::getMessage($report);
    requirementsExpect(strpos($message, '/usr/bin/php8.5') !== false && strpos($message, 'mbstring') !== false
        && strpos($message, 'sudo apt-get install -y php8.5-mbstring') !== false, 'message names the binary, the extension and the command');
    requirementsExpect(strpos($message, 'restart') === false, 'command-line PHP needs no restart');

    $report = PHPRequirements::buildReport('/usr/sbin/apache2', '8.2.35', ['gd', 'zip', 'mbstring'], false);
    requirementsExpect($report['blocking'] === ['mysqli', 'curl'], 'mysqli and curl are blocking');
    requirementsExpect($report['installCommand'] === 'sudo apt-get install -y php8.2-mysql php8.2-curl php8.2-common', 'mysqli maps to php-mysql and built-in extensions to php-common once');
    requirementsExpect($report['enableCommand'] === 'sudo phpenmod -v 8.2 mysqli curl', 'built-in extensions are not passed to phpenmod');
    requirementsExpect(strpos(PHPRequirements::getMessage($report), 'sudo systemctl restart apache2') !== false, 'web server PHP asks for an Apache restart');
    requirementsExpect(PHPRequirements::getMessage(PHPRequirements::buildReport('php', '8.3.1', $all, true)) === '', 'no message without problems');

    $current = PHPRequirements::check();
    requirementsExpect($current['phpVersion'] === PHP_VERSION && $current['isCLI'], 'current process is checked without starting PHP');
    $external = PHPRequirements::check(PHP_BINARY);
    requirementsExpect(is_array($external) && $external['phpVersion'] === PHP_VERSION, 'another PHP binary is checked by running it');
    requirementsExpect(PHPRequirements::check($dir . '/missing-php') === null, 'a missing binary is reported as unknown, not as healthy or broken');

    $rootPath = '/var/www/html/Encoder 100%/';
    file_put_contents($dir . '/' . EncoderCron::getFileName($rootPath), EncoderCron::buildFile($rootPath, '/usr/bin/php8.5', 'www-data'));
    requirementsExpect(EncoderCron::getInstalledPHP($rootPath, $dir) === '/usr/bin/php8.5', 'installed cron PHP binary is read back');
    requirementsExpect(EncoderCron::getInstalledPHP('/other/', $dir) === '', 'no cron file means no binary');

    $log = $dir . '/avideo.log';
    $ok = PHPRequirements::checkCommandLine($log, 'test');
    requirementsExpect($ok === empty($current['blocking']), 'command-line check only stops on blocking extensions');
    requirementsExpect(empty($current['missing']) ? !is_file($log) : strpos((string) @file_get_contents($log), 'apt-get install') !== false, 'missing extensions are logged with the fix');

    echo 'PASS: ' . $checks . ' PHP requirement checks' . PHP_EOL;
} catch (Throwable $error) {
    fwrite(STDERR, 'FAIL: ' . $error->getMessage() . PHP_EOL);
    $failed = true;
} finally {
    foreach (glob($dir . '/*') as $path) { unlink($path); }
    rmdir($dir);
}
exit(empty($failed) ? 0 : 1);
