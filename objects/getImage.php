<?php
function fileOlderThen($file, $ageInSeconds){
    $filemtime = @filemtime($file);  // returns FALSE if file does not exist
    if(!$filemtime || (time() - $filemtime >= $ageInSeconds)){
        return true;
    }
    return false;
}

require_once dirname(__FILE__) . '/../videos/configuration.php';
header('Access-Control-Allow-Origin: *');
$url = base64_decode($_GET['base64Url']);
$destinationFile = md5($url);
$destination = sys_get_temp_dir().DIRECTORY_SEPARATOR.$destinationFile;
$destinationPallet = "{$destination}palette.png";
$cache_life = '600'; //caching time, in seconds
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
    $ffmpegPallet ="ffmpeg -y -t 3 -i {$url} -vf fps=10,scale=320:-1:flags=lanczos,palettegen {$destinationPallet}";
    $exec ="ffmpeg -y -t 3 -i {$url} -i {$destination}palette.png -filter_complex \"fps=10,scale=320:-1:flags=lanczos[x];[x][1:v]paletteuse\" {$destination}";
    $destinationTmpFile = "{$global['systemRootPath']}view/img/notfound.gif";
}else{
    error_log("ERROR Destination get Image {$_GET['format']} not suported");
    die();
}

if(!is_readable($destination)){
    echo file_get_contents($destinationTmpFile);
    error_log("Destination get Temp Image {$_GET['format']}: {$destinationTmpFile}");
}else{
    // flush old image then encode
    echo file_get_contents($destination);
    error_log("Destination get Image {$_GET['format']}: {$destination}");
}
ob_flush();

if(!file_exists($destination) || fileOlderThen($destination, $cache_life) || !empty($_GET['renew'])){
    if(!empty($ffmpegPallet) && (!file_exists($destinationPallet) || is_readable($destinationPallet))){
        $cmd = "{$ffmpegPallet} && {$exec} &> /dev/null &";
    }else{
        $cmd = "{$exec} &> /dev/null &";
    }
    exec($cmd);
    error_log("Exec get Image: {$cmd}");
}else{
    
}

die();