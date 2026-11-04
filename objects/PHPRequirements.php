<?php

/**
 * PHP extensions the Encoder needs, checked for the web server PHP and for the command-line PHP
 * binaries that run the queue (getPHP(), view/run.php) and the monitor cron (install/cron.php).
 * Those are often different PHP versions: installing a newer PHP package on Ubuntu switches
 * /usr/bin/php to it, without the extensions installed for the web server's version.
 *
 * Standalone on purpose (no configuration, database or other Encoder files), so view/run.php and
 * install/cron.php can explain a missing extension before loading code that would die on it.
 */
class PHPRequirements
{
    // Same list the installer checks (install/installer.php).
    const EXTENSIONS = ['mysqli', 'curl', 'gd', 'mbstring', 'zip', 'zlib', 'openssl'];

    // Without these, loading the configuration or talking to the Streamer fails.
    const BLOCKING_EXTENSIONS = ['mysqli', 'curl'];

    // Ubuntu (ondrej/php) package that provides each extension.
    const PACKAGES = [
        'mysqli' => 'mysql',
        'curl' => 'curl',
        'gd' => 'gd',
        'mbstring' => 'mbstring',
        'zip' => 'zip',
        'zlib' => 'common',
        'openssl' => 'common',
    ];

    /**
     * @param string[] $loaded loaded extension names
     * @return string[] required extensions that are not loaded
     */
    public static function getMissing(array $loaded)
    {
        $loaded = array_map('strtolower', $loaded);
        return array_values(array_diff(self::EXTENSIONS, $loaded));
    }

    public static function getPackages(array $missing, $version)
    {
        $packages = [];
        foreach ($missing as $extension) {
            $suffix = isset(self::PACKAGES[$extension]) ? self::PACKAGES[$extension] : $extension;
            $packages[] = 'php' . $version . '-' . $suffix;
        }
        return array_values(array_unique($packages));
    }

    public static function getInstallCommand(array $missing, $version)
    {
        if (empty($missing)) {
            return '';
        }
        return 'sudo apt-get install -y ' . implode(' ', self::getPackages($missing, $version));
    }

    /** For a package that is installed but whose extension was disabled with phpdismod. */
    public static function getEnableCommand(array $missing, $version)
    {
        $modules = array_values(array_diff($missing, ['zlib', 'openssl']));
        if (empty($modules)) {
            return '';
        }
        return 'sudo phpenmod -v ' . $version . ' ' . implode(' ', $modules);
    }

    /**
     * @param string $binary PHP binary path, '' for the PHP running this code
     * @return array|null null when the binary could not be run
     */
    public static function check($binary = '')
    {
        if ($binary === '') {
            return self::buildReport(PHP_BINARY, PHP_VERSION, get_loaded_extensions(), PHP_SAPI === 'cli');
        }
        if (!function_exists('exec') || !@is_file($binary)) {
            return null;
        }
        // No double quotes: escapeshellarg() on Windows (local tests) replaces them.
        $code = "echo json_encode(['version' => PHP_VERSION, 'extensions' => get_loaded_extensions()]);";
        $output = [];
        $exitCode = 1;
        $null = PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null';
        @exec(escapeshellarg($binary) . ' -r ' . escapeshellarg($code) . ' 2>' . $null, $output, $exitCode);
        $info = json_decode(implode('', $output), true);
        if ($exitCode !== 0 || !is_array($info) || empty($info['version']) || !isset($info['extensions']) || !is_array($info['extensions'])) {
            return null;
        }
        return self::buildReport($binary, $info['version'], $info['extensions'], true);
    }

