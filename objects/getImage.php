<?php
$url = base64_decode($_GET['base64Url']);
$destinationFile = md5($url);
$destination = sys_get_temp_dir().DIRECTORY_SEPARATOR.$destinationFile.".png";
if(!file_exists($destination)){
    $exec = "ffmpeg -i {$url} -f image2 -vframes 1 {$destination}";
    error_log("Exec get Image: {$exec}");
    shell_exec($exec);
}
header('Content-Type: image/x-png');
error_log("Destination get Image: {$destination}");
echo file_get_contents($destination);
die();