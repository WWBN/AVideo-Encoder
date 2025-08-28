<?php

$obj = new stdClass();
header('Content-Type: application/json');
require_once dirname(__FILE__) . '/../videos/configuration.php';
require_once $global['systemRootPath'] . 'objects/Encoder.php';
require_once $global['systemRootPath'] . 'objects/Login.php';
require_once $global['systemRootPath'] . 'objects/Streamer.php';
require_once $global['systemRootPath'] . 'objects/functions.php';

session_write_close();

function cleanFilename($filename) {
    // Remove BOM (Byte Order Mark) and other unwanted characters
    return preg_replace('/[^\w\d\-_\. ]/', '', preg_replace('/^\xEF\xBB\xBF/', '', $filename));
}

if (empty($_REQUEST['videoURL'])) {
    error_log("youtubeDl.json: videoURL is empty");
    $obj->msg = "videoURL is empty";
} else {
    if (!empty($_REQUEST['webSiteRootURL']) && !empty($_REQUEST['user']) && !empty($_REQUEST['pass']) && empty($_REQUEST['justLogin'])) {
        error_log("youtubeDl.json: Attempting login with provided credentials");
        Login::run($_REQUEST['user'], $_REQUEST['pass'], $_REQUEST['webSiteRootURL'], true);
    }

    if (!Login::canUpload()) {
        error_log("youtubeDl.json: User does not have upload permissions");
        $obj->msg = "This user can not upload files";
    } else {
        if (!($streamers_id = Login::getStreamerId())) {
            error_log("youtubeDl.json: No streamer site found");
            $obj->msg = "There is no streamer site";
        } else {
            error_log("youtubeDl.json: Streamer ID found: {$streamers_id}");
            // if it is a channel
            $rexexp = "/^(http(s)?:\\/\\/)?((w){3}.)?youtu(be|.be)?(\\.com)?\\/(channel|user).+/";
            if (preg_match($rexexp, $_REQUEST['videoURL'])) {
                error_log("youtubeDl.json: Processing YouTube channel URL");
                if (!Login::canBulkEncode()) {
                    $obj->msg = "Channel Import is disabled";
                    die(json_encode($obj));
                }
                $start = 0;
                $end = 100;
                if (!empty($_REQUEST['startIndex'])) {
                    $start = $current = intval($_REQUEST['startIndex']);
                }
                if (!empty($_REQUEST['endIndex'])) {
                    $end = intval($_REQUEST['endIndex']);
                }
                error_log("Processing Channel {$start} to {$end}");
                $list = Encoder::getReverseVideosJsonListFromLink($_REQUEST['videoURL'], Login::getStreamerId());
                $i = $start;
                for (; $i <= $end; $i++) {
                    if (is_object($list[$i]) && empty($list[$i]->id)) {
                        error_log(($i) . " Not Object " .  print_r($list[$i], true));
                        continue;
                    }
                    error_log(($i) . " Process Video {$list[$i]->id}");
                    $url = "https://www.youtube.com/watch?v={$list[$i]->url}";
                    $obj = addVideo($url, $streamers_id, $list[$i]->title);
                }
                error_log("Process Done Total {$i}");
            } else {
                if (isFTPURL($_REQUEST['videoURL'])) {
                    error_log("youtubeDl.json: Detected FTP URL");
                    require_once __DIR__ . '/../objects/FTPDownloader.php';
                    try {
                        $downloader = new FTPDownloader($_REQUEST['videoURL']);
                        $downloader->connect();
                        $downloader->queueFiles();
                        $downloader->close();
                    } catch (Exception $e) {
                        error_log("youtubeDl.json: FTPDownloader exception - " . $e->getMessage());
                        $obj->msg = "FTP download failed: " . $e->getMessage();
                    }
                } else {
                    error_log("youtubeDl.json: Adding video with URL: {$_REQUEST['videoURL']}");
                    $obj = addVideo($_REQUEST['videoURL'], $streamers_id, @$_REQUEST['videoTitle']);
                }
            }
        }
    }
}

if (isset($_REQUEST['videoTitle'])) {
    $_REQUEST['videoTitle'] = cleanFilename($_REQUEST['videoTitle']);
    error_log("youtubeDl.json: Cleaned video title: {$_REQUEST['videoTitle']}");
}

if (isset($_REQUEST['videoDownloadedLink'])) {
    $_REQUEST['videoDownloadedLink'] = cleanFilename($_REQUEST['videoDownloadedLink']);
    error_log("youtubeDl.json: Cleaned video downloaded link: {$_REQUEST['videoDownloadedLink']}");
}

// Add logging before calling queueFiles
error_log("youtubeDl.json: Calling queueFiles with videoDownloadedLink: {$_REQUEST['videoDownloadedLink']} and videoTitle: {$_REQUEST['videoTitle']}");

// Ensure $obj is populated before sending the response
if (empty((array)$obj)) {
    error_log("youtubeDl.json: Object is empty after queueFiles. Debugging...");
    error_log("youtubeDl.json: videoDownloadedLink: {$_REQUEST['videoDownloadedLink']}, videoTitle: {$_REQUEST['videoTitle']}");
    $obj->msg = "An unknown error occurred";
}

// Convert $obj to JSON for logging
$objAsJson = json_encode($obj);
if ($objAsJson === false) {
    $jsonError = json_last_error_msg();
    error_log("youtubeDl.json: Failed to encode object for logging - {$jsonError}");
    $objAsJson = "{\"error\":\"Failed to encode object\"}";
}

if (empty($doNotDie)) {
    $resp = json_encode($obj);
    if ($resp === false) {
        $jsonError = json_last_error_msg();
        error_log("youtubeDl.json: JSON encoding error - {$jsonError}");
        $resp = json_encode(["error" => "JSON encoding failed", "details" => $jsonError]);
    } else {
        error_log("youtubeDl.json: Sending response {$resp}");
    }
    echo $resp;
    exit;
}
