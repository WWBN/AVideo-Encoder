<?php

$scale_width = 1280;
$scale_height = 720;
//$ffmpegParameters = " -preset veryfast -vcodec h264 -acodec aac -strict -2 -max_muxing_queue_size 1024 -y -s {$scale_width}x{$scale_height}  -ar 44100";
$ffmpegParameters = " -c:v libx264 -preset medium -b:v 3000k -maxrate 3000k -bufsize 6000k -vf \"scale={$scale_width}:{$scale_height},format=yuv420p\" -g 50 -c:a aac -b:a 128k -ac 2 -ar 44100 ";
//$ffmpegParametersRTMP = "  -c copy ";
//$ffmpegParametersRTMP = " -c:v libx264 -b:v 3000k -maxrate 3000k -bufsize 6000k -g 50 -c:a aac -b:a 128k -ac 2 -s {$scale_width}x{$scale_height}  -ar 44100  ";
$ffmpegParametersRTMP = " -c:v libx264 -b:v 3000k -maxrate 3000k -bufsize 6000k -g 50 -c:a aac -b:a 128k -ac 2 -s {$scale_width}x{$scale_height}  -ar 44100  ";

$outputExtension = "flv";


//$ffmpegParameters = " -preset veryfast  -vcodec h264 -acodec aac -strict -2 -max_muxing_queue_size 1024 -y -s {$scale_width}x{$scale_height}   -r 10 -ab 24k -ar 22050 -bsf:v h264_mp4toannexb -maxrate 750k -bufsize 3000k   -tune zerolatency ";
//$ffmpegParameters = "  -strict -2  -max_muxing_queue_size 1024 -y -s {$scale_width}x{$scale_height}  -tune zerolatency ";

$complexFilter1 = ' [{$counter}]setdar=16/9,scale='.$scale_width.':'.$scale_height.',fps=30[{$counter}:v]; ';
$complexFilter2 = ' [{$counter}:v] [{$counter}:a] ';
$recreateAllVideos = 1;
$directTransmit = 1;

// Make sure you set hls_continuous on; on nginx.conf

require_once dirname(__FILE__) . '/../videos/configuration.php';
require_once $global['systemRootPath'] . 'objects/Encoder.php';
require_once $global['systemRootPath'] . 'objects/Login.php';
require_once $global['systemRootPath'] . 'objects/Streamer.php';

$domain = get_domain($_REQUEST['webSiteRootURL']);

$obj = new stdClass();
$obj->error = true;
$obj->msg = "";
$obj->time = time();

if (empty($domain)) {
    $obj->msg = "Domain is empty ({$_REQUEST['webSiteRootURL']})";
    die($obj->msg);
}

$countdownTotalDurationSeconds = 3610; // 1 hour and 10 sec
//$countdownBegin = 10;
$countdownBegin = 0;
$countdownVideoFile = $global['systemRootPath'] . 'view/img/countdown.mp4';

$videosListToLivePath = $global['systemRootPath'] . 'videos/videosListToLive/';
$dir = "{$videosListToLivePath}{$domain}/";
make_path($dir);

// clear all get 
foreach ($_REQUEST as $key => $value) {
    if(empty($_REQUEST[$value])){
        continue;
    }
    $_REQUEST[$value] = str_replace('/[^a-z0-9.:/-]/i', '', trim($_REQUEST[$value]));
}

$obj->playlists_id = intval($_REQUEST['playlists_id']);
$obj->live_servers_id = intval(@$_REQUEST['live_servers_id']);
$APISecret = $_REQUEST['APISecret'];
$obj->webSiteRootURL = $_REQUEST['webSiteRootURL'];

if (empty($obj->playlists_id)) {
    $obj->msg = "playlists_id is empty";
    die(json_encode($obj));
}

if (empty($APISecret)) {
    $obj->msg = "APISecret is empty";
    die(json_encode($obj));
}

if (!empty($_REQUEST['webSiteRootURL']) && !empty($_REQUEST['user']) && !empty($_REQUEST['pass']) && empty($_REQUEST['justLogin'])) {
    error_log("videosListToLive: Login::run");
    Login::run($_REQUEST['user'], $_REQUEST['pass'], $_REQUEST['webSiteRootURL'], true);
}
if (!Login::isLogged()) {
    $obj->msg = "Could not login";
    die(json_encode($obj));
}
if (!Login::canStream()) {
    $obj->msg = "cannot stream";
    die(json_encode($obj));
}

