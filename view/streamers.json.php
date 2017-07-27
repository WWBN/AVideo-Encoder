<?php
require_once dirname(__FILE__) . '/../videos/configuration.php';
require_once $global['systemRootPath'].'objects/Streamer.php';
header('Content-Type: application/json');
$rows = Streamer::getAll();
$total = Streamer::getTotal();

echo '{  "current": '.$_POST['current'].',"rowCount": '.$_POST['rowCount'].', "total": '.$total.', "rows":'. json_encode($rows).'}';