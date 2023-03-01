<?php

global $time_start;
$time_start = microtime(true);

function testTime($line) {
    global $time_start;
    $time_end = microtime(true);
    $time = $time_end - $time_start;
    if ($time > 1) {
        error_log(__FILE__ . " " . $line . 'Execution time : ' . $time . ' seconds');
    }
    $time_start = microtime(true);
}

testTime(__LINE__);

require_once dirname(__FILE__) . '/../videos/configuration.php';
require_once '../objects/Encoder.php';
header('Access-Control-Allow-Origin: *');
$url = base64_decode($_GET['base64Url']);

if(!isURLaVODVideo($url)){
    error_log("ERROR URL is not a VOD {$url}");
    die();
}

$destinationFile = md5($url);
$destination = _sys_get_temp_dir() . DIRECTORY_SEPARATOR . $destinationFile;
$destinationPallet = "{$destination}palette.png";
$cache_life = '600'; //caching time, in seconds
$ob_flush = false;

$type = 'video';
// get filetype
$parts = explode('?', $url);
if (preg_match('/\.mp3$/i', $parts[0])) {
    $type = 'audio';
}

$url = str_replace(array('"', "'"), array('', ''), $url);
$url = escapeshellarg($url);
error_log("getImageMP4 Starts: {$url}");

if ($type == 'audio') {
    //ffmpeg -i inputfile.mp3 -lavfi showspectrumpic=s=800x400:mode=separate spectrogram.png
    if ($_GET['format'] === 'jpg') {
        header('Content-Type: image/jpg');
        $destination .= "." . $_GET['format'];
    } else if ($_GET['format'] === 'gif') {
        header('Content-Type: image/gif');
        $destination .= "." . $_GET['format'];
    } else if ($_GET['format'] === 'webp') {
        // gif image has the double lifetime
        $cache_life *= 2;
        header('Content-Type: image/webp');
        $destination .= "." . $_GET['format'];
    } else {
        error_log("ERROR Destination get Image {$_GET['format']} not suported");
        die();
    }
    //$cmd = get_ffmpeg() . " -i {$url} -lavfi showspectrumpic=s=800x400:mode=separate {$destination}";
    //$cmd = get_ffmpeg() . " -i {$url} -filter_complex \"compand,showwavespic=s=1280x720\" -y {$destination}";
    $cmd = get_ffmpeg() . " -i {$url} -filter_complex \"compand,showwavespic=s=1280x720:colors=FFFFFF\" {$destination}";
    $cmd = removeUserAgentIfNotURL($cmd);
    exec($cmd);
    error_log("Create image from audio: {$cmd}");
} else if(preg_match('/(youtube.com|youtu.be|vimeo.com)/', $url)){
    require_once $global['systemRootPath'] . 'objects/Encoder.php';
    header('Content-Type: image/jpg');
    die(Encoder::getThumbsFromLink($url));
} else {

    testTime(__LINE__);
    if ($_GET['time'] > 600) {
        $_GET['time'] = 600;
    }
    $duration = Encoder::parseSecondsToDuration($_GET['time']);
    if ($_GET['format'] === 'jpg') {
        header('Content-Type: image/jpg');
        $destination .= "." . $_GET['format'];
        $exec = get_ffmpeg() . "  -ss {$duration} -i {$url} -f image2  "
        . "-vf ".getFFmpegScaleToForceOriginalAspectRatio(640, 360)." "
                . "-vframes 1 -y {$destination}";
    } else if ($_GET['format'] === 'gif') {
        // gif image has the double lifetime
        $cache_life *= 2;
        header('Content-Type: image/gif');
        $destination .= "." . $_GET['format'];
        //Generate a palette:
        $ffmpegPallet = get_ffmpeg() . " -y  -ss {$duration} -t 3 -i {$url} -vf fps=10,".getFFmpegScaleToForceOriginalAspectRatio(320, 180).":flags=lanczos,palettegen {$destinationPallet}";
        $exec = get_ffmpeg() . " -y  -ss {$duration} -t 3 -i {$url} -i {$destinationPallet} -filter_complex \"fps=10,".getFFmpegScaleToForceOriginalAspectRatio(320, 180).":flags=lanczos[x];[x][1:v]paletteuse\" {$destination}";
    } else if ($_GET['format'] === 'webp') {
        // gif image has the double lifetime
        $cache_life *= 2;
        header('Content-Type: image/webp');
        $destination .= "." . $_GET['format'];
        $exec = get_ffmpeg() . " -y -ss {$duration} -t 3 -i {$url} -vcodec libwebp -lossless 1 -vf fps=10,".getFFmpegScaleToForceOriginalAspectRatio(640, 360)." -q 60 -preset default -loop 0 -an -vsync 0 {$destination}";
        $destinationTmpFile = "{$global['systemRootPath']}view/img/notfound.gif";
    } else {
        error_log("ERROR Destination get Image {$_GET['format']} not suported");
        die();
    }
    
    $exec = removeUserAgentIfNotURL($exec);
    testTime(__LINE__);
    if (!empty($ffmpegPallet)) {
        $cmd = "{$ffmpegPallet}";
        $cmd = removeUserAgentIfNotURL($cmd);
        exec($cmd);
        error_log("Create Gif Pallet: {$cmd}");
        if (is_readable($destinationPallet)) {
            $cmdGif = "{$exec}";
            exec($cmdGif);
            error_log("Create Gif with Pallet: {$cmd}");
        } else {
            $cmdGif = get_ffmpeg() . " -ss {$duration} -y -t 3 -i {$url} -vf fps=10,".getFFmpegScaleToForceOriginalAspectRatio(320, 180)." {$destination}";
            $cmdGif = removeUserAgentIfNotURL($cmdGif);
            exec($cmdGif);
            error_log("Create Gif no Pallet: {$cmdGif}");
        }
    } else {
        $cmd = "{$exec}";
        exec($cmd);
        error_log("Exec get Image: {$cmd} ".__FILE__.' '. json_encode($_SERVER['REMOTE_ADDR']));
    }
}

echo url_get_contents($destination);

testTime(__LINE__);
die();