ini_set('max_execution_time', 0);

//$logFile = $global['systemRootPath'] . "videos/videoListToLive_" . Login::getStreamerId() . "_{$_REQUEST['playlists_id']}.log";
$logFile = $global['systemRootPath'] . "videos/videoListToLive.log";

_log("Start: log file: {$logFile}");

$warermark = createWaterMark($_REQUEST['webSiteRootURL'], $dir);
_log("createWaterMark {$warermark}");

$apiURL = Login::getStreamerURL() . "plugin/API/get.json.php?APIName=video_from_program&playlists_id={$_REQUEST['playlists_id']}&APISecret={$_REQUEST['APISecret']}";
_log("API request: {$apiURL}");

$json = json_decode(url_get_contents($apiURL));
if (!empty($json->error)) {
    $obj->msg = "API response: Error: {$json->message}";
    _log($obj->msg);
    die(json_encode($obj));
}
if (empty($json->response->videos)) {
    $obj->msg = "API response: no videos to show";
    _log($obj->msg);
    die(json_encode($obj));
}
//var_dump($json);
$counter = 0;
$videos = array();

$ffmpegInputs = array();
$ffmpegFilters1 = array();
$ffmpegFilters2 = array();
$videos = array();

$timer = new stdClass();
$timer->videos_id = -1;
$timer->filename = "timer";
$timer->duration = secondsToVideoTime($countdownBegin);
$timer->duration_seconds = parseDurationToSeconds($timer->duration);
$timer->title = "timer";
$timer->desc = "";
$timer->category = "";

_log("we will process videos now total " . count($json->response->videos));

$timeStart = microtime(true);
foreach ($json->response->videos as $value) {
    if (empty($value->path)) {
        _log("ERROR the path of the video is empty " . json_encode($value));
        continue;
    }

    $timeStartVideo = microtime(true);
    $outputFile = "{$dir}video_{$value->videos_id}.{$outputExtension}";
    _log("Processing video ($counter) ({$value->videos_id}) ({$value->title}) ($outputFile)");
    if ($directTransmit && !isAudio($value->path)) {
        $ffmpegInputs[] = " -re -i \"{$value->path}\" ";
        eval('$ffmpegFilters1[] = " ' . $complexFilter1 . ' ";');
        eval('$ffmpegFilters2[] = " ' . $complexFilter2 . ' ";');
        $videos[] = $value;
        $counter++;
    } else if (!empty($recreateAllVideos) || !file_exists($outputFile)) {
        if (isAudio($value->path)) {
            $cmd = get_ffmpeg() . " -i \"{$value->path}\" -filter_complex '[0:a]showwaves=s={$scale_width}x{$scale_height}:mode=line,format=yuv420p[v]' -map '[v]' -map 0:a "
                    . " {$ffmpegParameters} -y {$outputFile} ";
        } else {
            $cmd = get_ffmpeg() . " -i \"{$value->path}\" "
                    . " {$ffmpegParameters} -y {$outputFile} ";
        }
        if (__exec($cmd)) {
            $ffmpegInputs[] = " -re -i \"{$outputFile}\" ";
            eval('$ffmpegFilters1[] = " ' . $complexFilter1 . ' ";');
            eval('$ffmpegFilters2[] = " ' . $complexFilter2 . ' ";');
            $videos[] = $value;
            $counter++;
        } else {
            _log("ERROR on video ($counter) ({$value->path})");
        }
    } else {
        $ffmpegInputs[] = " -re -i \"{$outputFile}\" ";
        eval('$ffmpegFilters1[] = " ' . $complexFilter1 . ' ";');
        eval('$ffmpegFilters2[] = " ' . $complexFilter2 . ' ";');
        $videos[] = $value;
        $counter++;
    }
    _log("** Processing video tooks " . secondsToVideoTime(microtime(true) - $timeStartVideo, 3) . "");
}

_log("** The conversion process tooks " . secondsToVideoTime(microtime(true) - $timeStart, 3) . "");

if (empty($ffmpegInputs)) {
    $obj->msg = "No valid inputs found";
    _log($obj->msg);
    die(json_encode($obj));
}

