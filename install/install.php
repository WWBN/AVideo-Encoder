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
if (version_compare(phpversion(), '7.2', '<')) {
    $databaseUser = "root";
}
ob_start();
$webSiteRootURL = @$argv[1];
$databaseUser = empty($argv[2])?$databaseUser:$argv[2];
$databasePass = empty($argv[3])?$databasePass:$argv[3];
$systemAdminPass = empty($argv[4]) ? "123" : $argv[4];
while (!filter_var($webSiteRootURL, FILTER_VALIDATE_URL)) {
    if (!empty($webSiteRootURL)) {
        echo "Invalid Site URL\n";
    }
    echo "Enter Site URL\n";
    ob_flush();
    $webSiteRootURL = trim(readline(""));
}
$webSiteRootURL = rtrim($webSiteRootURL, '/') . '/';

$_POST['systemRootPath'] = str_replace("install", "", getcwd());
if(!is_dir($_POST['systemRootPath'])){
    $_POST['systemRootPath'] = "/var/www/html/YouPHPTube/Encoder/";
    if(!is_dir($_POST['systemRootPath'])){
        $_POST['systemRootPath'] = "/var/www/html/AVideo/Encoder/";
    }
}
echo "Installing in {$_POST['systemRootPath']}".PHP_EOL;
$_POST['databaseHost'] = "localhost";
$_POST['databaseUser'] = $databaseUser;
$_POST['databasePass'] = $databasePass;
$_POST['databasePort'] = "3306";
$_POST['databaseName'] = empty($argv[5])?"AVideoEncoder":$argv[5];
$_POST['createTables'] = 2;
$_POST['systemAdminPass'] = $systemAdminPass;
$_POST['inputUser'] = 'admin';
$_POST['inputPassword'] = $systemAdminPass;
$_POST['webSiteTitle'] = "AVideo";
$_POST['siteURL'] = $webSiteRootURL;
$_POST['webSiteRootURL'] = empty($argv[6])?($webSiteRootURL . "Encoder/"):$argv[6];
$_POST['allowedStreamers'] = "";
$_POST['defaultPriority'] = 1;

include './checkConfiguration.php';

$streamerConfiguration = "{$_POST['systemRootPath']}../videos/configuration.php";
if (file_exists($streamerConfiguration)) {
    require_once $streamerConfiguration;
    $sql = "UPDATE configurations SET "
            . "encoderURL = '{$global['mysqli']->real_escape_string($webSiteRootURL)}'"
            . " WHERE id = 1";

    $global['mysqli']->query($sql);
}else{
    echo PHP_EOL."File not found {$streamerConfiguration}".PHP_EOL;
}

