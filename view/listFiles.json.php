<?php
require_once dirname(__FILE__) . '/../videos/configuration.php';
require_once $global['systemRootPath'] . 'objects/Login.php';
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

if (empty($global['allowed'])) {
    $global['allowed'] = array();
}

// Ensure extensions are in lowercase and unique
$global['allowed'] = array_map('strtolower', $global['allowed']);
$global['allowed'] = array_unique($global['allowed']);

$files = array();

if (Login::canBulkEncode()) {
    if (!empty($_POST['path'])) {
        $path = rtrim($_POST['path'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        // Log the received path
        error_log("Bulk Encode: Received path - " . $path);

        if (!file_exists($path)) {
            error_log("Bulk Encode Error: Path does not exist - " . $path);
            echo json_encode(["error" => "Path does not exist"]);
            exit;
        }

        if (!is_readable($path)) {
            error_log("Bulk Encode Error: Path is not readable - " . $path);
            echo json_encode(["error" => "Path is not readable"]);
            exit;
        }

        $video_array = array();
        foreach ($global['allowed'] as $ext) {
            $filesFound = glob($path . "*." . $ext);
            if ($filesFound === false) {
                error_log("Bulk Encode Error: glob() failed for extension .$ext in path $path");
            } else {
                error_log("Bulk Encode: Found " . count($filesFound) . " files with extension .$ext");
            }
            $video_array = array_merge($video_array, $filesFound);
        }

        if (empty($video_array)) {
            error_log("Bulk Encode Warning: No files found in the directory.");
        }

        $addedFiles = [];
        $id = 0;
        foreach ($video_array as $value) {
            if (isset($addedFiles[strtolower($value)])) {
                continue;
            }

            $addedFiles[strtolower($value)] = true;
            $path_parts = pathinfo($value);

            $obj = new stdClass();
            $obj->id = $id++;
            $obj->path = utf8_encode($value);
            $obj->name = utf8_encode($path_parts['basename']);
            $files[] = $obj;
        }
    } else {
        error_log("Bulk Encode Error: No path provided.");
        echo json_encode(["error" => "No path provided"]);
        exit;
    }
} else {
    error_log("Bulk Encode Error: User does not have permission to bulk encode.");
    echo json_encode(["error" => "Permission denied"]);
    exit;
}

echo json_encode($files);