if (!empty($countdownBegin)) {
    $outputFileCountDown = "{$videosListToLivePath}countdown{$countdownBegin}.{$outputExtension}";

    if (!is_file($outputFileCountDown)) {
        _log("get the count down");
        $start = parseSecondsToDuration($countdownTotalDurationSeconds - $countdownBegin);
        $duration = parseSecondsToDuration($countdownBegin);
        $cmd = get_ffmpeg() . " -i \"{$countdownVideoFile}\"  -ss {$start} -t {$duration}  {$ffmpegParameters} {$outputFileCountDown} ";
        if (__exec($cmd)) {
            array_unshift($ffmpegInputs, " -re -i \"{$outputFileCountDown}\" ");
            eval('$ffmpegFilters1[] = " ' . $complexFilter1 . ' ";');
            eval('$ffmpegFilters2[] = " ' . $complexFilter2 . ' ";');
            $counter++;
            $ffmpegInputs[] = " -re -i \"{$outputFileCountDown}\" ";
            eval('$ffmpegFilters1[] = " ' . $complexFilter1 . ' ";');
            eval('$ffmpegFilters2[] = " ' . $complexFilter2 . ' ";');
            array_unshift($videos, $timer);
            $videos[] = $timer;
            $counter++;
        }
    } else {
        array_unshift($ffmpegInputs, " -re -i \"{$outputFileCountDown}\" ");
        eval('$ffmpegFilters1[] = " ' . $complexFilter1 . ' ";');
        eval('$ffmpegFilters2[] = " ' . $complexFilter2 . ' ";');
        $counter++;
        $ffmpegInputs[] = " -re -i \"{$outputFileCountDown}\" ";
        eval('$ffmpegFilters1[] = " ' . $complexFilter1 . ' ";');
        eval('$ffmpegFilters2[] = " ' . $complexFilter2 . ' ";');
        array_unshift($videos, $timer);
        $videos[] = $timer;
        $counter++;
    }
}


$timeLiveStart = microtime(true);
_log("Live stream it now");

$cmd = get_ffmpeg() . " " . implode(" ", $ffmpegInputs) . " ";
if (!file_exists($warermark)) {
    $cmd .= " -filter_complex \"" . implode(" ", $ffmpegFilters1) . " " . implode(" ", $ffmpegFilters2) . " concat=n={$counter}:v=1:a=1:unsafe=1 [v] [a]\" -map \"[v]\" -map \"[a]\" ";
} else {
    $cmd .= " -re -i '{$warermark}' ";
    $cmd .= " -filter_complex \"" . implode(" ", $ffmpegFilters1) . " " . implode(" ", $ffmpegFilters2) . " concat=n={$counter}:v=1:a=1:unsafe=1 [vv] [a]; [vv][{$counter}:v]overlay=W-w-0:0[v]\" -map \"[v]\" -map \"[a]\" ";
}

$rtmpURL = "{$_SESSION['login']->streamServerURL}/{$_SESSION['login']->streamKey}_{$json->response->playlists_id}";

$cmd .= " {$ffmpegParametersRTMP} -f flv {$rtmpURL}";


_log("Create EPG");
$channel = new stdClass();
$channel->id = $json->response->users_id . "." . $domain;
$channel->users_id = $json->response->users_id;
$channel->name = $json->response->channel_name;
$channel->icon = $json->response->channel_photo;
$channel->bg = $json->response->channel_bg;
$channel->link = $json->response->channel_link;
$channel->start = time();
$channel->start_date = date("Y-m-d H:i:s", $channel->start);
$channel->link = "{$_REQUEST['webSiteRootURL']}live/{$obj->live_servers_id}/{$channel->name}?playlists_id_live={$json->response->playlists_id}";
$channel->playlists_id = $json->response->playlists_id;
$channel->key = $_SESSION['login']->streamKey;
$channel->live_servers_id = $obj->live_servers_id;
$channel->embedlink = "{$channel->link}&embed=1";
$channel->programme = array();

