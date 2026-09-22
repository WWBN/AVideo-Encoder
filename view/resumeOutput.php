<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}
require_once dirname(__FILE__) . '/../videos/configuration.php';
require_once $global['systemRootPath'] . 'objects/Encoder.php';
session_write_close();
set_time_limit(0);

$id = filter_var($argv[1] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id) {
    exit(1);
}
try {
    $encoder = new Encoder($id);
    exit($encoder->resumeOutputTransfer() ? 0 : 1);
} catch (Throwable $error) {
    _error_log('Output resume worker failed for encoder ' . $id . ': ' . $error->getMessage());
    exit(1);
}
