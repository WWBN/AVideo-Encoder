<?php
require_once dirname(__FILE__) . '/../videos/configuration.php';
require_once $global['systemRootPath'] . 'objects/Login.php';
require_once $global['systemRootPath'] . 'objects/EncoderVersion.php';
header('Content-Type: application/json');
header('Cache-Control: no-store');

// Use the Encoder's existing admin boundary (the Streamer's User/forbiddenPage helpers are not available here).
if (!Login::isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => true, 'msg' => 'Permission denied']);
    exit;
}
session_write_close();
try {
    $installed = EncoderVersion::installed($global['systemRootPath']);
    $result = EncoderVersion::check($installed, function ($path) use ($global) {
        return EncoderVersion::github($path, $global['systemRootPath'] . 'videos');
    });
    echo json_encode(['error' => false, 'result' => $result]);
} catch (Throwable $error) {
    _error_log('Encoder update check failed: ' . $error->getMessage());
    http_response_code(500);
    echo json_encode(['error' => true, 'msg' => 'Could not check for updates']);
}