foreach ($videos as $value) {
    $programme = new stdClass();
    $programme->channel = $channel->id;
    $programme->id = $json->response->playlists_id;
    $programme->videos_id = $value->id;
    $programme->filename = $value->filename;
    $programme->duration = $value->duration;
    $programme->duration_seconds = parseDurationToSeconds($programme->duration);
    $programme->title = $value->title;
    $programme->desc = $value->description;
    $programme->date = date("Ymd", $programme->start);
    $programme->category = $value->category->name;
    $channel->programme[] = $programme;
}
$epgdir = "{$dir}epg/";
make_path($epgdir);
$epgfile = "{$epgdir}channel_{$channel->users_id}_playlist_{$json->response->playlists_id}.json";
if (file_exists($epgfile)) {
    _log("EPG File already exists");
    $jsonEPG = json_decode(file_get_contents($epgfile));
    if (!empty($jsonEPG->pid)) {
        _log("Old PID found {$jsonEPG->pid}");
        $cmdPid = "kill -9 {$jsonEPG->pid}";
        __exec($cmdPid);

        $liveDir = "/HLS/live/{$_SESSION['login']->streamKey}_{$json->response->playlists_id}";
        if (is_dir($liveDir)) {
            __exec("rm -R $liveDir");
        }
    }
}

$channel->pid = __exec($cmd, true);
_log("FFMPEG executed async PID: {$channel->pid}");

if (!empty($channel->programme)) {
    file_put_contents($epgfile, json_encode($channel));
    _log("EPG saved on {$epgfile}");
}

_log("** The live process tooks " . secondsToVideoTime(microtime(true) - $timeLiveStart, 3) . "");

_log("End ");

_log("** All process tooks " . secondsToVideoTime(microtime(true) - $timeStart, 3) . "");
ob_flush();
$obj->error = false;

die(json_encode($obj));

function __exec($cmd, $async = false) {
    _log($cmd);
    ob_flush();
    if (!$async) {
        exec($cmd . " 2>&1", $output, $return_val);
        if ($return_val !== 0) {
            _log("Error: " . json_encode($output));
            return false;
        }
        return true;
    } else {
        return exec($cmd . ' > /dev/null 2>&1 & echo $!; ', $output);
    }
}

function _log($msg) {
    global $logFile;
    error_log("videoListToLive: " . $msg);
    //echo "<hr>" . $msg . "<br>" . PHP_EOL;
    return file_put_contents($logFile, "[" . date("Y-m-d H:i:s") . "] " . $msg . PHP_EOL, FILE_APPEND);
}

function isAudio($source) {
    return empty(isVideo($source));
}

function isVideo($source) {
    $cmd = get_ffprobe()." -i \"{$source}\" -show_streams -select_streams v:0 -show_entries stream=width,height -loglevel error";
    exec($cmd . " 2>&1", $output, $return_val);
    $return = array("width" => 0, "height" => 0);
    if ($return_val !== 0) {
        _log("isVideo Error: " . json_encode($output));
    } else {
        foreach ($output as $value) {
            if (preg_match('/width=([0-9]+)/', $value, $matches)) {
                if (!empty($matches[1])) {
                    $return["width"] = intval($matches[1]);
                }
            } else
            if (preg_match('/height=([0-9]+)/', $value, $matches)) {
                if (!empty($matches[1])) {
                    $return["height"] = intval($matches[1]);
                }
            }
            if (!empty($return["width"]) && !empty($return["height"])) {
                _log("isVideo Success: " . json_encode($return));
                return $return;
            }
        }
    }
    return false;
}

function createWaterMark($webSiteRootURL, $path) {
    global $scale_width,$scale_height;
    $imgPath = "{$path}watermark.png";
    if (file_exists($imgPath)) {
        //return $imgPath;
    }
    $backGround = imagecreatetruecolor($scale_width,$scale_height);
    imagesavealpha($backGround, true);
    $color = imagecolorallocatealpha($backGround, 0, 0, 0, 127);
    imagefill($backGround, 0, 0, $color);

    $logo = imagecreatefrompng("{$webSiteRootURL}videos/favicon.png");
    //$logo = imagecreatefrompng("{$webSiteRootURL}videos/userPhoto/logo.png"); 
    $opacity = 0.4;
    imagealphablending($logo, false); // imagesavealpha can only be used by doing this for some reason
    imagesavealpha($logo, true); // this one helps you keep the alpha. 
    $transparency = 1 - $opacity;
    imagefilter($logo, IMG_FILTER_COLORIZE, 0, 0, 0, 127 * $transparency); // the fourth parameter is alpha   

    imagecopyresized($backGround, $logo, 1220, 5, 0, 0, 50, 50, 180, 180);
    //imagecopy($backGround, $logo, 1000, 25, 0, 0, 250, 70);  

    imagepng($backGround, $imgPath);
    return $imgPath;
}
