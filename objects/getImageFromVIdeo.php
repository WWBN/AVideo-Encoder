<?php
$url = base64_decode($_GET['url']);
$destinationFile = md5($url);
$destination = sys_get_temp_dir().DIRECTORY_SEPARATOR.$destinationFile.".png";
if(!file_exists($destination)){
    $exec = "ffmpeg -i {$url} -f image2 -vframes 1 {$destination}";
    shell_exec($exec);
}
header('Content-Type: image/x-png');
readfile($destination);
die();