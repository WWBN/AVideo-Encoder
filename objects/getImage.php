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
function fileOlderThen($file, $ageInSeconds){
    $filemtime = @filemtime($file);  // returns FALSE if file does not exist
    if(!$filemtime || (time() - $filemtime >= $ageInSeconds)){
        return true;
    }
    return false;
}

if(empty($_GET['format'])){
    $_GET['format'] = 'jpg';
}

testTime(__LINE__);

require_once dirname(__FILE__) . '/../videos/configuration.php';
header('Access-Control-Allow-Origin: *');
$url = base64_decode($_GET['base64Url']);
$destinationFile = md5($url);
$destination = _sys_get_temp_dir().DIRECTORY_SEPARATOR.$destinationFile;
$destinationPallet = "{$destination}palette.png";
$cache_life = '600'; //caching time, in seconds
$ob_flush = false;

testTime(__LINE__);

if(preg_match('/(youtube.com|youtu.be|vimeo.com|rumble.com)/', $url)){
    require_once $global['systemRootPath'] . 'objects/Encoder.php';
    header('Content-Type: image/jpg');
    die(Encoder::getThumbsFromLink($url));
}else
if($_GET['format'] === 'png'){
    header('Content-Type: image/x-png');
    $destination .= ".".$_GET['format'];
    $exec = get_ffmpeg()." -i \"{$url}\" -f image2  "
    . "-vf ".getFFmpegScaleToForceOriginalAspectRatio(640, 360)." "
            . "-vframes 1 -y {$destination}";
    $destinationTmpFile = "{$global['systemRootPath']}view/img/OnAir.png";
}else if($_GET['format'] === 'jpg'){
    header('Content-Type: image/jpg');
    $destination .= ".".$_GET['format'];
    $exec = get_ffmpeg()." -i \"{$url}\" -f image2  "
    . "-vf ".getFFmpegScaleToForceOriginalAspectRatio(640, 360)." "
            . "-vframes 1 -y {$destination}";
    $destinationTmpFile = "{$global['systemRootPath']}view/img/OnAir.jpg";
}else if($_GET['format'] === 'gif'){
    // gif image has the double lifetime
    $cache_life*=2;
    header('Content-Type: image/gif');
    $destination .= ".".$_GET['format'];    
    //Generate a palette:
    $ffmpegPallet =get_ffmpeg()." -y -t 3 -i \"{$url}\" -vf fps=10,".getFFmpegScaleToForceOriginalAspectRatio(320, 180).":flags=lanczos,palettegen {$destinationPallet}";
    $ffmpegPallet = removeUserAgentIfNotURL($ffmpegPallet);
    $exec =get_ffmpeg()." -y -t 3 -i \"{$url}\" -i {$destinationPallet} -filter_complex \"fps=10,".getFFmpegScaleToForceOriginalAspectRatio(320, 180).":flags=lanczos[x];[x][1:v]paletteuse\" {$destination}";
    $destinationTmpFile = "{$global['systemRootPath']}view/img/notfound.gif";
}else if($_GET['format'] === 'webp'){
    // gif image has the double lifetime
    $cache_life*=2;
    header('Content-Type: image/webp');
    $destination .= ".".$_GET['format'];    
    $exec =get_ffmpeg()." -y -ss 3 -t 3 -i \"{$url}\" -vcodec libwebp -lossless 1 -vf fps=10,".getFFmpegScaleToForceOriginalAspectRatio(640, 360)." -q 60 -preset default -loop 0 -an -vsync 0 {$destination}";
    $destinationTmpFile = "{$global['systemRootPath']}view/img/notfound.gif";
}else{
    error_log("ERROR Destination get Image {$_GET['format']} not suported");
    die();
}

testTime(__LINE__);
$httpReferer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
if(!is_readable($destination)){
    echo url_get_contents($destinationTmpFile);
    error_log("getImage {$httpReferer} Destination get Temp Image from {$url} {$_GET['format']}: {$destinationTmpFile}");
}else{
    // flush old image then encode
    echo url_get_contents($destination);
    //error_log("getImage {$httpReferer} Destination get Image from {$url} {$_GET['format']}: {$destination}");
}

testTime(__LINE__);

ob_flush();

$exec = removeUserAgentIfNotURL($exec);
if(!file_exists($destination) || fileOlderThen($destination, $cache_life) || !empty($_GET['renew'])){
    if(!empty($ffmpegPallet)){               
        execAsync($ffmpegPallet);
        error_log("Create Gif Pallet: {$ffmpegPallet}");        
        if(is_readable($destinationPallet)){       
            execAsync($exec);
            error_log("Create Gif with Ppallet: {$exec}");
        }else{
            $cmdGif = get_ffmpeg()."  -y -t 3 -i \"{$url}\" -vf fps=10,".getFFmpegScaleToForceOriginalAspectRatio(320, 180)." {$destination}";
            $cmdGif = removeUserAgentIfNotURL($cmdGif);
            execAsync($cmdGif);
            error_log("Create Gif no Pallet: {$cmdGif}");
        }
    }else{
        execAsync($exec);
        error_log("Exec get Image: {$exec} ".__FILE__.' '. json_encode($_SERVER['REMOTE_ADDR']));
    }
}else{
    
}
testTime(__LINE__);
die();