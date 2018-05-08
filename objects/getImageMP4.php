<?php
global $time_start;
$time_start = microtime(true);
function testTime($line){
    global $time_start;
    $time_end = microtime(true);
    $time = $time_end - $time_start;
    if ($time > 1) {
        error_log(__FILE__." ".$line . 'Execution time : ' . $time . ' seconds');
    }
    $time_start = microtime(true);
}

testTime(__LINE__);

require_once dirname(__FILE__) . '/../videos/configuration.php';
require_once '../objects/Encoder.php';
header('Access-Control-Allow-Origin: *');
$url = base64_decode($_GET['base64Url']);
$destinationFile = md5($url);
$destination = sys_get_temp_dir().DIRECTORY_SEPARATOR.$destinationFile;
$destinationPallet = "{$destination}palette.png";
$cache_life = '600'; //caching time, in seconds
$ob_flush = false;

testTime(__LINE__);
$duration = Encoder::parseSecondsToDuration($_GET['time']);
if($_GET['format'] === 'jpg'){
    header('Content-Type: image/jpg');
    $destination .= ".".$_GET['format'];
    $exec = "ffmpeg -i {$url} -ss {$duration} -f image2  -s 400x225 -vframes 1 -y {$destination}";
}else if($_GET['format'] === 'gif'){
    // gif image has the double lifetime
    $cache_life*=2;
    header('Content-Type: image/gif');
    $destination .= ".".$_GET['format'];    
    //Generate a palette:
    $ffmpegPallet ="ffmpeg -y  -ss {$duration} -t 3 -i {$url} -vf fps=10,scale=320:-1:flags=lanczos,palettegen {$destinationPallet}";
    $exec ="ffmpeg -y  -ss {$duration} -t 3 -i {$url} -i {$destinationPallet} -filter_complex \"fps=10,scale=320:-1:flags=lanczos[x];[x][1:v]paletteuse\" {$destination}";
}else{
    error_log("ERROR Destination get Image {$_GET['format']} not suported");
    die();
}

testTime(__LINE__);
if(!empty($ffmpegPallet)){        
    $cmd = "{$ffmpegPallet}";        
    exec($cmd);
    error_log("Create Gif Pallet: {$cmd}");        
    if(is_readable($destinationPallet)){
        $cmdGif = "{$exec}";
        exec($cmdGif);
        error_log("Create Gif with Pallet: {$cmd}");
    }else{
        $cmdGif = "ffmpeg -ss {$duration} -y -t 3 -i {$url} -vf fps=10,scale=320:-1 {$destination}";
        exec($cmdGif);
        error_log("Create Gif no Pallet: {$cmd}");
    }
}else{
    $cmd = "{$exec}";
    exec($cmd);
    error_log("Exec get Image: {$cmd}");
}


echo url_get_contents($destination);

testTime(__LINE__);
die();