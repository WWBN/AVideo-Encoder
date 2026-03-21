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
$url = getExternalHttpUrlForShell($url, 'getImageMP4');
if ($url === false) {
    die();
}

if (!isURLaVODVideo($url)) {
    error_log("ERROR URL is not a VOD {$url}");
    die();
}

$destinationFile = md5($url);
$destination = _sys_get_temp_dir() . DIRECTORY_SEPARATOR . $destinationFile;
$destinationPallet = "{$destination}palette.png";
$destinationPalletEscaped = escapeshellarg($destinationPallet);
$cache_life = '600'; //caching time, in seconds
$ob_flush = false;

$type = 'video';
// get filetype
$parts = explode('?', $url);
if (preg_match('/\.mp3$/i', $parts[0])) {
    $type = 'audio';
}

$urlEscaped = escapeshellarg($url);
error_log("getImageMP4 Starts: {$urlEscaped}");

if ($type === 'audio') {
    //ffmpeg -i inputfile.mp3 -lavfi showspectrumpic=s=800x400:mode=separate spectrogram.png
    if ($_GET['format'] === 'jpg') {
        header('Content-Type: image/jpg');
        $destination .= "." . $_GET['format'];
    } elseif ($_GET['format'] === 'gif') {
        header('Content-Type: image/gif');
        $destination .= "." . $_GET['format'];
    } elseif ($_GET['format'] === 'webp') {
        // gif image has the double lifetime
        $cache_life *= 2;
        header('Content-Type: image/webp');
        $destination .= "." . $_GET['format'];
    } else {
        error_log("ERROR Destination get Image {$_GET['format']} not suported");
        die();
    }
    $destinationEscaped = escapeshellarg($destination);
    //$cmd = get_ffmpeg() . " -i {$url} -lavfi showspectrumpic=s=800x400:mode=separate {$destination}";
    //$cmd = get_ffmpeg() . " -i {$url} -filter_complex \"compand,showwavespic=s=1280x720\" -y {$destination}";
    $cmd = get_ffmpeg() . " -i {$urlEscaped} -filter_complex \"compand,showwavespic=s=1280x720:colors=FFFFFF\" {$destinationEscaped}";
    $cmd = removeUserAgentIfNotURL($cmd);
    exec($cmd);
    error_log("Create image from audio: {$cmd}");
} elseif (preg_match('/(youtube.com|youtu.be|vimeo.com|rumble.com)/', $url)) {
    require_once $global['systemRootPath'] . 'objects/Encoder.php';
    header('Content-Type: image/jpg');
    die(Encoder::getThumbsFromLink($url, Login::getStreamerId()));
} else {

    testTime(__LINE__);
    /*
    if ($_GET['time'] > 600) {
        $_GET['time'] = 600;
    }
     *
     */
    $duration = Encoder::parseSecondsToDuration(intval($_GET['time']));
    error_log("GetImageInTime duration=$duration time={$_GET['time']}");
    if ($_GET['format'] === 'jpg') {
        header('Content-Type: image/jpg');
        $destination .= "." . $_GET['format'];
        $destinationEscaped = escapeshellarg($destination);
        $exec = get_ffmpeg() . "  -ss {$duration} -i {$urlEscaped} -f image2  "
        . "-vf ".getFFmpegScaleToForceOriginalAspectRatio(640, 360)." "
                . "-vframes 1 -y {$destinationEscaped}";
    } elseif ($_GET['format'] === 'gif') {
        // gif image has the double lifetime
        $cache_life *= 2;
        header('Content-Type: image/gif');
        $destination .= "." . $_GET['format'];
        $destinationEscaped = escapeshellarg($destination);
        //Generate a palette:
        $ffmpegPallet = get_ffmpeg() . " -y  -ss {$duration} -t 3 -i {$urlEscaped} -vf fps=10,".getFFmpegScaleToForceOriginalAspectRatio(320, 180).":flags=lanczos,palettegen {$destinationPalletEscaped}";
        $exec = get_ffmpeg() . " -y  -ss {$duration} -t 3 -i {$urlEscaped} -i {$destinationPalletEscaped} -filter_complex \"fps=10,".getFFmpegScaleToForceOriginalAspectRatio(320, 180).":flags=lanczos[x];[x][1:v]paletteuse\" {$destinationEscaped}";
    } elseif ($_GET['format'] === 'webp') {
        // gif image has the double lifetime
        $cache_life *= 2;
        header('Content-Type: image/webp');
        $destination .= "." . $_GET['format'];
        $destinationEscaped = escapeshellarg($destination);
        $exec = get_ffmpeg() . " -y -ss {$duration} -t 3 -i {$urlEscaped} -vcodec libwebp -lossless 1 -vf fps=10,".getFFmpegScaleToForceOriginalAspectRatio(640, 360)." -q 60 -preset default -loop 0 -an -vsync 0 {$destinationEscaped}";
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
            $cmdGif = get_ffmpeg() . " -ss {$duration} -y -t 3 -i {$urlEscaped} -vf fps=10,".getFFmpegScaleToForceOriginalAspectRatio(320, 180)." {$destinationEscaped}";
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
