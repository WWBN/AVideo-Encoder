<?php
/*
 * jQuery File Upload Plugin PHP Example
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2010, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * https://opensource.org/licenses/MIT
 */
require_once '../../../../videos/configuration.php';
require_once '../../../../objects/Login.php';

if(!Login::isLogged()){
    die("Not login");
}

//error_reporting(E_ALL | E_STRICT);
require('UploadHandler.php');
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
$upload_handler = new UploadHandler();