<?php

//streamer config
require_once '../videos/configuration.php';

if (!isCommandLineInterface()) {
    return die('Command Line only');
}

$sql = "UPDATE encoder_queue SET status = 'queue' where id > 0";
echo $sql . PHP_EOL;
$insert_row = $global['mysqli']->query($sql);

if ($insert_row) {
    echo "All set to queue" . PHP_EOL;
} else {
    die($sql . ' Error : (' . $global['mysqli']->errno . ') ' . $global['mysqli']->error);
}
echo "Bye";
echo "\n";
die();




