<?php
require_once dirname(__FILE__) . '/../videos/configuration.php';
require_once $global['systemRootPath'] . 'objects/Login.php';
header('Content-Type: application/json');

$files = array();
if(Login::canBulkEncode()){
    if (!empty($_POST['path'])) {
        $path = $_POST['path'];
        if (substr($path, -1) !== '/') {
            $path .= "/";
        }

        if (file_exists($path)) {
            $filesStr = "{*." . implode(",*.", $global['allowed']) . "}";

            //echo $files;
            $video_array = glob($path . $filesStr, GLOB_BRACE);

            $id = 0;
            foreach ($video_array as $key => $value) {
                $path_parts = pathinfo($value);
                $obj = new stdClass();
                $obj->id = $id++;
                $obj->path = utf8_encode($value);
                $obj->name = utf8_encode($path_parts['basename']);
                $files[] = $obj;
            }
        }
    }
}
echo json_encode($files);
