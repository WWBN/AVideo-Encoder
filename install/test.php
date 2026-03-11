<?php

//streamer config
require_once '../videos/configuration.php';

if (!isCommandLineInterface()) {
    return die('Command Line only');
}

$_REQUEST['user'] = 'admin';
$_REQUEST['pass'] = '123';
//$_POST['inputAutoHLS'] = true;
$_REQUEST['notifyURL'] = str_replace("Encoder/", "", $global['webSiteRootURL']);
$_REQUEST['notifyURL'] = str_ireplace(array('rtmp://'), array(''), $_REQUEST['notifyURL']);
$_REQUEST['webSiteRootURL'] = $_REQUEST['notifyURL'];

require_once $global['systemRootPath'] . 'objects/Encoder.php';
require_once $global['systemRootPath'] . 'objects/Login.php';
require_once $global['systemRootPath'] . 'objects/Streamer.php';
require_once $global['systemRootPath'] . 'objects/functions.php';

// Login once before processing all videos
Login::run($_REQUEST['user'], $_REQUEST['pass'], $_REQUEST['webSiteRootURL'], true);

if (!Login::canUpload()) {
    die('This user can not upload files' . PHP_EOL);
}

$streamers_id = Login::getStreamerId();
if (empty($streamers_id)) {
    die('There is no streamer site' . PHP_EOL);
}

echo "Logged in. Streamer ID: {$streamers_id}" . PHP_EOL;

$filesURL = array(
    /*
    'https://www.youtube.com/watch?v=PaXVZJFfgJA',
    'https://www.youtube.com/watch?v=WO2b03Zdu4Q',
    'https://www.youtube.com/watch?v=R5LAPvUKGvc',
    'https://www.youtube.com/watch?v=njX2bu-_Vw4',
    'https://www.youtube.com/watch?v=ee9i6oMqShk',
    'https://www.youtube.com/watch?v=mkggXE5e2yk',
    */
    //'https://www.youtube.com/watch?v=e4zRaWV2YEQ',
    //'https://4k.ypt.me/1080/Raindrops_Videvo.mp4',//(NO AUDIO)
    //'https://4k.ypt.me/1080/Christmas_Tree_Pan.mp4',//(NO AUDIO)
    'https://www.youtube.com/watch?v=f1wxBXqPtCM',
    'https://www.youtube.com/watch?v=UsL0LiBb9RQ',
    'https://4k.ypt.me/4K/Hisense.mp4',
    'https://4k.ypt.me/4K/Rocket_to_Space.mp4',
    'https://4k.ypt.me/4K/beach-uhd_3840_2160_30fps.mp4',
    'https://4k.ypt.me/4K/Time_Scapes.mp4',
    'https://4k.ypt.me/1080/big_buck_bunny_720p_30mb.mp4',
    'https://4k.ypt.me/1080/rain-hd_1920_1080_30fps.mp4',
);

foreach ($filesURL as $key => $value) {
    $index = $key + 1;
    $total = count($filesURL);
    echo "[{$index}/{$total}] Processing: {$value}" . PHP_EOL;
    $result = addVideo($value, $streamers_id);
    if (!empty($result->error)) {
        echo "[{$index}/{$total}] ERROR: " . (isset($result->text) ? $result->text : json_encode($result)) . PHP_EOL;
    } else {
        echo "[{$index}/{$total}] Queued: " . (isset($result->text) ? $result->text : 'OK') . " (queue_id: " . (isset($result->queue_id) ? $result->queue_id : 'N/A') . ")" . PHP_EOL;
    }
}


echo "Bye" . PHP_EOL;
die();
