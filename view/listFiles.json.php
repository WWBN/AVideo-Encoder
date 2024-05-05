<?php
require_once dirname(__FILE__) . '/../videos/configuration.php';
require_once $global['systemRootPath'] . 'objects/Login.php';
header('Content-Type: application/json');

if(empty($global['allowed'])){
    $global['allowed'] = array();
}

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
        
        $video_array = array();
        if (file_exists($path)) {
            foreach ($global['allowed'] as $value) {
                $video_array = array_merge($video_array, glob($path . "*." . $value));
            }
        }
        
        // Deduplication: Use an associative array to track already added files
        $addedFiles = [];
        
        $id = 0;
        foreach ($video_array as $key => $value) {
            // If file is already added, skip
            if(isset($addedFiles[strtolower($value)])) {
                continue;
            }
            
            // Mark the file as added
            $addedFiles[strtolower($value)] = true;
            
            $path_parts = pathinfo($value);
            $obj = new stdClass();
            $obj->id = $id++;
            $obj->path = _utf8_encode($value);
            $obj->name = _utf8_encode($path_parts['basename']);
            $files[] = $obj;
        }
    }
}
echo json_encode($files);
