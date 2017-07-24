<?php
ini_set( 'log_errors_max_len', '1024' );
ini_set('error_log', $global['systemRootPath'].'videos/youphptube.log');
global $global;
global $config;


$global['mysqli'] = new mysqli($mysqlHost, $mysqlUser,$mysqlPass,$mysqlDatabase);

$now = new DateTime();
$mins = $now->getOffset() / 60;
$sgn = ($mins < 0 ? -1 : 1);
$mins = abs($mins);
$hrs = floor($mins / 60);
$mins -= $hrs * 60;
$offset = sprintf('%+d:%02d', $hrs*$sgn, $mins);
$global['mysqli']->query("SET time_zone='$offset';");

session_start();

require_once $global['systemRootPath'].'objects/Object.php';