<?php
//exit;
header('Content-Type: application/json');
require_once dirname(__FILE__) . '/../videos/configuration.php';
require_once $global['systemRootPath'].'objects/Encoder.php';
require_once $global['systemRootPath'].'objects/Login.php';

// run.php is dispatched by execRun() using CLI; in that context there is no browser session.
$isCLI = isCommandLineInterface();
$canUpload = Login::canUpload();
error_log('run.php: invoked isCLI=' . ($isCLI ? 'true' : 'false') . ' canUpload=' . ($canUpload ? 'true' : 'false') . ' session=' . session_id());

if (!$isCLI && !$canUpload) {
    error_log('run.php: permission denied');
    http_response_code(403);
    echo json_encode(['error' => true, 'msg' => 'Permission denied']);
    exit;
}
//error_log("Run Executed");
$e = Encoder::run();
$safeResult = is_object($e) ? json_encode($e) : var_export($e, true);
error_log('run.php: Encoder::run result=' . $safeResult);
$resp = json_encode($e);
echo $resp;
if (!empty($global['mysqli'])) {
    $global['mysqli']->close();
    unset($global['mysqli']); // opcional, para limpar a variável
}
