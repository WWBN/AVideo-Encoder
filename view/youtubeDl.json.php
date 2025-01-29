<?php

$obj = new stdClass();
header('Content-Type: application/json');
require_once dirname(__FILE__) . '/../videos/configuration.php';
require_once $global['systemRootPath'] . 'objects/Encoder.php';
require_once $global['systemRootPath'] . 'objects/Login.php';
require_once $global['systemRootPath'] . 'objects/Streamer.php';
require_once $global['systemRootPath'] . 'objects/functions.php';

session_write_close();

if (empty($_REQUEST['videoURL'])) {
    $obj->msg = "videoURL is empty";
} else {
    if (!empty($_REQUEST['webSiteRootURL']) && !empty($_REQUEST['user']) && !empty($_REQUEST['pass']) && empty($_REQUEST['justLogin'])) {
        error_log("youtubeDl.json: Login::run");
        Login::run($_REQUEST['user'], $_REQUEST['pass'], $_REQUEST['webSiteRootURL'], true);
    }

    if (!Login::canUpload()) {
        $obj->msg = "This user can not upload files";
    } else {
        if (!($streamers_id = Login::getStreamerId())) {
            $obj->msg = "There is no streamer site";
        } else {
            // if it is a channel
            $rexexp = "/^(http(s)?:\/\/)?((w){3}.)?youtu(be|.be)?(\.com)?\/(channel|user).+/";
            if (preg_match($rexexp, $_REQUEST['videoURL'])) {
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
                    require_once __DIR__ . '/../objects/FTPDownloader.php';
                    try {
                        $downloader = new FTPDownloader($_REQUEST['videoURL']);
                        $downloader->connect();
                        $downloader->queueFiles();
                        $downloader->close();
                    } catch (Exception $e) {
                        echo "Error: " . $e->getMessage() . "\n";
                    }
                } else {
                    $obj = addVideo($_REQUEST['videoURL'], $streamers_id, @$_REQUEST['videoTitle']);
                }
            }
        }
    }
}

if (empty($doNotDie)) {
    echo (json_encode($obj));
    exit;
}
