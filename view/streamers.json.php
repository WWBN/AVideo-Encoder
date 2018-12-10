<?php
require_once dirname(__FILE__) . '/../videos/configuration.php';
require_once $global['systemRootPath'].'objects/Streamer.php';
require_once $global['systemRootPath'].'objects/Login.php';
header('Content-Type: application/json');

if(!Login::isAdmin()){
    die("Not Admin");
}

$rows = Streamer::getAll();
$total = Streamer::getTotal();

echo '{  "current": '.$_POST['current'].',"rowCount": '.$_POST['rowCount'].', "total": '.$total.', "rows":'. json_encode($rows).'}';