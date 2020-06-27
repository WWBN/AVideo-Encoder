<?php

require_once '../objects/functions.php';
if (!isCommandLineInterface()) {
    die('Command Line only');
}
if (file_exists("../videos/configuration.php")) {
    die("Can not create configuration again: " . json_encode($_SERVER));
}

$webSiteRootURL = @$argv[1];
$databaseUser = empty($argv[2])?"youphptube":$argv[2];
$databasePass = empty($argv[3])?"youphptube":$argv[3];
$systemAdminPass = empty($argv[4])?"123":$argv[4];
while (!filter_var($webSiteRootURL, FILTER_VALIDATE_URL)) {
    if (!empty($webSiteRootURL)) {
        echo "Invalid Site URL\n";
    }
    echo "Enter Site URL\n";
    ob_flush();
    $webSiteRootURL = trim(readline(""));
}


$_POST['systemRootPath'] = "/var/www/html/YouPHPTube/Encoder/";
$_POST['databaseHost'] = "localhost";
$_POST['databaseUser'] = $databaseUser;
$_POST['databasePass'] = $databasePass;
$_POST['databasePort'] = "3306";
$_POST['databaseName'] = "AVideoEncoder";
$_POST['createTables'] = 2;
$_POST['systemAdminPass'] = $systemAdminPass;
$_POST['webSiteTitle'] = "AVideo";
$_POST['siteURL'] = $webSiteRootURL;
$_POST['webSiteRootURL'] = $webSiteRootURL."Encoder/";
$_POST['allowedStreamers'] = "";
$_POST['defaultPriority'] = 1;

include './checkConfiguration.php';
