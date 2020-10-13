<?php
ini_set( 'log_errors_max_len', '1024' );
if(empty($global['logfile'])){
    $global['logfile'] = $global['systemRootPath'].'videos/avideo.log';
}
ini_set('error_log', $global['logfile']);
global $global;
global $config;

if(empty($global['configurationVersion']) || $global['configurationVersion']<2){
    require_once $global['systemRootPath'].'objects/Configuration.php';
    Configuration::rewriteConfigFile();
}
header('Set-Cookie: cross-site-cookie=name; SameSite=None; Secure');
require_once $global['systemRootPath'] . 'objects/security.php';

$global['mysqli'] = new mysqli($mysqlHost, $mysqlUser,$mysqlPass,$mysqlDatabase);

$now = new DateTime();
$mins = $now->getOffset() / 60;
$sgn = ($mins < 0 ? -1 : 1);
$mins = abs($mins);
$hrs = floor($mins / 60);
$mins -= $hrs * 60;
$offset = sprintf('%+d:%02d', $hrs*$sgn, $mins);
$global['mysqli']->query("SET time_zone='$offset';");

session_set_cookie_params(86400);
ini_set('session.gc_maxlifetime', 86400);
ini_set('session.cookie_lifetime',86400);

if(!function_exists('local_get_contents')){
    require_once $global['systemRootPath'].'objects/functions.php';
    require_once $global['systemRootPath'].'objects/Object.php';
}

_session_start();

$_SESSION['lastUpdate'] = time();

$global['multiResolutionOrder']       = array(74,75,76,77,78,79,80,81,82,83,84,85,86,87,6,7,8, 88, 89, 90);
$global['sendAll']                    = array(6,7,8, 88, 89, 90);
$global['hasHDOrder']                 = array(87,86,85,83,80,79,78,76); 
$global['hasSDOrder']                 = array(87,85,84,82,80,78,77,75); 
$global['hasLowOrder']                = array(87,86,84,81,80,79,77,74); 
$global['bothVideosOrder']            = array(81,82,83,84,85,86,87); // MP4 and Webm

// in case of PHP - youtube-dl: command not found
putenv('PATH=/usr/local/bin:/usr/bin');