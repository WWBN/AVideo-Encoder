<?php
header('Content-Type: application/json');
require_once dirname(__FILE__) . '/../videos/configuration.php';
require_once '../objects/Encoder.php';
require_once '../objects/Login.php';

if(empty($_POST['fileURI'])){
    die("File URI Not found");
}

$e = new Encoder(@$_POST['id']);
if(empty($e->getId())){
    if(!Login::canUpload()){
        die("This user can not upload files");
    }
   if (!($streamers_id = Login::getStreamerId())) {
        die("There is no streamer site");
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
            $e->setFormats_id(7); // video to spectrum [(6)MP4 to MP3] -> [(5)MP3 to spectrum] -> [(2)MP4 to webm] 
        } else {
            $e->setFormats_id(8);
        }
    } else if(!empty($_POST['format'])){
        $e->setFormats_id($_POST['format']);
    } else if (empty($_POST['webm']) || $_POST['webm'] === 'false') {
        // mp4 only
        if(
                !empty($_POST['inputLow']) && $_POST['inputLow'] !== 'false' &&
                !empty($_POST['inputSD']) && $_POST['inputSD'] !== 'false' &&
                !empty($_POST['inputHD']) && $_POST['inputHD'] !== 'false'                
                ){ // all resolutions
            $e->setFormats_id(29);
        }else if(
                !empty($_POST['inputLow']) && $_POST['inputLow'] !== 'false' &&
                !empty($_POST['inputHD']) && $_POST['inputHD'] !== 'false'                
                ){ 
            $e->setFormats_id(28);
        }else if(
                !empty($_POST['inputSD']) && $_POST['inputSD'] !== 'false' &&
                !empty($_POST['inputHD']) && $_POST['inputHD'] !== 'false'                
                ){ 
            $e->setFormats_id(27);
        }else if(
                !empty($_POST['inputLow']) && $_POST['inputLow'] !== 'false' &&
                !empty($_POST['inputSD']) && $_POST['inputSD'] !== 'false'              
                ){ 
            $e->setFormats_id(26);
        }else if(
                !empty($_POST['inputHD']) && $_POST['inputHD'] !== 'false'                
                ){ 
            $e->setFormats_id(25);
        }else if(
                !empty($_POST['inputSD']) && $_POST['inputSD'] !== 'false'             
                ){ 
            $e->setFormats_id(24);
        }else{ 
            $e->setFormats_id(23);
        }
    }else{
        // mp4 and webm
        if(
                !empty($_POST['inputLow']) && $_POST['inputLow'] !== 'false' &&
                !empty($_POST['inputSD']) && $_POST['inputSD'] !== 'false' &&
                !empty($_POST['inputHD']) && $_POST['inputHD'] !== 'false'                
                ){ // all resolutions
            $e->setFormats_id(36);
        }else if(
                !empty($_POST['inputLow']) && $_POST['inputLow'] !== 'false' &&
                !empty($_POST['inputHD']) && $_POST['inputHD'] !== 'false'                
                ){ 
            $e->setFormats_id(35);
        }else if(
                !empty($_POST['inputSD']) && $_POST['inputSD'] !== 'false' &&
                !empty($_POST['inputHD']) && $_POST['inputHD'] !== 'false'                
                ){ 
            $e->setFormats_id(34);
        }else if(
                !empty($_POST['inputLow']) && $_POST['inputLow'] !== 'false' &&
                !empty($_POST['inputSD']) && $_POST['inputSD'] !== 'false'              
                ){ 
            $e->setFormats_id(33);
        }else if(
                !empty($_POST['inputHD']) && $_POST['inputHD'] !== 'false'                
                ){ 
            $e->setFormats_id(32);
        }else if(
                !empty($_POST['inputSD']) && $_POST['inputSD'] !== 'false'             
                ){ 
            $e->setFormats_id(31);
        }else{ 
            $e->setFormats_id(30);
        }
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