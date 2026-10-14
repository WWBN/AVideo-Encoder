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
// FFmpeg runs from argument lists without a shell (execArgv()), see getImage.php.
$url = decodeBase64UrlParameter(isset($_GET['base64Url']) ? $_GET['base64Url'] : '');
if ($url === false) {
    error_log('getImageMP4: invalid base64Url rejected');
    die();
}
$url = getExternalHttpUrlForShell($url, 'getImageMP4');
if ($url === false) {
    die();
}
$format = isset($_GET['format']) && is_string($_GET['format']) ? $_GET['format'] : '';

if (!isURLaVODVideo($url)) {
    error_log("ERROR URL is not a VOD {$url}");
    die();
}

$destinationFile = md5($url);
$destination = _sys_get_temp_dir() . DIRECTORY_SEPARATOR . $destinationFile;
$destinationPallet = "{$destination}palette.png";
$cache_life = '600'; //caching time, in seconds
$ob_flush = false;
$ffmpegInput = array_merge(getFFmpegArgv(), getFFmpegRemoteInputArgv());

$type = 'video';
// get filetype
$parts = explode('?', $url);
if (preg_match('/\.mp3$/i', $parts[0])) {
    $type = 'audio';
}

error_log("getImageMP4 Starts: " . escapeshellarg($url));

if ($type === 'audio') {
    //ffmpeg -i inputfile.mp3 -lavfi showspectrumpic=s=800x400:mode=separate spectrogram.png
    if ($format === 'jpg') {
        header('Content-Type: image/jpg');
        $destination .= ".jpg";
    } elseif ($format === 'gif') {
        header('Content-Type: image/gif');
        $destination .= ".gif";
    } elseif ($format === 'webp') {
        // gif image has the double lifetime
        $cache_life *= 2;
        header('Content-Type: image/webp');
        $destination .= ".webp";
    } else {
        error_log("ERROR Destination get Image " . json_encode($format) . " not suported");
        die();
    }
    //$cmd = get_ffmpeg() . " -i {$url} -lavfi showspectrumpic=s=800x400:mode=separate {$destination}";
    //$cmd = get_ffmpeg() . " -i {$url} -filter_complex \"compand,showwavespic=s=1280x720\" -y {$destination}";
    $cmd = array_merge($ffmpegInput, array('-i', $url, '-filter_complex', 'compand,showwavespic=s=1280x720:colors=FFFFFF', $destination));
    execArgv($cmd);
    error_log("Create image from audio: " . argvToLogString($cmd));
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
    error_log("GetImageInTime duration=$duration time=" . intval($_GET['time']));
    if ($format === 'jpg') {
        header('Content-Type: image/jpg');
        $destination .= ".jpg";
        $exec = array_merge($ffmpegInput, array('-ss', $duration, '-i', $url, '-f', 'image2', '-vf', getFFmpegScaleToForceOriginalAspectRatio(640, 360), '-vframes', '1', '-y', $destination));
    } elseif ($format === 'gif') {
        // gif image has the double lifetime
        $cache_life *= 2;
        header('Content-Type: image/gif');
        $destination .= ".gif";
        //Generate a palette:
        $ffmpegPallet = array_merge($ffmpegInput, array('-y', '-ss', $duration, '-t', '3', '-i', $url, '-vf', 'fps=10,' . getFFmpegScaleToForceOriginalAspectRatio(320, 180) . ':flags=lanczos,palettegen', $destinationPallet));
        $exec = array_merge($ffmpegInput, array('-y', '-ss', $duration, '-t', '3', '-i', $url, '-i', $destinationPallet, '-filter_complex', 'fps=10,' . getFFmpegScaleToForceOriginalAspectRatio(320, 180) . ':flags=lanczos[x];[x][1:v]paletteuse', $destination));
    } elseif ($format === 'webp') {
        // gif image has the double lifetime
        $cache_life *= 2;
        header('Content-Type: image/webp');
        $destination .= ".webp";
        $exec = array_merge($ffmpegInput, array('-y', '-ss', $duration, '-t', '3', '-i', $url, '-vcodec', 'libwebp', '-lossless', '1', '-vf', 'fps=10,' . getFFmpegScaleToForceOriginalAspectRatio(640, 360), '-q', '60', '-preset', 'default', '-loop', '0', '-an', '-vsync', '0', $destination));
        $destinationTmpFile = "{$global['systemRootPath']}view/img/notfound.gif";
    } else {
        error_log("ERROR Destination get Image " . json_encode($format) . " not suported");
        die();
    }

    testTime(__LINE__);
    if (!empty($ffmpegPallet)) {
        execArgv($ffmpegPallet);
        error_log("Create Gif Pallet: " . argvToLogString($ffmpegPallet));
        if (is_readable($destinationPallet)) {
            execArgv($exec);
            error_log("Create Gif with Pallet: " . argvToLogString($exec));
        } else {
            $cmdGif = array_merge($ffmpegInput, array('-ss', $duration, '-y', '-t', '3', '-i', $url, '-vf', 'fps=10,' . getFFmpegScaleToForceOriginalAspectRatio(320, 180), $destination));
            execArgv($cmdGif);
            error_log("Create Gif no Pallet: " . argvToLogString($cmdGif));
        }
    } else {
        execArgv($exec);
        error_log("Exec get Image: " . argvToLogString($exec) . ' ' . __FILE__ . ' ' . json_encode($_SERVER['REMOTE_ADDR']));
    }
}

echo url_get_contents($destination);

testTime(__LINE__);
die();
