<?php
//streamer config
require_once '../videos/configuration.php';

if(!isCommandLineInterface()){
    return die('Command Line only');
}

$sql = "UPDATE {$global['tablesPrefix']}configurations_encoder SET allowedStreamersURL = '' where id > 0 ";
$global['mysqli']->query($sql);

die();
