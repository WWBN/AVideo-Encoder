<?php

//streamer config
require_once '../videos/configuration.php';

if (!isCommandLineInterface()) {
    return die('Command Line only');
}
require_once $global['systemRootPath'] . 'objects/Encoder.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_DEPRECATED);

//$rows = Encoder::getAllQueue();
$rows = Encoder::getAll(false);

echo "Start {$global['webSiteRootURL']}" . PHP_EOL;
foreach ($rows as $value) {
    echo "Deleting [{$value['id']}]{$value['title']}, {$value['videoDownloadedLink']}" . PHP_EOL;
    $e = new Encoder($value['id']);
    $e->delete();
}

echo "end" . PHP_EOL;
echo "\n";
die();
