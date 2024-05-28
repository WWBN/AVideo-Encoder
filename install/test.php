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

//var_dump($global['webSiteRootURL']);exit;
/*
$filesURL = array(
    'http://4k.ypt.me/1080/Christmas_Tree_Bokeh.mp4',
    'http://4k.ypt.me/1080/Galaxy_With_Customization.mp4',
    'http://4k.ypt.me/1080/Earth_from_Space.mov',
    'http://4k.ypt.me/1080/Snow.mp4',
    'http://4k.ypt.me/4K/Elecard_about_Tomsk_part1_HEVC_UHD.mp4',
);

foreach ($filesURL as $value) {
    $_POST['fileURI'] = $value;
    $path_parts = pathinfo($_POST['fileURI']);
    $basename = explode(".", $path_parts['basename']);
    $_POST['filename'] = str_replace(array(".", "_"), array(" ", " "), $basename[0]);
    echo "Processing: ".json_encode($_POST) . PHP_EOL;
    include $global['systemRootPath'].'view/queue.php';
    echo "Include: {$_POST['filename']}" . PHP_EOL;
}
*/
$filesURL = array(
    'https://www.youtube.com/watch?v=PaXVZJFfgJA',
    'https://www.youtube.com/watch?v=WO2b03Zdu4Q',
    'https://www.youtube.com/watch?v=R5LAPvUKGvc',
    'https://www.youtube.com/watch?v=njX2bu-_Vw4',
    'https://www.youtube.com/watch?v=ee9i6oMqShk',
    'https://www.youtube.com/watch?v=mkggXE5e2yk',
    //'https://www.youtube.com/watch?v=e4zRaWV2YEQ',
);

foreach ($filesURL as $value) {
    $_REQUEST['videoURL'] = $value;
    echo "Processing: ".json_encode($_REQUEST) . PHP_EOL;
    include $global['systemRootPath'].'view/youtubeDl.json.php';
}


echo "Bye" . PHP_EOL;
die();
