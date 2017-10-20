<?php
require_once dirname(__FILE__) . '/../videos/configuration.php';
header('Access-Control-Allow-Origin: *');
$url = base64_decode($_GET['base64Url']);
$destinationFile = md5($url);
$destination = sys_get_temp_dir().DIRECTORY_SEPARATOR.$destinationFile;
$cache_life = '120'; //caching time, in seconds
$ob_flush = false;

if($_GET['format'] === 'png'){
    header('Content-Type: image/x-png');
    $destination .= ".".$_GET['format'];
    $exec = "ffmpeg -i {$url} -f image2 -vframes 1 -y {$destination}";
    $destinationTmpFile = "{$global['systemRootPath']}view/img/OnAir.png";
}else if($_GET['format'] === 'jpg'){
    header('Content-Type: image/jpg');
    $destination .= ".".$_GET['format'];
    $exec = "ffmpeg -i {$url} -f image2 -vframes 1 -y {$destination}";
    $destinationTmpFile = "{$global['systemRootPath']}view/img/OnAir.jpg";
}else if($_GET['format'] === 'gif'){
    header('Content-Type: image/gif');
    $destination .= ".".$_GET['format'];    
    //Generate a palette:
    $ffmpegPallet ="ffmpeg -y -t 3 -i {$url} -vf fps=10,scale=320:-1:flags=lanczos,palettegen {$destination}palette.png";
    error_log("Exec get Image palette: {$ffmpeg}");
    $exec ="ffmpeg -y -t 3 -i {$url} -i {$destination}palette.png -filter_complex \"fps=10,scale=320:-1:flags=lanczos[x];[x][1:v]paletteuse\" {$destination}";
    $destinationTmpFile = "{$global['systemRootPath']}view/img/notfound.gif";
}else{
    error_log("ERROR Destination get Image {$_GET['format']} not suported");
    die();
}
if(!file_exists($destination)){
    $destination = $destinationTmpFile;
}
// flush old image then encode
echo file_get_contents($destination);
ob_flush();

$filemtime = @filemtime($destination);  // returns FALSE if file does not exist
if(!$filemtime || (time() - $filemtime >= $cache_life) || !empty($_GET['renew'])){
    if((time() - $filemtime >= ($cache_life*3))){
        unlink($destination) ;
    }
    if(!empty($ffmpegPallet)){
        exec($ffmpegPallet."   2>&1");
    }
    error_log("Exec get Image: {$exec}");
    exec($exec."   2>&1");
}
error_log("Destination get Image {$_GET['format']}: {$destination}");

die();