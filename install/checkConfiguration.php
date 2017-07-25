<?php

$installationVersion = "3.4";

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

$sql = "INSERT INTO `formats` VALUES "
        . "(1,'MP4','ffmpeg -i {\$pathFileName} -vf scale=640:360 -vcodec h264 -acodec aac -strict -2 -y {\$destinationFile}','2017-01-01 00:00:00','2017-07-24 16:07:03','mp4','mp4'),"
        . "(2,'Webm','ffmpeg -i {\$pathFileName} -vf scale=320:180 -f webm -c:v libvpx -b:v 1M -acodec libvorbis -y {\$destinationFile}','2017-07-11 12:56:26','2017-07-24 16:07:03','webm','mp4'),"
        . "(3,'MP3','ffmpeg -i {\$pathFileName} -acodec libmp3lame -y {\$destinationFile}','2017-01-01 00:00:00','2017-07-24 16:07:03','mp3','mp3'),"
        . "(4,'OGG','ffmpeg -i {\$pathFileName} -acodec libvorbis -y {\$destinationFile}','2017-01-01 00:00:00','2017-07-24 16:07:03','ogg','mp3'),"
        . "(5,'MP3 to Spectrum.MP4','ffmpeg -i {\$pathFileName} -filter_complex \'[0:a]showwaves=s=640x360:mode=line,format=yuv420p[v]\' -map \'[v]\' -map 0:a -c:v libx264 -c:a copy {\$destinationFile}','2017-01-01 00:00:00','2017-07-24 16:07:03','mp4','mp3'),"
        . "(6,'Video.MP4 to Audio.MP3','ffmpeg -i {\$pathFileName} -y {\$destinationFile}','2017-01-01 00:00:00','2017-07-24 16:07:03','mp3','mp4'),"
        . "(7,'Video to Spectrum','6-5-2','2017-01-01 00:00:00','2017-01-01 00:00:00','mp4','mp4'),"
        . "(8,'Video to Audio','6-4','2017-01-01 00:00:00','2017-01-01 00:00:00','mp3','mp4'),"
        . "(9,'Both Video','1-2','2017-01-01 00:00:00','2017-01-01 00:00:00','mp4','mp4'),(10,'Both Audio','3-4','2017-01-01 00:00:00','2017-01-01 00:00:00','mp3','mp3');";
if ($mysqli->query($sql) !== TRUE) {
    $obj->error = "Error creating Formats: " . $mysqli->error;
    echo json_encode($obj);
    exit;
}

$mysqli->close();

$content = "<?php
\$global['webSiteRootURL'] = '{$_POST['webSiteRootURL']}';
\$global['systemRootPath'] = '{$_POST['systemRootPath']}';

\$global['disableConfigurations'] = false;
\$global['disableBulkEncode'] = false;

\$mysqlHost = '{$_POST['databaseHost']}';
\$mysqlUser = '{$_POST['databaseUser']}';
\$mysqlPass = '{$_POST['databasePass']}';
\$mysqlDatabase = '{$_POST['databaseName']}';

\$global['allowed'] = array('mp4', 'avi', 'mov', 'mkv', 'flv', 'mp3', 'wav', 'm4v', 'webm', 'wmv');
/**
 * Do NOT change from here
 */

require_once \$global['systemRootPath'].'objects/include_config.php';
";

$fp = fopen($_POST['systemRootPath'] . "videos/configuration.php", "wb");
fwrite($fp, $content);
fclose($fp);

$obj->success = true;
echo json_encode($obj);
