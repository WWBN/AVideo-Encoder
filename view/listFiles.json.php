<?php
require_once dirname(__FILE__) . '/../videos/configuration.php';
require_once $global['systemRootPath'] . 'objects/Login.php';
header('Content-Type: application/json');

// Ensure extensions are in lowercase and unique
$global['allowed'] = array_map('strtolower', $global['allowed']);
$global['allowed'] = array_unique($global['allowed']);

$files = array();
if(Login::canBulkEncode()){
    if (!empty($_POST['path'])) {
        $path = $_POST['path'];
        if (substr($path, -1) !== DIRECTORY_SEPARATOR) {
            $path .= DIRECTORY_SEPARATOR;
        }
        
        if (file_exists($path)) {
            if (defined('GLOB_BRACE')) {
                $extn = implode(",*.", $global['allowed']);
                $extnLower = strtolower($extn);
                $extnUpper = strtoupper($extn);
                $filesStr = "{*." . $extn . ",*" . $extnLower . ",*" . $extnUpper . "}";
                $video_array = glob($path . $filesStr, GLOB_BRACE);
            } else {
                $video_array = array();
                foreach ($global['allowed'] as $value) {
                    $video_array = array_merge($video_array, glob($path . "*." . $value));
                }
            }
            
            $id = 0;
            foreach ($video_array as $key => $value) {
                $path_parts = pathinfo($value);
                $obj = new stdClass();
                $obj->id = $id++;
                $obj->path = _utf8_encode($value);
                $obj->name = _utf8_encode($path_parts['basename']);
                $files[] = $obj;
            }
        }
    }
}
echo json_encode($files);
