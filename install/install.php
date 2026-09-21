<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Command line only'); }
if (file_exists(__DIR__ . '/../videos/configuration.php')) { exit('Setup is locked.'); }
$databaseUser = "youphptube";
$databasePass = "youphptube";
$databaseHost = "localhost";
if (version_compare(phpversion(), '7.2', '<')) {
    $databaseUser = "root";
}
ob_start();
$siteURL = @$argv[1];
while (!filter_var($siteURL, FILTER_VALIDATE_URL)) {
    if (!empty($siteURL)) {
        echo "Invalid Site URL\n";
    }
    echo "Enter Site URL\n";
    ob_flush();
    $siteURL = trim(readline(""));
}
$siteURL = rtrim($siteURL, '/') . '/';

// Determine the folder name based on the current script directory
$folderName = basename(dirname(__DIR__));
// Extract the domain name from the URL
$domainName = preg_replace("/[^0-9a-z]/i", "", parse_url($siteURL, PHP_URL_HOST));
$databaseName = "AVideoEncoder_" . $domainName . "_" . preg_replace("/[^0-9a-z]/i", "", $folderName);
$webSiteRootURL = $siteURL . "Encoder/";

$databaseUser = empty($argv[2]) ? $databaseUser : $argv[2];
$databasePass = $argv[3] ?? $databasePass;
$systemAdminPass = $argv[4] ?? (getenv('STREAMER_PASSWORD') ?: '');
if ($systemAdminPass === '') {
    require_once __DIR__ . '/installer.php';
    if (!extension_loaded('curl')) { fwrite(STDERR, "Enable the PHP curl extension to verify the Streamer password.\n"); exit(1); }
    $systemAdminPass = '123';
    for ($attempt = 0; $attempt < 2; ++$attempt) {
        try {
            installerValidateStreamer(['siteURL' => $siteURL, 'inputUser' => 'admin', 'inputPassword' => $systemAdminPass]);
            break;
        } catch (InstallerFailure $e) {
            fwrite(STDERR, $e->getMessage() . "\n");
            if ($attempt === 1) { exit(1); }
            fwrite(STDERR, "Enter the Streamer administrator password (leave empty to cancel): ");
            $passwordLine = fgets(STDIN);
            $systemAdminPass = $passwordLine === false ? '' : rtrim($passwordLine, "\r\n");
            if ($systemAdminPass === '') { fwrite(STDERR, "Installation cancelled.\n"); exit(1); }
        }
    }
}
$databaseName = empty($argv[5]) ? $databaseName : $argv[5];
$webSiteRootURL = empty($argv[6]) ? $webSiteRootURL : $argv[6];
$databaseHost = empty($argv[7]) ? $databaseHost : $argv[7];
$databasePort = empty($argv[8]) ? '3306' : $argv[8];

// install.php siteURL databaseUser databasePass systemAdminPass databaseName webSiteRootURLEncoder databaseHost databasePort

$_POST['systemRootPath'] = str_replace('\\', '/', dirname(__DIR__)) . '/';
$_POST['databaseHost'] = $databaseHost;
$_POST['databaseUser'] = $databaseUser;
$_POST['databasePass'] = $databasePass;
$_POST['databasePort'] = $databasePort;
$_POST['databaseName'] = $databaseName;
$_POST['createTables'] = 2;
$_POST['systemAdminPass'] = $systemAdminPass;
$_POST['inputUser'] = 'admin';
$_POST['inputPassword'] = $systemAdminPass;
$_POST['webSiteTitle'] = "AVideo";
$_POST['siteURL'] = $siteURL;
$_POST['webSiteRootURL'] = $webSiteRootURL;
$_POST['allowedStreamers'] = "";
$_POST['defaultPriority'] = 1;

require __DIR__ . '/checkConfiguration.php';
exit(!empty($installerSucceeded) ? 0 : 1);
