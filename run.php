<?php
header('Content-Type: application/json');
require_once 'configuration.php';
require_once $global['systemRootPath'].'objects/Encoder.php';

$e = Encoder::run();
$resp = json_encode($e);
echo $resp;