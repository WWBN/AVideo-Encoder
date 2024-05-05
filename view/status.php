<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
require_once dirname(__FILE__) . '/../videos/configuration.php';
require_once '../objects/Encoder.php';

$config = new Configuration();

//$r = Encoder::sendFile("{$global['systemRootPath']}videos/1_tmpFile.mp4", 1, "mp4");var_dump($r);return;
$obj = new stdClass();
$obj->queue_size = 0;
$obj->concurrent = 1;
$obj->is_encoding = false;
$obj->is_downloading = false;
$obj->queue_list = array();
$obj->msg = "";
$obj->encoding = new stdClass();
$obj->cmd = "";
$obj->encoding_status = array();
$obj->version = $config->getVersion();
//$obj->logfile = $global['logfile'];

if (!empty($global['concurrent'])) {
    $obj->concurrent = $global['concurrent'];
}
$obj->encoding = Encoder::areEncoding();
$obj->downloaded = Encoder::areDownloaded();
$obj->downloading = Encoder::areDownloading();
$obj->transferring = Encoder::areTransferring();
//$obj->transferring = Encoder::isTransferring();
$obj->queue_list = Encoder::getAllQueue();
$obj->queue_size = count($obj->queue_list);

if (count($obj->encoding) == 0) {
    if (empty($obj->queue_list)) {
        $obj->msg = "There is no file on queue";
    } else {
        execRun();
        $obj->msg = "We send the file to encode";
    }
} else {
    $obj->is_encoding = true;
    $msg = (count($obj->encoding) == 1) ? "The file " : "The files ";
    for ($i = 0; $i < count($obj->encoding); $i++) {
        $obj->encoding_status[$i] = Encoder::getVideoConversionStatus($obj->encoding[$i]['id']);
        $msg .= "[{$obj->encoding[$i]['id']}] {$obj->encoding[$i]['filename']}";
        if (count($obj->encoding) > 1 && $i < count($obj->encoding) - 1) {
            $msg .= ", ";
        }
    }
    $msg .= (count($obj->encoding) == 1) ? " is encoding" : " are encoding";
    $obj->msg = $msg;
}

if (!empty($obj->downloading)) {
    $obj->is_downloading = true;
    $msg = (count($obj->downloading) == 1) ? "The file " : "The files ";
    for ($i = 0; $i < count($obj->encoding); $i++) {
        $obj->download_status[$i] = Encoder::getYoutubeDlProgress($obj->downloading[$i]['id']);
        $msg .= "[{$obj->encoding[$i]['id']}] {$obj->encoding[$i]['filename']}";
        if (count($obj->downloading) > 1 && $i < count($obj->downloading) - 1) {
            $msg .= ", ";
        }
    }
    $msg .= (count($obj->encoding) == 1) ? " is downloading" : " are downloading";
    $obj->msg = $msg;
}

if (!empty($_GET['serverStatus'])) {
    require_once '../objects/ServerMonitor.php';
    require_once '../objects/functions.php';
    $obj->memory = ServerMonitor::getMemory();
    $obj->file_upload_max_size = get_max_file_size();
}

$remove = array(
    'return_vars',
    'filename',
    'fileURI',
    'worker_pid',
    'worker_ppid',
    'videoDownloadedLink',
    'downloadedFileName',
    'code',
);

foreach ($remove as $value) {
    $obj = removeKeyFromData($obj, $value);
}

if(!is_string($obj)){
    $obj = json_encode($obj);
}

//$resp = json_encode($obj);
echo $obj;
