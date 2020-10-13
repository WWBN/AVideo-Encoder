<?php

if (file_exists("../videos/configuration.php")) {
    error_log("Can not create configuration again: ".  json_encode($_SERVER));
    exit;
}
$installationVersion = "3.3";

header('Content-Type: application/json');

$obj = new stdClass();
$obj->post = $_POST;

if(empty($_POST['systemRootPath'])){
    $obj->error = "Your system path to application can not be empty";
    echo json_encode($obj);
    exit;
}

if (!file_exists($_POST['systemRootPath'] . "index.php")) {
    $obj->error = "Your system path to application ({$_POST['systemRootPath']}) is wrong";
    echo json_encode($obj);
    exit;
}

$mysqli = @new mysqli($_POST['databaseHost'], $_POST['databaseUser'], $_POST['databasePass']);

/*
 * This is the "official" OO way to do it,
 * BUT $connect_error was broken until PHP 5.2.9 and 5.3.0.
 */
if ($mysqli->connect_error) {
    $obj->error = ('Connect Error (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
    echo json_encode($obj);
    exit;
}

if ($_POST['createTables'] == 2) {
    $sql = "CREATE DATABASE IF NOT EXISTS `{$_POST['databaseName']}`";
    if ($mysqli->query($sql) !== TRUE) {
        $obj->error = "Error creating database: " . $mysqli->error;
        echo json_encode($obj);
        exit;
    }
}
$mysqli->select_db($_POST['databaseName']);

/*
  $cmd = "mysql -h {$_POST['databaseHost']} -u {$_POST['databaseUser']} -p {$_POST['databasePass']} {$_POST['databaseName']} < {$_POST['systemRootPath']}install/database.sql";
  exec("{$cmd} 2>&1", $output, $return_val);
  if ($return_val !== 0) {
  $obj->error = "Error on command: {$cmd}";
  echo json_encode($obj);
  exit;
  }
 */
if ($_POST['createTables'] > 0) {
// Temporary variable, used to store current query
    $templine = '';
// Read in entire file
    $lines = file("{$_POST['systemRootPath']}install/database.sql");
// Loop through each line
    $obj->error = "";
    foreach ($lines as $line) {
// Skip it if it's a comment
        if (substr($line, 0, 2) == '--' || $line == '')
            continue;

// Add this line to the current segment
        $templine .= $line;
// If it has a semicolon at the end, it's the end of the query
        if (substr(trim($line), -1, 1) == ';') {
            // Perform the query
            if (!$mysqli->query($templine)) {
                $obj->error = ('Error performing query \'<strong>' . $templine . '\': ' . $mysqli->error . '<br /><br />');
            }
            // Reset temp variable to empty
            $templine = '';
        }
    }
}


if (substr($_POST['siteURL'], -1) !== '/') {
    $_POST['siteURL'] .= "/";
}

$sql = "INSERT INTO streamers (siteURL, user, pass, priority, created, modified, isAdmin) VALUES ('{$_POST['siteURL']}', '{$_POST['inputUser']}', '{$_POST['inputPassword']}', 1, now(), now(), 1)";
if ($mysqli->query($sql) !== TRUE) {
    $obj->error = "Error creating streamer: " . $mysqli->error;
    echo json_encode($obj);
    exit;
}

$sql = "INSERT INTO configurations (id, allowedStreamersURL, defaultPriority, version, created, modified) VALUES (1, '{$_POST['allowedStreamers']}', '{$_POST['defaultPriority']}', '{$installationVersion}', now(), now())";
if ($mysqli->query($sql) !== TRUE) {
    $obj->error = "Error creating streamer: " . $mysqli->error;
    echo json_encode($obj);
    exit;
}

$mysqli->close();

$content = "<?php
\$global['configurationVersion'] = 2;
\$global['webSiteRootURL'] = '{$_POST['webSiteRootURL']}';
\$global['systemRootPath'] = '{$_POST['systemRootPath']}';
\$global['webSiteRootPath'] = '';

\$global['disableConfigurations'] = false;
\$global['disableBulkEncode'] = false;
\$global['disableImportVideo'] = false;
\$global['disableWebM'] = false;

\$mysqlHost = '{$_POST['databaseHost']}';
\$mysqlUser = '{$_POST['databaseUser']}';
\$mysqlPass = '{$_POST['databasePass']}';
\$mysqlDatabase = '{$_POST['databaseName']}';

\$global['allowed'] = array('mp4', 'avi', 'mov', 'flv', 'mp3', 'wav', 'm4v', 'webm', 'wmv', 'mpg', 'mpeg', 'f4v', 'm4v', 'm4a', 'm2p', 'rm', 'vob', 'mkv', '3gp');
/**
 * Do NOT change from here
 */
if(empty(\$global['webSiteRootPath'])){
    preg_match('/https?:\/\/[^\/]+(.*)/i', \$global['webSiteRootURL'], \$matches);
    if(!empty(\$matches[1])){
        \$global['webSiteRootPath'] = \$matches[1];
    }
}
if(empty(\$global['webSiteRootPath'])){
    die('Please configure your webSiteRootPath');
}


require_once \$global['systemRootPath'].'objects/include_config.php';
";

$fp = fopen($_POST['systemRootPath'] . "videos/configuration.php", "wb");
fwrite($fp, $content);
fclose($fp);

$obj->success = true;
echo json_encode($obj);
