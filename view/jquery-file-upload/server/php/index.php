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

if (!Login::isLogged()) {
    die("Not login");
}

error_reporting(E_ALL & ~E_DEPRECATED);
if (!empty($_FILES) && !empty($_FILES['files'])) {
    //var_dump($_FILES['files']['name']);
    if (!empty($_FILES['files']['name'])) {
        foreach ($_FILES['files']['name'] as $key => $value) {
            if (strlen($value) < 5 && !empty($_FILES['files']['full_path'][$key])) {
                $_FILES['files']['name'][$key] = $_FILES['files']['full_path'][$key];
            }
        }
        //var_dump($_FILES, $_POST, $_GET, $_REQUEST);
    }
}
//var_dump($global['allowed']);
require('UploadHandler.php');
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
$upload_handler = new UploadHandler();
