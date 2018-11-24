<?php
header('Content-Type: application/json');
require_once './Login.php';
Login::logoff();
header("Location: {$global['webSiteRootURL']}");