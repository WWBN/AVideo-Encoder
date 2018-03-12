<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
require_once dirname(__FILE__) . '/../videos/configuration.php';
require_once '../objects/Encoder.php';

//$r = Encoder::sendFile("{$global['systemRootPath']}videos/1_tmpFile.mp4", 1, "mp4");var_dump($r);return;
$obj = new stdClass();
$obj->queue_size = 0;
$obj->is_encoding = false;
$obj->queue_list = array();
$obj->msg = "";
$obj->encoding = new stdClass();
$obj->cmd = "";
$obj->encoding_status = "";

$obj->encoding = Encoder::isEncoding();
//$obj->transferring = Encoder::isTransferring();
$obj->queue_list = Encoder::getAllQueue();
$obj->queue_size = count($obj->queue_list);

if(empty($obj->encoding['id'])){
    if(empty($obj->queue_list)){
        $obj->msg = "There is no file on queue";
    }else{
        $cmd = PHP_BINDIR."/php -f {$global['systemRootPath']}view/run.php > /dev/null 2>/dev/null &";
        //echo "** executing command {$cmd}\n";
        exec($cmd);
        $obj->cmd = $cmd;
        $obj->msg = "We send the file to encode";
    }
}else{
    $obj->is_encoding = true;
    $obj->encoding_status = Encoder::getVideoConversionStatus($obj->encoding['id']);
    $obj->download_status = Encoder::getYoutubeDlProgress($obj->encoding['id']);
    $obj->msg = "The file [{$obj->encoding['id']}] {$obj->encoding['filename']} is encoding";
}

if(!empty($_GET['serverStatus'])){
    require_once '../objects/ServerMonitor.php';
    require_once '../objects/functions.php';
    $obj->cpu = ServerMonitor::getCpu();
    $obj->memory = ServerMonitor::getMemory();
    $obj->file_upload_max_size = get_max_file_size();
}
$resp = json_encode($obj);
echo $resp;