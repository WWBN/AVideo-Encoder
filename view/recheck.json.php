<?php
require_once dirname(__FILE__) . '/../videos/configuration.php';
require_once $global['systemRootPath'] . 'objects/Encoder.php';
require_once $global['systemRootPath'] . 'objects/Login.php';
require_once $global['systemRootPath'] . 'locale/function.php';
header('Content-Type: application/json');

if (!Login::isLogged() || !Login::canUpload()) {
    http_response_code(403);
    echo json_encode(['error' => true, 'msg' => __('Permission denied')]);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['error' => true, 'msg' => __('Invalid request')]);
    exit;
}
// Require the same-origin AJAX control before resuming a transfer.
if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'XMLHttpRequest') {
    http_response_code(403);
    echo json_encode(['error' => true, 'msg' => __('Permission denied')]);
    exit;
}

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => true, 'msg' => __('Invalid request')]);
    exit;
}
$encoder = new Encoder($id);
if (!$encoder->getId() || (!Login::isAdmin() && Login::getStreamerId() !== (int) $encoder->getStreamers_id())) {
    http_response_code(403);
    echo json_encode(['error' => true, 'msg' => __('Permission denied')]);
    exit;
}
session_write_close();

try {
    $response = $encoder->startOutputResume();
    $response['msg'] = __($response['msg']);
    echo json_encode($response);
} catch (Throwable $error) {
    _error_log('Output recheck failed for encoder ' . $id . ': ' . $error->getMessage());
    http_response_code(500);
    echo json_encode(['error' => true, 'msg' => __('An error occurred')]);
}
