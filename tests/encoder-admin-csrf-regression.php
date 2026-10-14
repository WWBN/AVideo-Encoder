<?php
// Standalone regression test, without an installed database or Streamer configuration.
// php tests/encoder-admin-csrf-regression.php
if (PHP_SAPI !== 'cli') {
    exit(1);
}

// Start the session before any output so _session_start() inside the helper is a no-op.
session_save_path(sys_get_temp_dir());
session_start();
$global = ['webSiteRootURL' => 'https://encoder.example.test/'];
require_once dirname(__DIR__) . '/objects/functions.php';

function expectCSRF($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
    echo "PASS: {$message}\n";
}

$token = getEncoderAdminCSRFToken();
try {
    expectCSRF(preg_match('/^[0-9a-f]{64}$/', $token) === 1, 'Token is 32 random bytes in hex');
    expectCSRF(getEncoderAdminCSRFToken() === $token, 'Token is stable within the session');

    $_POST = [];
    expectCSRF(!isEncoderAdminCSRFTokenValid(), 'Request without a token is refused');
    $_POST = ['csrfToken' => str_repeat('0', 64)];
    expectCSRF(!isEncoderAdminCSRFTokenValid(), 'Request with a wrong token is refused');
    $_POST = ['csrfToken' => [$token]];
    expectCSRF(!isEncoderAdminCSRFTokenValid(), 'Request with an array token is refused');
    $_POST = ['csrfToken' => $token];
    expectCSRF(isEncoderAdminCSRFTokenValid(), 'Request with the session token is accepted');

    $root = dirname(__DIR__);
    foreach (['view/saveConfig.php' => '->setCode(', 'view/isAdmin.php' => '->setIsAdmin('] as $file => $sideEffect) {
        $source = file_get_contents("{$root}/{$file}");
        $admin = strpos($source, 'Login::isAdmin()');
        $check = strpos($source, 'isEncoderAdminCSRFTokenValid()');
        expectCSRF($admin !== false && $check !== false && $admin < $check && $check < strpos($source, $sideEffect), "{$file} checks the token after the admin check and before saving");
    }
    $index = file_get_contents("{$root}/view/index.php");
    expectCSRF(substr_count($index, '"csrfToken": <?php echo json_encode(getEncoderAdminCSRFToken()); ?>') === 2, 'view/index.php sends the token with saveConfig and isAdmin');
} finally {
    session_destroy();
}
