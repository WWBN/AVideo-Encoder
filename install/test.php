<?php

//streamer config
require_once '../videos/configuration.php';

if (!isCommandLineInterface()) {
    return die('Command Line only');
}

$filesURL = array(
    'http://4k.ypt.me/1080/Christmas_Tree_Bokeh.mp4',
    'http://4k.ypt.me/1080/Galaxy_With_Customization.mp4',
    'http://4k.ypt.me/1080/Earth_from_Space.mov',
    'http://4k.ypt.me/1080/Snow.mp4',
    'http://4k.ypt.me/4K/Elecard_about_Tomsk_part1_HEVC_UHD.mp4',
    'http://4k.ypt.me/4K/Stream1_AV1_4K_8.5mbps.webm'
);

$_POST['user'] = 'admin';
$_POST['pass'] = '123';
$_POST['notifyURL'] = str_replace("Encoder/", "", $global['webSiteRootURL']);
foreach ($filesURL as $value) {
    $_POST['fileURI'] = $value;
    $path_parts = pathinfo($_POST['fileURI']);
    $_POST['filename'] = str_replace(array(".", "_"), array(" ", " "), $path_parts['basename']);
    echo "Include: ".json_encode($_POST).PHP_EOL;
    include $global['systemRootPath'].'view/queue.php';
}

echo "Bye".PHP_EOL;
die();




