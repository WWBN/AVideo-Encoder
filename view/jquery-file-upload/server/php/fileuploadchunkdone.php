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
require_once '../../../../objects/Encoder.php';
require_once '../../../../objects/Login.php';

if(!Login::isLogged()){
    die("Not login");
}

$_FILES['upl'] = array();
$_FILES['upl']['error'] = 0;

$_FILES['upl']['name'] = str_replace("/", "", $_POST['file']);
$_FILES['upl']['tmp_name'] = $global['systemRootPath'] . "videos/chunk/" . Login::getStreamerId() . "/" . $_FILES['upl']['name'];

$forceRename = true;

require_once '../../../upload.php';
