<?php

$obj = new stdClass();
header('Content-Type: application/json');
require_once dirname(__FILE__) . '/../videos/configuration.php';
require_once $global['systemRootPath'] . 'objects/Encoder.php';
require_once $global['systemRootPath'] . 'objects/Login.php';
require_once $global['systemRootPath'] . 'objects/Streamer.php';


if (!Login::canUpload()) {
    $obj->msg = "This user can not upload files";
} else
if (!($streamers_id = Login::getStreamerId())) {
    $obj->msg = "There is no streamer site";
} else {
    // remove list parameter from
    $_POST['videoURL'] = preg_replace('~(\?|&)list=[^&]*~', '$1', $_POST['videoURL']);
    $_POST['videoURL'] = str_replace("?&", "?", $_POST['videoURL']);
    if (substr($_POST['videoURL'], -1) == '&') {
        $_POST['videoURL'] = substr($_POST['videoURL'], 0, -1);
    }

    $title = Encoder::getTitleFromLink($_POST['videoURL']);
    if (!$title) {
        $obj->error = "youtube-dl --force-ipv4 get title ERROR** " . print_r($output, true);
        $obj->type = "warning";
        $obj->title = "Sorry!";
        $obj->text = sprintf("We could not get the title of your video (%s) go to %s to fix it", $output[0], "<a href='https://github.com/DanielnetoDotCom/YouPHPTube/wiki/youdtube-dl-failed-to-extract-signature' class='btn btn-xm btn-default'>https://github.com/DanielnetoDotCom/YouPHPTube/wiki/youdtube-dl-failed-to-extract-signature</a>");
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
        $e->setFileURI($_POST['videoURL']);
        $e->setVideoDownloadedLink($_POST['videoURL']);
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
        }  else {
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
}
die(json_encode($obj));
