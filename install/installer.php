<?php
define('INSTALLER_PRODUCT', 'Encoder');
// Setup must work without loading the application's database configuration.
function installerRoot() { return str_replace('\\', '/', dirname(__DIR__)) . '/'; }
// A root CLI install (common in Docker entrypoints) must not leave configuration.php
// owned by root while the web server runs as another account (e.g. www-data): match
// the ownership already set on the videos directory before restricting permissions.
function installerMatchDirectoryOwnership($file, $directory) {
    if (PHP_OS_FAMILY === 'Windows' || !function_exists('posix_getuid') || posix_getuid() !== 0) { return; }
    $owner = @fileowner($directory);
    $group = @filegroup($directory);
    if ($owner !== false && $owner !== @fileowner($file)) { @chown($file, $owner); }
    if ($group !== false && $group !== @filegroup($file)) { @chgrp($file, $group); }
}
function installerConfigured() {
    clearstatcache();
    $file = installerRoot() . 'videos/configuration.php';
    return file_exists($file);
}
function installerSession() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Strict',
            'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
    }
    if (empty($_SESSION['install_csrf_token'])) { $_SESSION['install_csrf_token'] = bin2hex(random_bytes(32)); }
}
function installerURL() {
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $path = str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/install/index.php')));
    return ($https ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim($path, '/') . '/';
}
function installerQuote($value) {
    return "'" . str_replace("'", PHP_OS_FAMILY === 'Windows' ? "''" : "'\"'\"'", $value) . "'";
}
function installerPermissionHelp() {
    $target = is_dir(installerRoot() . 'videos') ? installerRoot() . 'videos' : rtrim(installerRoot(), '/');
    $root = installerQuote($target);
    if (PHP_OS_FAMILY === 'Windows') {
        return ['title' => 'PowerShell as administrator',
            'text' => 'Replace APACHE_ACCOUNT with the account running Apache (Services > Apache > Log On; if XAMPP was started manually, use whoami). Then try again.',
            'command' => 'icacls ' . $root . ' /grant "APACHE_ACCOUNT:(OI)(CI)M"'];
    }
    return ['title' => 'Server terminal', 'text' => 'Replace PHP_USER with the Apache/PHP-FPM account (for example, www-data). Grant access only to that account and try again.',
        'command' => "sudo apt-get install acl\nsudo setfacl -m u:PHP_USER:rwx " . $root];
}
function installerBinaryAvailable($name) {
    foreach (explode(PATH_SEPARATOR, getenv('PATH') ?: '') as $directory) {
        if ($directory === '') { continue; }
        $file = rtrim(trim($directory, '"'), '/\\') . DIRECTORY_SEPARATOR . $name . (PHP_OS_FAMILY === 'Windows' ? '.exe' : '');
        if (is_file($file) && is_executable($file)) { return true; }
    }
    return false;
}
function installerChecks() {
    $videos = installerRoot() . 'videos/';
    $checks = [
        ['label' => 'PHP 8.1 or later', 'ok' => version_compare(PHP_VERSION, '8.1', '>='), 'detail' => PHP_VERSION],
        ['label' => 'Database schema', 'ok' => is_readable(__DIR__ . '/database.sql'), 'detail' => 'install/database.sql'],
        ['label' => 'Configuration write access', 'ok' => is_dir($videos) ? is_writable($videos) : is_writable(installerRoot()), 'detail' => 'videos directory'],
    ];
    foreach (['mysqli', 'curl', 'gd', 'mbstring', 'zip', 'zlib', 'openssl'] as $extension) {
        $checks[] = ['label' => 'PHP ' . $extension, 'ok' => extension_loaded($extension), 'detail' => 'Enable in the web server php.ini'];
    }
    $checks[] = ['label' => 'Frontend assets', 'ok' => is_readable(installerRoot() . 'node_modules/jquery/dist/jquery.min.js') && is_readable(installerRoot() . 'node_modules/bootstrap/dist/js/bootstrap.min.js') && is_readable(installerRoot() . 'node_modules/bootstrap/dist/css/bootstrap.min.css'), 'detail' => 'Run npm install in the Encoder directory.'];
    foreach (['exec', 'proc_open'] as $function) {
        $checks[] = ['label' => 'PHP ' . $function, 'ok' => function_exists($function), 'detail' => 'Enable this function in the web server php.ini disable_functions setting.'];
    }
    foreach (['ffmpeg' => 'FFmpeg', 'ffprobe' => 'FFprobe'] as $binary => $label) {
        $checks[] = ['label' => $label, 'ok' => installerBinaryAvailable($binary), 'detail' => 'Install FFmpeg and add its binary directory to the PHP service PATH.'];
    }
    return $checks;
}
class InstallerFailure extends RuntimeException {
    public $help;
    public function __construct($message, array $help = []) { parent::__construct($message); $this->help = $help; }
}
function installerField(array $data, $key, $default = '') {
    $value = $data[$key] ?? $default;
    if (!is_string($value) || strlen($value) > 8192 || strpos($value, "\0") !== false) {
        throw new InstallerFailure('Invalid value for field ' . $key . '.');
    }
    return $value;
}
function installerValidateURL($url, $label) {
    $parts = parse_url($url);
    if (!filter_var($url, FILTER_VALIDATE_URL) || !$parts || !in_array($parts['scheme'] ?? '', ['http', 'https'], true) ||
        isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment']) || strlen($url) > 254) {
        throw new InstallerFailure($label . ': enter a complete HTTP/HTTPS URL without credentials, query parameters, or a fragment.');
    }
    return rtrim($url, '/') . '/';
}
function installerDatabaseMessage($code) {
    // MySQL client errors can use the operating system language.
    $messages = [
        1044 => 'The database account does not have access to this database.',
        1045 => 'Access denied. Check the database username and password.',
        1049 => 'The requested database does not exist.',
        1050 => 'A table with this name already exists.',
        1054 => 'A required database column is missing or incompatible.',
        1062 => 'A duplicate record conflicts with an existing database key.',
        1064 => 'The database rejected a SQL statement. Check the schema and server compatibility.',
        1142 => 'The database account does not have permission to perform this operation.',
        1143 => 'The database account does not have permission to access a required column.',
        1146 => 'A required database table is missing.',
        1227 => 'The database account is missing a required privilege.',
        1273 => 'The database server does not support the requested collation.',
        2002 => 'Unable to connect to the database server. Check the service, host, and port.',
        2003 => 'The database server refused the connection. Check the service, host, and port.',
        2005 => 'The database hostname could not be resolved.',
        2006 => 'The database server closed the connection.',
        2013 => 'The database connection was lost during the operation.',
    ];
    return 'MySQL/MariaDB (' . $code . '): ' . ($messages[$code] ?? 'The database operation failed. Review the database server logs and schema using the diagnostic command below.');
}
function installerDatabaseHelp($code, array $data) {
    $host = $data['databaseHost']; $port = $data['databasePort']; $db = $data['databaseName'];
    $user = str_replace(["\\", "'"], ["\\\\", "''"], $data['databaseUser']);
    $client = 'mysql';
    if (PHP_OS_FAMILY === 'Windows') {
        // Under Apache PHP_BINARY may be httpd.exe, so also inspect the loaded ini location.
        foreach ([dirname(php_ini_loaded_file() ?: PHP_BINARY, 2), dirname(PHP_BINARY, 2)] as $base) {
            $candidate = str_replace('\\', '/', $base) . '/mysql/bin/mysql.exe';
            if (is_file($candidate)) { $client = '& ' . installerQuote($candidate); break; }
        }
    }
    $connect = $client . ' --host=' . installerQuote($host) . ' --port=' . $port . ' --user=' . installerQuote($data['databaseUser']) . ' --password';
    if (in_array($code, [1044, 1045, 1142, 1143, 1227], true)) {
        return ['title' => 'MySQL / MariaDB access',
            'text' => 'Check your username and password. Test the connection using the command below. If permissions are missing, a database administrator must run the SQL; replace ACCOUNT_HOST with the MySQL account host (usually localhost for a local connection). The terminal will prompt for the password.',
            'command' => $connect,
            'sql' => "CREATE DATABASE IF NOT EXISTS `" . $db . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\nGRANT ALL PRIVILEGES ON `" . $db . "`.* TO '" . $user . "'@'ACCOUNT_HOST';"];
    }
    if (in_array($code, [2002, 2003, 2005, 2006, 2013], true)) {
        return ['title' => 'Check the database service',
            'text' => PHP_OS_FAMILY === 'Windows' ? 'Start MySQL in the XAMPP control panel. Check the host and port; for a remote server, also check the firewall. Test the port in PowerShell.' : 'Check the host and port. For a local database, check the service below; for a remote server, also check the firewall.',
            'command' => PHP_OS_FAMILY === 'Windows' ? 'Test-NetConnection -ComputerName ' . installerQuote($host) . ' -Port ' . $port : "sudo systemctl status mariadb\n# If the service is named mysql:\nsudo systemctl status mysql"];
    }
    return ['title' => 'Database diagnostics', 'text' => 'Open the SQL client and review the error and database structure. Do not remove tables containing data. Fix the cause, then try again.', 'command' => $connect . ' ' . installerQuote($db)];
}

function installerValidate(array $post, $testing = false) {
    $data = [];
    foreach (['databaseHost', 'databasePort', 'databaseName', 'databaseUser', 'databasePass'] as $key) {
        $data[$key] = installerField($post, $key, $key === 'databasePort' ? '3306' : '');
        if ($key !== 'databasePass') { $data[$key] = trim($data[$key]); }
    }
    if ($data['databaseHost'] === '' || strlen($data['databaseHost']) > 253 ||
        !ctype_digit($data['databasePort']) || (int)$data['databasePort'] < 1 || (int)$data['databasePort'] > 65535 ||
        !preg_match('/^[a-zA-Z0-9_\-]{1,64}$/D', $data['databaseName']) || $data['databaseUser'] === '') {
        throw new InstallerFailure('Check the database host, port, username, and name.');
    }
    $mode = $post['createTables'] ?? '2';
    if (is_int($mode)) { $post['createTables'] = (string)$mode; }
    $data['createTables'] = installerField($post, 'createTables', '2');
    $data['tablesPrefix'] = installerField($post, 'tablesPrefix');
    if (!in_array($data['createTables'], ['0','1','2'], true) || !preg_match('/^[A-Za-z0-9_]{0,25}$/D', $data['tablesPrefix'])) {
        throw new InstallerFailure('Select a valid setup option. Table prefixes accept up to 25 letters, numbers, and underscores.');
    }
    $GLOBALS['installerPrefix'] = $data['tablesPrefix'];
    if ($testing) { return $data; }
    if (realpath(installerField($post, 'systemRootPath', installerRoot())) !== realpath(installerRoot())) {
        throw new InstallerFailure('The application path must point to this Encoder installation.');
    }
    $data['systemRootPath'] = installerRoot();
    foreach (['webSiteRootURL', 'siteURL'] as $key) { $data[$key] = installerValidateURL(trim(installerField($post, $key)), $key); }
    $data['inputUser'] = trim(installerField($post, 'inputUser'));
    $data['inputPassword'] = installerField($post, 'inputPassword');
    if ($data['inputUser'] === '' || strlen($data['inputUser']) > 255 || $data['inputPassword'] === '') {
        throw new InstallerFailure('Enter the AVideo administrator username and password.');
    }
    $data['allowedStreamers'] = trim(installerField($post, 'allowedStreamers'));
    if (isset($post['defaultPriority']) && is_int($post['defaultPriority'])) { $post['defaultPriority'] = (string)$post['defaultPriority']; }
    $allowed = [];
    foreach (preg_split('/\r\n|\r|\n/', $data['allowedStreamers']) as $url) {
        if (trim($url) !== '') { $allowed[] = installerValidateURL(trim($url), 'Allowed Streamer URL'); }
    }
    $data['allowedStreamers'] = implode(PHP_EOL, array_unique($allowed));
    $data['defaultPriority'] = installerField($post, 'defaultPriority', '1');
    if (!ctype_digit($data['defaultPriority']) || (int)$data['defaultPriority'] < 1 || (int)$data['defaultPriority'] > 10) {
        throw new InstallerFailure('Default priority must be between 1 and 10.');
    }
    return $data;
}
function installerConfig(array $data) {
    $settings = ['configurationVersion' => 2, 'tablesPrefix' => $data['tablesPrefix'],
        'webSiteRootURL' => $data['webSiteRootURL'], 'systemRootPath' => installerRoot(),
        'webSiteRootPath' => parse_url($data['webSiteRootURL'], PHP_URL_PATH) ?: '/',
        'disableConfigurations' => false, 'disableBulkEncode' => false, 'disableImportVideo' => false,
        'disableWebM' => false, 'defaultWebM' => false, 'concurrent' => 1, 'hideUserGroups' => false,
        'progressiveUpload' => false, 'killWorkerOnDelete' => false,
        'allowed' => ['mp4','avi','mov','flv','mp3','wav','m4v','webm','wmv','mpg','mpeg','f4v','m4a','m2p','rm','vob','mkv','3gp','mts','m2ts']];
    $content = "<?php\n";
    foreach ($settings as $key => $value) { $content .= '$global[' . var_export($key, true) . '] = ' . var_export($value, true) . ";\n"; }
    foreach (['mysqlHost'=>'databaseHost','mysqlPort'=>'databasePort','mysqlUser'=>'databaseUser','mysqlPass'=>'databasePass','mysqlDatabase'=>'databaseName'] as $variable=>$key) {
        $content .= '$' . $variable . ' = ' . var_export($data[$key], true) . ";\n";
    }
    return $content . "require_once \$global['systemRootPath'] . 'objects/include_config.php';\n";
}
function installerLog($exception, $stage) {
    // Bootstrap failures must also be reportable before Composer/application helpers exist.
    $message = 'AVideo installer: ' . $stage . ', ' . get_class($exception) . ' (' . $exception->getCode() . '), line ' . $exception->getLine();
    if (function_exists('_error_log')) { _error_log($message); }
    else { error_log($message); }
}

function installerSchemaTables() { return ['formats','streamers','encoder_queue','upload_queue','configurations_encoder']; }
function installerTable($name) { return ($GLOBALS['installerPrefix'] ?? '') . $name; }
function installerStatements() {
    $statements = []; $statement = '';
    foreach (file(__DIR__ . '/database.sql') as $line) {
        if (strpos(ltrim($line), '--') === 0 || trim($line) === '') { continue; }
        $statement .= $line;
        if (substr(trim($line), -1) !== ';') { continue; }
        // Prefix table references and constraint names, never FFmpeg templates or column names.
        $statement = preg_replace_callback('/(CREATE TABLE IF NOT EXISTS|INSERT INTO|REFERENCES|CONSTRAINT) `([^`]+)`/', function ($match) {
            return $match[1] . ' `' . installerTable($match[2]) . '`';
        }, $statement);
        $statements[] = trim($statement); $statement = '';
    }
    return $statements;
}
function installerCheckEmptyDatabase($mysqli) {
    $expected = array_map('installerTable', installerSchemaTables());
    $tables = $mysqli->query('SHOW FULL TABLES');
    while ($table = $tables->fetch_row()) {
        if (!in_array($table[0], $expected, true) && ($GLOBALS['installerPrefix'] ?? '') !== '') { continue; }
        if ($table[1] !== 'BASE TABLE' || !in_array($table[0], $expected, true)) {
            throw new InstallerFailure('Use an empty database or a separate table prefix. Existing tables were not changed.');
        }
        // A manually imported schema includes the built-in format catalog. Other tables must be empty.
        if ($table[0] !== installerTable('formats') && $mysqli->query('SELECT 1 FROM `' . $table[0] . '` LIMIT 1')->num_rows) {
            throw new InstallerFailure('Encoder tables already contain data. Restore the existing configuration or choose another database or prefix.');
        }
    }
    $tables->free();
}
function installerImportSchema($mysqli) {
    foreach (installerStatements() as $statement) {
        if (strpos($statement, 'CREATE TABLE') === 0) { $mysqli->query($statement); }
    }
}
function installerVerifySchema($mysqli) {
    foreach (installerSchemaTables() as $name) {
        $table = installerTable($name);
        $mysqli->query('SELECT 1 FROM `' . $table . '` LIMIT 0');
        $stmt = $mysqli->prepare('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
        $stmt->bind_param('s', $table); $stmt->execute(); $stmt->bind_result($engine); $stmt->fetch(); $stmt->close();
        if (strcasecmp($engine ?? '', 'InnoDB') !== 0) { throw new InstallerFailure('Encoder installation requires InnoDB tables.'); }
    }
}
function installerSeed($mysqli, array $data) {
    $formats = installerTable('formats');
    if (!$mysqli->query('SELECT 1 FROM `' . $formats . '` LIMIT 1')->num_rows) {
        foreach (installerStatements() as $statement) {
            if (strpos($statement, 'INSERT INTO') === 0) { $mysqli->query($statement); }
        }
    }
    if ((int)$mysqli->query('SELECT COUNT(*) FROM `' . $formats . '` WHERE id BETWEEN 1 AND 35')->fetch_row()[0] !== 35) {
        throw new InstallerFailure('The format catalog is incomplete. Import the complete installation schema into a new database or prefix.');
    }
    $hash = md5(hash('whirlpool', sha1($data['inputPassword'])));
    $stmt = $mysqli->prepare('INSERT INTO `' . installerTable('streamers') . '` (siteURL,user,pass,priority,created,modified,isAdmin) VALUES (?,?,?,1,NOW(),NOW(),1)');
    $stmt->bind_param('sss', $data['siteURL'], $data['inputUser'], $hash); $stmt->execute(); $stmt->close();
    $stmt = $mysqli->prepare("INSERT INTO `" . installerTable('configurations_encoder') . "` (id,allowedStreamersURL,defaultPriority,version,created,modified) VALUES (1,?,?,'8.1',NOW(),NOW())");
    $stmt->bind_param('si', $data['allowedStreamers'], $data['defaultPriority']); $stmt->execute(); $stmt->close();
}
function installerValidateStreamer(array $data) {
    $curl = curl_init($data['siteURL'] . 'login');
    // Let the Streamer verify the password with its own salt; raw hashes are not login tokens.
    curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query([
        'user' => $data['inputUser'], 'pass' => $data['inputPassword'], 'encodedPass' => 'false']),
        CURLOPT_USERAGENT => 'AVideoEncoder Installer',
        CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_TIMEOUT => 25,
        CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS, CURLOPT_FOLLOWLOCATION => false]);
    $body = curl_exec($curl); $status = curl_getinfo($curl, CURLINFO_HTTP_CODE); $errno = curl_errno($curl);
    curl_close($curl);
    $help = ['title' => 'Check the AVideo site', 'text' => 'Check the final URL (without redirects) and administrator credentials. For certificate errors, fix the server TLS certificate chain or configure curl.cainfo in php.ini and restart Apache.',
        'command' => 'curl --head ' . installerQuote($data['siteURL'] . 'login')];
    if ($body === false || $status < 200 || $status >= 300) {
        throw new InstallerFailure('Unable to verify the AVideo site (HTTP ' . $status . ', cURL ' . $errno . ').', $help);
    }
    $response = json_decode($body, true);
    if (!is_array($response) || !in_array($response['isAdmin'] ?? false, [true, 1, '1'], true)) {
        throw new InstallerFailure('The AVideo site did not confirm administrator access. Check the URL, username, and password.', $help);
    }
}

require_once __DIR__ . '/ubuntu-help-functions.php';
