<?php
header('Content-Type: application/json');
require_once dirname(__FILE__) . '/../videos/configuration.php';
require_once '../objects/Encoder.php';
require_once '../objects/Login.php';

if(empty($_POST['fileURI'])){
    die("File URI Not found");
}

if(!empty($_POST['user']) && !empty($_POST['pass']) && !empty($_POST['notifyURL'])){
    error_log("Sent Login variables try to login");
    Login::run($_POST['user'], $_POST['pass'], $_POST['notifyURL'], true);
}

$e = new Encoder(@$_POST['id']);
if(empty($e->getId())){
    if(!Login::canUpload()){
        error_log("This user can not upload files");
        exit;
    }
   if (!($streamers_id = Login::getStreamerId())) {
        error_log("There is no streamer site");
        exit;
    }
    $e->setStreamers_id($streamers_id);
    $s = new Streamer($streamers_id);
    
    $path_parts = pathinfo($_POST['fileURI']);
    if(empty($_POST['filename'])){
        $_POST['filename'] = $path_parts['basename'];
    }
    
    $e->setFileURI($_POST['fileURI']);
    $e->setFilename($_POST['filename']);
    $e->setTitle($path_parts['filename']);
    $e->setPriority($s->getPriority());
    
    if (!empty($_POST['audioOnly']) && $_POST['audioOnly']!=='false') {
        if (!empty($_POST['spectrum']) && $_POST['spectrum']!=='false') {
            $e->setFormats_idFromOrder(70); // video to spectrum [(6)MP4 to MP3] -> [(5)MP3 to spectrum] -> [(2)MP4 to webm] 
        } else {
            $e->setFormats_idFromOrder(71);
        }
    } else {
        $e->setFormats_idFromOrder(decideFormatOrder());
    }
    $obj = new stdClass();
    $obj->videos_id = @$_POST['videos_id'];
    // notify streamer if need
    if(empty($obj->videos_id)){
        $f = new Format($e->getFormats_id());
        $format = $f->getExtension();
        
        $response = Encoder::sendFile('', 0, $format, $e);
        //var_dump($response);exit;
        if(!empty($response->response->video_id)){
            $obj->videos_id = $response->response->video_id;
        }
    }
    $e->setReturn_vars(json_encode($obj));
    $id = $e->save();
}else{
    $e->setStatus('queue');
    $id = $e->save();
}
// start queue now
$cmd = PHP_BINDIR."/php -f {$global['systemRootPath']}view/run.php > /dev/null 2>/dev/null &";
//echo "** executing command {$cmd}\n";
exec($cmd);
echo json_encode($id);