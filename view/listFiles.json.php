<?php
require_once dirname(__FILE__) . '/../videos/configuration.php';
require_once $global['systemRootPath'] . 'objects/Login.php';
header('Content-Type: application/json');
$global['allowed'] = array_unique($global['allowed']);
$files = array();
if(Login::canBulkEncode()){
    if (!empty($_POST['path'])) {
        $path = $_POST['path'];
        if (substr($path, -1) !== DIRECTORY_SEPARATOR) {
            $path .= DIRECTORY_SEPARATOR;
        }
        //var_dump($path, file_exists($path));
        if (file_exists($path)) {
            if (defined( 'GLOB_BRACE' )) {
                $filesStr = "{*." . implode(",*.", $global['allowed']) . "}";
                //var_dump($filesStr);
                //echo $files;
                $video_array = glob($path . $filesStr, GLOB_BRACE);
            } else {
                //var_dump($global['allowed']);
                $video_array = array();
                foreach ($global['allowed'] as $value) {
                    $video_array += glob($path . "*." . $value);
                }
            }
            $id = 0;
            foreach ($video_array as $key => $value) {
                $path_parts = pathinfo($value);
                $obj = new stdClass();
                $obj->id = $id++;
                //$obj->path_clean = ($value);
                $obj->path = _utf8_encode($value);
                $obj->name = _utf8_encode($path_parts['basename']);
                $files[] = $obj;
            }
        }
    }
}
echo json_encode($files);
