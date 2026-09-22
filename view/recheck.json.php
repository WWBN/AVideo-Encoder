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
    $password = $_POST['password'] ?? null;
    unset($_POST['password'], $_REQUEST['password']);
    if ($password !== null && (!is_string($password) || $password === '' || strlen($password) > 1024)) {
        http_response_code(400);
        echo json_encode(['error' => true, 'msg' => __('Invalid request')]);
        exit;
    }
    $check = $encoder->recheckOutputFiles();
    if ($check['error']) {
        $check['msg'] = __($check['msg']);
        echo json_encode($check);
        exit;
    }
    $streamer = new Streamer($encoder->getStreamers_id());
    if (!empty($_POST['renew']) && $password === null) {
        $authentication = ['error' => true, 'authentication_required' => true,
            'account' => $streamer->getUser(), 'site' => $streamer->getSiteURL(),
            'msg' => 'Enter this account password to renew access and continue the transfer.'];
    } else {
        $authentication = $streamer->refreshAuthentication($password);
    }
    unset($password);
    if ($authentication['error']) {
        $authentication['msg'] = __($authentication['msg']);
        echo json_encode($authentication);
        exit;
    }
    $response = $encoder->startOutputResume();
    $response['msg'] = __($response['msg']);
    echo json_encode($response);
} catch (Throwable $error) {
    _error_log('Output recheck failed for encoder ' . $id . ': ' . $error->getMessage());
    http_response_code(500);
    echo json_encode(['error' => true, 'msg' => __('An error occurred')]);
}
