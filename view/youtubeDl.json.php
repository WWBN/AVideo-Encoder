<?php

$obj = new stdClass();
header('Content-Type: application/json');
require_once dirname(__FILE__) . '/../videos/configuration.php';
require_once $global['systemRootPath'] . 'objects/Encoder.php';
require_once $global['systemRootPath'] . 'objects/Login.php';
require_once $global['systemRootPath'] . 'objects/Streamer.php';

session_write_close();

if(!empty($_GET['videoURL']) && empty($_POST['videoURL'])){
    $_POST['videoURL'] = $_GET['videoURL'];
}
if (!empty($_GET['webSiteRootURL']) && !empty($_GET['user']) && !empty($_GET['pass']) && empty($_GET['justLogin'])) {
    Login::run($_GET['user'], $_GET['pass'], $_GET['webSiteRootURL'], true);
}

function addVideo($link, $streamers_id, $title = "") {
    $obj = new stdClass();
    // remove list parameter from
    $link = preg_replace('~(\?|&)list=[^&]*~', '$1', $link);
    $link = str_replace("?&", "?", $link);
    if (substr($link, -1) == '&') {
        $link = substr($link, 0, -1);
    }
    if(empty($title)){
        $title = Encoder::getTitleFromLink($link);
    }
    if (!$title) {
        $obj->error = "youtube-dl --force-ipv4 get title ERROR** " . print_r($link, true);
        $obj->type = "warning";
        $obj->title = "Sorry!";
        $obj->text = sprintf("We could not get the title of your video (%s) go to %s to fix it", $link, "<a href='https://github.com/DanielnetoDotCom/YouPHPTube/wiki/youtube-dl-failed-to-extract-signature' class='btn btn-xm btn-default'>Update your Youtube-DL</a>");
        error_log("youtubeDl::addVideo We could not get the title ($title) of your video ($link)");
    } else {
        $obj->type = "success";
        $obj->title = "Congratulations!";
        $obj->text = sprintf("Your video (%s) is downloading", $title);

        $filename = preg_replace("/[^A-Za-z0-9]+/", "_", cleanString($title));
        $filename = uniqid("{$filename}_YPTuniqid_", true) . ".mp4";

        $s = new Streamer($streamers_id);

        $e = new Encoder("");
        $e->setStreamers_id($streamers_id);
        $e->setTitle($title);
        $e->setFileURI($link);
        $e->setVideoDownloadedLink($link);
        $e->setFilename($filename);
        $e->setStatus('queue');
        $e->setPriority($s->getPriority());
        //$e->setNotifyURL($global['YouPHPTubeURL'] . "youPHPTubeEncoder.json");

        $encoders_ids = array();

        if (!empty($_POST['audioOnly']) && $_POST['audioOnly'] !== 'false') {
            if (!empty($_POST['spectrum']) && $_POST['spectrum'] !== 'false') {
                $e->setFormats_idFromOrder(70); // video to spectrum [(6)MP4 to MP3] -> [(5)MP3 to spectrum] -> [(2)MP4 to webm] 
            } else {
                $e->setFormats_idFromOrder(71);
            }
        } else {
            $e->setFormats_idFromOrder(decideFormatOrder());
        }
        $obj = new stdClass();
        $f = new Format($e->getFormats_id());
        $format = $f->getExtension();
        $response = Encoder::sendFile('', 0, $format, $e);
        //var_dump($response);exit;
        if (!empty($response->response->video_id)) {
            $obj->videos_id = $response->response->video_id;
        }
        $e->setReturn_vars(json_encode($obj));
        $encoders_ids[] = $e->save();
    }
    return $obj;
}

if (!Login::canUpload()) {
    $obj->msg = "This user can not upload files";
} else {
    if (!($streamers_id = Login::getStreamerId())) {
        $obj->msg = "There is no streamer site";
    } else {
        // if it is a channel
        $rexexp = "/^(http(s)?:\/\/)?((w){3}.)?youtu(be|.be)?(\.com)?\/(channel|user).+/";
        if (preg_match($rexexp, $_POST['videoURL'])) {
            if (!Login::canBulkEncode()){
                $obj->msg = "Channel Import is disabled";
                die(json_encode($obj));
            }
            $start = 0;
            $end = 100;
            if(!empty($_POST['startIndex'])){
                $start = $current = intval($_POST['startIndex']);                
            }
            if(!empty($_POST['endIndex'])){
                $end = intval($_POST['endIndex']);
            }            
            error_log("Processing Channel {$start} to {$end}");
            $list = Encoder::getReverseVideosJsonListFromLink($_POST['videoURL']);
            $i=$start;
            for(; $i<=$end;$i++){
                if(is_object($list[$i]) && empty($list[$i]->id)){
                    error_log(($i)." Not Object ".  print_r($list[$i], true));
                    continue;
                }
                error_log(($i)." Process Video {$list[$i]->id}");
                $url = "https://www.youtube.com/watch?v={$list[$i]->url}";
                $obj = addVideo($url, $streamers_id, $list[$i]->title);
            }
            error_log("Process Done Total {$i}");
        } else {
            $obj = addVideo($_POST['videoURL'], $streamers_id);
        }
    }
}
die(json_encode($obj));
