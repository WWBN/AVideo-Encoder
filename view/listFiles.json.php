<?php
require_once dirname(__FILE__) . '/../videos/configuration.php';
require_once $global['systemRootPath'] . 'objects/Login.php';
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

if (empty($global['allowed'])) {
    $global['allowed'] = [];
}

$global['allowed'] = array_map('strtolower', $global['allowed']);
$global['allowed'] = array_unique($global['allowed']);

$files = [];

if (Login::canBulkEncode()) {
    if (!empty($_POST['path'])) {
        $path = realpath($_POST['path']);
        if ($path === false) {
            error_log("Bulk Encode Error: realpath() failed for " . $_POST['path']);
            echo json_encode(["error" => "Invalid path"]);
            exit;
        }
        $path .= DIRECTORY_SEPARATOR;
        error_log("Bulk Encode: Resolved path - " . $path);

        if (!file_exists($path) || !is_readable($path)) {
            error_log("Bulk Encode Error: Path not accessible - " . $path);
            echo json_encode(["error" => "Path not accessible"]);
            exit;
        }

        $video_array = [];
        $dirContents = scandir($path);

        if ($dirContents !== false) {
            foreach ($dirContents as $file) {
                $filePath = $path . $file;
                if (is_file($filePath)) {
                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    if (in_array($ext, $global['allowed'])) {
                        $video_array[] = $filePath;
                    }
                }
            }
        } else {
            error_log("Bulk Encode Error: scandir() failed for " . $path);
        }

        if (empty($video_array)) {
            error_log("Bulk Encode Warning: No files found.");
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
    error_log("Bulk Encode Error: User does not have permission.");
    echo json_encode(["error" => "Permission denied"]);
    exit;
}

echo json_encode($files);
