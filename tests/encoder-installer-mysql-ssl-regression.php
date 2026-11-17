<?php
// The installer's "Encrypted connection (SSL/TLS)" option must write the $global['mysqlSSL'] value that
// objects/mysqlConnection.php reads, and test the connection with the same flags the Encoder will use.
// Run: php tests/encoder-installer-mysql-ssl-regression.php
if (PHP_SAPI !== 'cli') { exit(1); }
$root = dirname(__DIR__);
require $root . '/install/installer.php';
require $root . '/objects/mysqlConnection.php';
unset($root);
$checks = 0;
function installerSSLExpect($condition, $message) {
    global $checks;
    if (!$condition) { fwrite(STDERR, "FAIL: {$message}\n"); exit(1); }
    ++$checks;
}
class InstallerSSLRecordingMySQLi {
    public $ssl = null;
    public $flags = null;
    public function ssl_set($key, $cert, $ca, $capath, $cipher) { $this->ssl = [$key, $cert, $ca, $capath, $cipher]; return true; }
    public function real_connect($host, $user, $pass, $database, $port, $socket, $flags) { $this->flags = $flags; return true; }
}
function installerSSLForm(array $extra = []) {
    return $extra + ['databaseHost' => 'db.example.com', 'databasePort' => '3306', 'databaseName' => 'encoder',
        'databaseUser' => 'encoder', 'databasePass' => 'secret', 'createTables' => '2'];
}
function installerSSLWritten(array $data) {
    $source = installerConfig($data + ['webSiteRootURL' => 'https://encoder.example.com/', 'tablesPrefix' => '']);
    token_get_all($source, TOKEN_PARSE);
    $assignments = substr(explode("require_once \$global['systemRootPath']", $source)[0], 5);
    return (static function ($php) {
        $global = [];
        eval($php);
        return array_key_exists('mysqlSSL', $global) ? $global['mysqlSSL'] : 'unset';
    })($assignments);
}

$data = installerValidate(installerSSLForm(), true);
installerSSLExpect($data['databaseSSL'] === '0' && installerSSLWritten($data) === 'unset', 'default writes no mysqlSSL');
$mysqli = new InstallerSSLRecordingMySQLi();
installerConnect($mysqli, $data);
installerSSLExpect($mysqli->flags === 0 && $mysqli->ssl === null, 'default connects without SSL');

foreach ([[['databaseSSL' => 'encrypt'], true], [['databaseSSL' => 'verify'], ['verifyServerCert' => true]],
    [['databaseSSL' => 'verify', 'databaseSSLCA' => __FILE__], ['ca' => __FILE__]]] as [$fields, $expected]) {
    $data = installerValidate(installerSSLForm($fields), true);
    $written = installerSSLWritten($data);
    installerSSLExpect($written === $expected, 'writes ' . json_encode($expected));
    $runtime = getMySQLSSLConnectionSettings($written);
    $mysqli = new InstallerSSLRecordingMySQLi();
    installerConnect($mysqli, $data);
    installerSSLExpect($mysqli->flags === $runtime['flags'], 'same flags as the Encoder for ' . json_encode($expected));
    installerSSLExpect(($mysqli->ssl[2] ?? null) === $runtime['ca'], 'same CA as the Encoder for ' . json_encode($expected));
}

foreach ([['databaseSSL' => 'yes'], ['databaseSSL' => 'encrypt', 'databaseSSLCA' => __FILE__],
    ['databaseSSL' => 'verify', 'databaseSSLCA' => __DIR__ . '/missing-ca.pem']] as $fields) {
    try {
        installerValidate(installerSSLForm($fields), true);
        installerSSLExpect(false, 'rejects ' . json_encode($fields));
    } catch (InstallerFailure $e) {
        installerSSLExpect(true, 'rejected');
    }
}
installerSSLExpect(strpos(installerDatabaseMessage(3159), 'only accepts encrypted connections') !== false, '3159 explained');
installerSSLExpect(strpos(installerDatabaseMessage(2002, ['databaseSSL' => 'verify']), 'encrypted connection could not be established') !== false, '2002 with SSL explained');

echo "OK {$checks} checks\n";