    /**
     * Reports with missing extensions for the PHP running this code and for each command-line binary.
     * A result without problems is cached for 10 minutes, so the admin page does not start PHP
     * processes on every load; a problem is checked again on each load until it is fixed.
     *
     * @param string[] $binaries command-line PHP binaries (empty values are ignored)
     * @return array[] only the reports that have missing extensions
     */
    public static function getProblems(array $binaries)
    {
        $unique = [];
        foreach ($binaries as $binary) {
            $binary = (string) $binary;
            if ($binary === '') {
                continue;
            }
            $real = @realpath($binary);
            $unique[$real ? $real : $binary] = $binary;
        }
        $cache = sys_get_temp_dir() . '/encoder_php_requirements_ok_' . md5(PHP_BINARY . PHP_VERSION . json_encode(array_keys($unique)));
        if (@is_file($cache) && time() - intval(@filemtime($cache)) < 600) {
            return [];
        }
        $problems = [];
        $reports = [self::check()];
        foreach ($unique as $binary) {
            $reports[] = self::check($binary);
        }
        foreach ($reports as $report) {
            if (!empty($report['missing'])) {
                $problems[] = $report;
            }
        }
        if (empty($problems)) {
            @touch($cache);
        }
        return $problems;
    }

    public static function buildReport($binary, $phpVersion, array $extensions, $isCLI)
    {
        $parts = explode('.', (string) $phpVersion);
        $version = intval($parts[0]) . '.' . intval(isset($parts[1]) ? $parts[1] : 0);
        $missing = self::getMissing($extensions);
        return [
            'binary' => (string) $binary,
            'phpVersion' => (string) $phpVersion,
            'version' => $version,
            'isCLI' => (bool) $isCLI,
            'missing' => $missing,
            'blocking' => array_values(array_intersect($missing, self::BLOCKING_EXTENSIONS)),
            'installCommand' => self::getInstallCommand($missing, $version),
            'enableCommand' => self::getEnableCommand($missing, $version),
        ];
    }

    /** One plain-text paragraph with the cause and the Ubuntu commands that fix it. */
    public static function getMessage(array $report)
    {
        if (empty($report['missing'])) {
            return '';
        }
        $who = $report['isCLI']
            ? "The command-line PHP {$report['phpVersion']} ({$report['binary']}) that runs the Encoder queue and cron"
            : "The web server PHP {$report['phpVersion']}";
        $message = "{$who} is missing the PHP extension(s): " . implode(', ', $report['missing']) . '.'
            . " Install them on the server (Ubuntu) with: {$report['installCommand']}";
        if (!empty($report['enableCommand'])) {
            $message .= " (if the packages are already installed, enable them with: {$report['enableCommand']})";
        }
        if (!$report['isCLI']) {
            $message .= ' and then restart the web server: sudo systemctl restart apache2';
        }
        return $message . '.';
    }

    /** The Encoder log (objects/include_config.php), '' in Docker where it logs to stderr. */
    public static function getLogFile($rootPath)
    {
        return file_exists('/var/www/docker_vars.json') ? '' : rtrim($rootPath, '/\\') . '/videos/avideo.log';
    }

    /**
     * Command-line entry points call this before loading the configuration. Logs the problem with
     * the fix and returns false when a missing extension makes running impossible.
     */
    public static function checkCommandLine($logFile, $context)
    {
        $report = self::check();
        if (empty($report['missing'])) {
            return true;
        }
        $message = $context . ': ' . self::getMessage($report);
        if (defined('STDERR')) {
            fwrite(STDERR, $message . PHP_EOL);
        }
        // view/run.php is started every few seconds: log the same problem at most every 10 minutes.
        $marker = sys_get_temp_dir() . '/encoder_php_requirements_' . md5($message . $logFile);
        if (@is_file($marker) && time() - intval(@filemtime($marker)) < 600) {
            return empty($report['blocking']);
        }
        @touch($marker);
        $line = '[' . date('d-M-Y H:i:s T') . '] ' . $message . PHP_EOL;
        if (empty($logFile) || !@error_log($line, 3, $logFile)) {
            error_log($message);
        }
        return empty($report['blocking']);
    }
}
