<?php

require_once '../objects/functions.php';
if (!isCommandLineInterface()) {
    die('Command Line only');
}
if (file_exists("../videos/configuration.php")) {
    die("Can not create configuration again: " . json_encode($_SERVER));
}

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
$folderName = basename(dirname(getcwd()));
// Extract the domain name from the URL
$domainName = preg_replace("/[^0-9a-z]/i", "", parse_url($siteURL, PHP_URL_HOST));
$databaseName = "AVideoEncoder_" . $domainName . "_" . preg_replace("/[^0-9a-z]/i", "", $folderName);
$webSiteRootURL = $siteURL . "Encoder/";

$databaseUser = empty($argv[2]) ? $databaseUser : $argv[2];
$databasePass = empty($argv[3]) ? $databasePass : $argv[3];
$systemAdminPass = empty($argv[4]) ? "123" : $argv[4];
$databaseName = empty($argv[5]) ? $databaseName : $argv[5];
$webSiteRootURL = empty($argv[6]) ? $webSiteRootURL : $argv[6];
$databaseHost = empty($argv[7]) ? $databaseHost : $argv[7];
$databasePort = empty($argv[8]) ? '3306' : $argv[8];

// install.php siteURL databaseUser databasePass systemAdminPass databaseName webSiteRootURLEncoder databaseHost databasePort

$_POST['systemRootPath'] = str_replace("install", "", getcwd());
if (!is_dir($_POST['systemRootPath'])) {
    $_POST['systemRootPath'] = "/var/www/html/YouPHPTube/Encoder/";
    if (!is_dir($_POST['systemRootPath'])) {
        $_POST['systemRootPath'] = "/var/www/html/AVideo/Encoder/";
    }
}
echo "Installing in {$_POST['systemRootPath']}" . PHP_EOL;
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

include './checkConfiguration.php';

$streamerConfiguration = "{$_POST['systemRootPath']}../videos/configuration.php";
if (file_exists($streamerConfiguration)) {
    require_once $streamerConfiguration;
    $sql = "UPDATE {$global['tablesPrefix']}configurations_encoder SET "
            . "encoderURL = '{$global['mysqli']->real_escape_string($siteURL)}'"
            . " WHERE id = 1";

    $global['mysqli']->query($sql);
} else {
    echo PHP_EOL . "File not found {$streamerConfiguration}" . PHP_EOL;
}
