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

require_once $global['systemRootPath'].'objects/functions.php';
require_once $global['systemRootPath'].'objects/Object.php';


$global['multiResolutionIds']   = array(23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36);
$global['hasHDIds']             = array(36, 35, 34, 32, 29, 28, 27, 25); 
$global['hasSDIds']             = array(36, 34, 33, 31, 29, 27, 26, 24); 
$global['hasLowIds']            = array(36, 35, 33, 30, 29, 28, 26, 23); 
$global['bothVideosIds']        = array(36,35,34,33,32,31,30); // MP4 and Webm