<?php
// Returns the Monitor tab content (view/monitorDashboard.php) for its auto-refresh.
require_once dirname(__FILE__) . '/../videos/configuration.php';
require_once $global['systemRootPath'] . 'objects/Login.php';
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');

// Same admin boundary as view/encoderVersion.json.php (the Streamer's forbiddenPage() is not available here).
if (!Login::isAdmin()) {
    http_response_code(403);
    exit;
}
require_once $global['systemRootPath'] . 'objects/Encoder.php';
require_once $global['systemRootPath'] . 'locale/function.php';
session_write_close();
include $global['systemRootPath'] . 'view/monitorDashboard.php';
