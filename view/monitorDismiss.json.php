<?php
require_once dirname(__DIR__) . '/videos/configuration.php';
require_once $global['systemRootPath'] . 'objects/Login.php';
require_once $global['systemRootPath'] . 'objects/EncoderMonitor.php';
require_once $global['systemRootPath'] . 'locale/function.php';
header('Content-Type: application/json');

if (!Login::isAdmin()) {
    http_response_code(403);
    _error_log('monitorDismiss: permission denied');
    echo json_encode(['error' => true, 'msg' => __('Permission denied')]);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['error' => true, 'msg' => __('Invalid request')]);
    exit;
}
if (!isEncoderAdminCSRFTokenValid()) {
    http_response_code(403);
    _error_log('monitorDismiss: invalid or missing csrfToken');
    echo json_encode(['error' => true, 'msg' => __('Invalid token, reload the page')]);
    exit;
}
$streamers_id = filter_var($_POST['streamers_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$version = $_POST['version'] ?? null;
if (!$streamers_id || !is_string($version) || !preg_match('/^[a-f0-9]{64}$/D', $version)) {
    http_response_code(400);
    echo json_encode(['error' => true, 'msg' => __('Invalid request')]);
    exit;
}
session_write_close();
try {
    if (!EncoderMonitor::dismissStreamerIssue($streamers_id, $version)) {
        http_response_code(409);
        echo json_encode(['error' => true, 'msg' => __('The notice changed. Refresh the page and try again.')]);
        exit;
    }
    echo json_encode(['error' => false, 'msg' => __('Notice dismissed.')]);
} catch (Throwable $error) {
    _error_log('monitorDismiss: ' . $error->getMessage());
    http_response_code(500);
    echo json_encode(['error' => true, 'msg' => __('An error occurred')]);
}
