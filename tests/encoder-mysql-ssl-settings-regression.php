<?php
// $global['mysqlSSL'] in videos/configuration.php turns on SSL/TLS for the database connection.
// Run: php tests/encoder-mysql-ssl-settings-regression.php
if (PHP_SAPI !== 'cli') { exit(1); }
require dirname(__DIR__) . '/objects/mysqlConnection.php';
$checks = 0;
function mysqlSSLExpect($condition, $message) {
    global $checks;
    if (!$condition) { fwrite(STDERR, "FAIL: {$message}\n"); exit(1); }
    ++$checks;
}

mysqlSSLExpect(getMySQLSSLConnectionSettings(null) === false, 'unset keeps the plain connection');
mysqlSSLExpect(getMySQLSSLConnectionSettings(false) === false, 'false keeps the plain connection');
mysqlSSLExpect(getMySQLSSLConnectionSettings([]) === false, 'empty array keeps the plain connection');

$s = getMySQLSSLConnectionSettings(true);
mysqlSSLExpect($s['verifyServerCert'] === false, 'true does not check the certificate');
mysqlSSLExpect($s['flags'] === (MYSQLI_CLIENT_SSL | MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT), 'true encrypts');

$s = getMySQLSSLConnectionSettings(['ca' => '/etc/mysql/ca.pem']);
mysqlSSLExpect($s['verifyServerCert'] === true && $s['ca'] === '/etc/mysql/ca.pem', 'a CA turns on the certificate check');
mysqlSSLExpect($s['flags'] === (MYSQLI_CLIENT_SSL | MYSQLI_CLIENT_SSL_VERIFY_SERVER_CERT), 'a CA sets the verify flag');

$s = getMySQLSSLConnectionSettings(['ca' => '/etc/mysql/ca.pem', 'verifyServerCert' => false]);
mysqlSSLExpect($s['verifyServerCert'] === false, 'verifyServerCert=false wins over the CA');

$s = getMySQLSSLConnectionSettings(['cert' => '/c.pem', 'key' => '/k.pem', 'ca' => ['x']]);
mysqlSSLExpect($s['cert'] === '/c.pem' && $s['key'] === '/k.pem' && $s['ca'] === null, 'client certificate kept, invalid CA ignored');

global $global;
$global = ['mysqlSSL' => false];
try {
    _mysql_new_connection('127.0.0.1', 'nobody', 'nothing', '', 1);
    mysqlSSLExpect(false, 'a refused plain connection must throw');
} catch (mysqli_sql_exception $e) {
    mysqlSSLExpect(true, 'plain connection still uses new mysqli()');
}

echo "OK {$checks} checks\n";
