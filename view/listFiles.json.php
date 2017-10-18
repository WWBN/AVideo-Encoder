<?php
require_once dirname(__FILE__) . '/../videos/configuration.php';
header('Content-Type: application/json');

$files = array();
if(empty($global['disableBulkEncode'])){
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
                $obj->path = addslashes($value);
                $obj->name = addslashes($path_parts['basename']);
                $files[] = $obj;
            }
        }
    }
}
echo json_encode($files);
