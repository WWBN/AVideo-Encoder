<?php

if (file_exists("../videos/configuration.php")) {
    error_log("Can not create configuration again: ".  json_encode($_SERVER));
    exit;
}
$_POST['databaseName'] = str_replace('-', '_', $_POST['databaseName']);
require_once '../objects/functions.php';

$installationVersion = "4.0";

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
if ($mysqli->connect_error) {
    $obj->error = ('Connect Error (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
    echo json_encode($obj);
    exit;
}

if ($_POST['createTables'] == 2) {
    $sql = "CREATE DATABASE IF NOT EXISTS `{$_POST['databaseName']}`";
    
    try {
        $mysqli->query($sql);
    } catch (Exception $exc) {
        $obj->error = "Error: " . $mysqli->error;
        echo json_encode($obj);
    }
}
error_log("CheckConfiguration: createTables={$_POST['createTables']} databaseName={$_POST['databaseName']} ");
$mysqli->select_db($_POST['databaseName']);

$tablesPrefix = '';
if(!empty($_REQUEST['tablesPrefix'])){
    $tablesPrefix = preg_replace('/[^0-9a-z_]/i', '', $_REQUEST['tablesPrefix']);
}
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
            if(!empty($tablesPrefix)){
                $templine = addPrefixIntoQuery($templine, $tablesPrefix);
            }
            //echo $templine.PHP_EOL;
            // Perform the query
            
            try {
                $mysqli->query($templine);
            } catch (Exception $exc) {
                $obj->error = 'Error performing query \'<strong>' . $templine . '\': ' . $mysqli->error . '<br /><br />';
            }
            // Reset temp variable to empty
            $templine = '';
        }
    }
}


if (substr($_POST['siteURL'], -1) !== '/') {
    $_POST['siteURL'] .= "/";
}

$sql = "INSERT INTO {$tablesPrefix}streamers (siteURL, user, pass, priority, created, modified, isAdmin) VALUES ('{$_POST['siteURL']}', '{$_POST['inputUser']}', '{$_POST['inputPassword']}', 1, now(), now(), 1)";

try {
    $mysqli->query($sql);
} catch (Exception $exc) {
    $obj->error = 'Error: ' . $mysqli->error.PHP_EOL;
}

$sql = "INSERT INTO {$tablesPrefix}configurations_encoder (id, allowedStreamersURL, defaultPriority, version, created, modified) VALUES (1, '{$_POST['allowedStreamers']}', '{$_POST['defaultPriority']}', '{$installationVersion}', now(), now())";

try {
    $mysqli->query($sql);
} catch (Exception $exc) {
    $obj->error = 'Error: ' . $mysqli->error.PHP_EOL;
}

$mysqli->close();

$content = "<?php
\$global['configurationVersion'] = 2;
\$global['tablesPrefix'] = '{$tablesPrefix}';
\$global['webSiteRootURL'] = '{$_POST['webSiteRootURL']}';
\$global['systemRootPath'] = '{$_POST['systemRootPath']}';
\$global['webSiteRootPath'] = '';

\$global['disableConfigurations'] = false;
\$global['disableBulkEncode'] = false;
\$global['disableImportVideo'] = false;
\$global['disableWebM'] = false;
\$global['defaultWebM'] = false;
\$global['concurrent'] = 1;
\$global['hideUserGroups'] = false;
\$global['progressiveUpload'] = false;
\$global['killWorkerOnDelete'] = false;

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


$videosDir = $_POST['systemRootPath'].'videos/';

if(!is_dir($videosDir)){
    mkdir($videosDir, 0777, true);
}

$fp = fopen("{$videosDir}configuration.php", "wb");
fwrite($fp, $content);
fclose($fp);

$obj->success = true;
echo json_encode($obj);
