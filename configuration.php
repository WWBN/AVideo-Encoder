<?php
$global['webSiteRootURL'] = 'https://127.0.0.1/YouPHPTube-Encoder/';
$global['systemRootPath'] = '/home/daniel/Dropbox/htdocs/YouPHPTube-Encoder/';

$global['disableConfigurations'] = false;
$global['disableBulkEncode'] = false;

$mysqlHost = 'localhost';
$mysqlUser = 'root';
$mysqlPass = 'M!$S@0';
$mysqlDatabase = 'YouPHPTube-Encoder';

// A list of permitted file extensions
$global['allowed'] = array('mp4', 'avi', 'mov', 'mkv', 'flv', 'mp3', 'wav', 'm4v', 'webm', 'wmv');

/**
 * Casa 
$global['webSiteRootURL'] = 'http://192.168.25.45/YouPHPTube-Encoder/';
$global['systemRootPath'] = '/var/www/html/YouPHPTube-Encoder/';
$global['YouPHPTubeURL'] = 'http://192.168.25.16/YouPHPTube/';
$mysqlPass = '123';
*/

require_once $global['systemRootPath'].'objects/include_config.php';