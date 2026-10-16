<?php
require_once __DIR__ . '/../videos/configuration.php';
require_once __DIR__ . '/../objects/Streamer.php';
require_once __DIR__ . '/../objects/Login.php';
require_once __DIR__ . '/../objects/LocalEncoderPromotion.php';
header('Content-Type: application/json');
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');
if (!Login::isLogged() || !Login::canUpload() || !isEncoderAdminCSRFTokenValid()) {
    http_response_code(403);
    error_log('Local Encoder sign-in: missing session, permission or CSRF token');
    echo json_encode(array('error' => true, 'msg' => 'Not authorized'));
    exit;
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(array('error' => true, 'msg' => 'Method not allowed'));
    exit;
}
$response = array('error' => true, 'msg' => 'Open Local Encoder again, or sign in manually.');
try {
    $grant = EncoderLocalEncoderPromotion::requestLoginCode($_POST['challenge'] ?? '');
    if ($grant) { $response = $grant; }
} catch (Throwable $th) {
    error_log('Local Encoder sign-in failed (' . get_class($th) . ')');
}
echo json_encode($response);
