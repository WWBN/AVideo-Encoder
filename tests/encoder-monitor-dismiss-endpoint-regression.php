<?php
// Execute the real JSON endpoint in an isolated copy, without installation secrets or a DB.
if (PHP_SAPI !== 'cli') { exit(1); }
if (($argv[1] ?? '') === '--child') {
    $dismissScenario = json_decode(base64_decode($argv[2]), true);
    $dismissFixtureRoot = $dismissScenario['root'];
    register_shutdown_function(function () use ($dismissFixtureRoot) {
        file_put_contents($dismissFixtureRoot . '/http-status', (string) (http_response_code() ?: 200));
    });
    $_SERVER['REQUEST_METHOD'] = $dismissScenario['method'];
    $_POST = $dismissScenario['post'];
    // The real logger echoes in CLI only; HTTP responses do not contain these log lines.
    ob_start(function ($output) { return preg_replace('/^\[[0-9 :-]+\] monitorDismiss:.*\R/m', '', $output); });
    require $dismissFixtureRoot . '/view/monitorDismiss.json.php';
    exit;
}

$dismissFixtureRoot = sys_get_temp_dir() . '/encoder-dismiss-endpoint-' . bin2hex(random_bytes(8));
foreach (['videos', 'objects', 'locale', 'view'] as $folder) { mkdir($dismissFixtureRoot . '/' . $folder, 0700, true); }
$dismissEndpoint = dirname(__DIR__) . '/view/monitorDismiss.json.php';
copy($dismissEndpoint, $dismissFixtureRoot . '/view/monitorDismiss.json.php');
$dismissFunctions = var_export(dirname(__DIR__) . '/objects/functions.php', true);
file_put_contents($dismissFixtureRoot . '/videos/configuration.php', '<?php
$global = ["systemRootPath" => $dismissScenario["root"] . "/", "webSiteRootURL" => "https://encoder.example.test/", "docker_vars" => $dismissScenario["root"] . "/missing"];
$_SESSION = ["encoderAdminCSRFToken" => str_repeat("a", 64)];
require ' . $dismissFunctions . ';
');
file_put_contents($dismissFixtureRoot . '/objects/Login.php', '<?php
class Login { public static function isAdmin() { global $dismissScenario; return $dismissScenario["admin"]; } }
');
file_put_contents($dismissFixtureRoot . '/objects/EncoderMonitor.php', '<?php
class EncoderMonitor {
    public static function dismissStreamerIssue($id, $version) {
        global $dismissScenario;
        file_put_contents($dismissScenario["root"] . "/writes", json_encode([$id, $version]));
        if (!empty($dismissScenario["throw"])) { throw new RuntimeException("PRIVATE_DATABASE_DETAIL"); }
        return $dismissScenario["current"];
    }
}
');
file_put_contents($dismissFixtureRoot . '/locale/function.php', '<?php // Translation fixture: use the real __() fallback.');

$checks = 0;
function checkDismissEndpoint($condition, $message)
{
    global $checks;
    if (!$condition) { throw new RuntimeException($message); }
    $checks++;
}
function runDismissEndpoint($overrides)
{
    global $dismissFixtureRoot;
    foreach (['writes', 'http-status'] as $name) {
        if (is_file($dismissFixtureRoot . '/' . $name)) { unlink($dismissFixtureRoot . '/' . $name); }
    }
    $scenario = array_replace(['root' => $dismissFixtureRoot, 'admin' => true, 'method' => 'POST',
        'current' => true, 'post' => ['streamers_id' => '2', 'version' => str_repeat('b', 64), 'csrfToken' => str_repeat('a', 64)]], $overrides);
    $process = proc_open([PHP_BINARY, '-d', 'log_errors=1', '-d', 'error_log=' . $dismissFixtureRoot . '/php-errors',
        __FILE__, '--child', base64_encode(json_encode($scenario))],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($process)) { throw new RuntimeException('Could not start fixture'); }
    fclose($pipes[0]);
    $body = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $stderr = stream_get_contents($pipes[2]); fclose($pipes[2]);
    $exit = proc_close($process);
    checkDismissEndpoint($exit === 0, 'endpoint completes without runtime errors: ' . $stderr . $body);
    $json = json_decode($body, true);
    checkDismissEndpoint(is_array($json) && isset($json['error']), 'response is valid JSON');
    return ['status' => (int) file_get_contents($dismissFixtureRoot . '/http-status'),
        'json' => $json, 'body' => $body, 'wrote' => is_file($dismissFixtureRoot . '/writes')];
}
try {
    $refused = [
        [['admin' => false], 403],
        [['method' => 'GET'], 405],
        [['post' => ['streamers_id' => '2', 'version' => str_repeat('b', 64)]], 403],
        [['post' => ['streamers_id' => '2', 'version' => str_repeat('b', 64), 'csrfToken' => ['bad']]], 403],
        [['post' => ['streamers_id' => '2', 'version' => str_repeat('b', 64), 'csrfToken' => str_repeat('c', 64)]], 403],
        [['post' => ['streamers_id' => '0', 'version' => str_repeat('b', 64), 'csrfToken' => str_repeat('a', 64)]], 400],
        [['post' => ['streamers_id' => '2', 'version' => ['bad'], 'csrfToken' => str_repeat('a', 64)]], 400],
    ];
    foreach ($refused as [$scenario, $status]) {
        $result = runDismissEndpoint($scenario);
        checkDismissEndpoint($result['status'] === $status && $result['json']['error'] && !$result['wrote'], 'refused request has no persistence side effect');
    }
    $result = runDismissEndpoint([]);
    checkDismissEndpoint($result['status'] === 200 && !$result['json']['error'] && $result['wrote'], 'valid admin POST reaches acknowledgement');
    $result = runDismissEndpoint(['current' => false]);
    checkDismissEndpoint($result['status'] === 409 && $result['json']['error'], 'changed notice returns conflict');
    $result = runDismissEndpoint(['throw' => true]);
    checkDismissEndpoint($result['status'] === 500 && strpos($result['body'], 'PRIVATE_DATABASE_DETAIL') === false, 'unexpected errors stay out of the response');
    echo "PASS: {$checks} monitor dismissal endpoint checks\n";
} finally {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dismissFixtureRoot, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) {
        if ($file->isDir()) { rmdir($file->getPathname()); } else { unlink($file->getPathname()); }
    }
    rmdir($dismissFixtureRoot);
}
