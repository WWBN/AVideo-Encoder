<?php
//exit;
header('Content-Type: application/json');
require_once dirname(__FILE__) . '/../videos/configuration.php';
require_once $global['systemRootPath'].'objects/Encoder.php';
require_once $global['systemRootPath'].'objects/Login.php';

if (!Login::canUpload()) {
    http_response_code(403);
    echo json_encode(['error' => true, 'msg' => 'Permission denied']);
    exit;
}
//error_log("Run Executed");
$e = Encoder::run();
$resp = json_encode($e);
echo $resp;
if (!empty($global['mysqli'])) {
    $global['mysqli']->close();
    unset($global['mysqli']); // opcional, para limpar a variável
}
