<?php
// The queue runs on the CLI PHP from getPHP(), which may lack mbstring (e.g. a newer PHP installed
// next to the web server's one). Run it also as `php -n` to exercise the fallback without mbstring.
if (PHP_SAPI !== 'cli') { exit(1); }
class ObjectYPT {}
class Login {}
class Streamer {}
$global = ['systemRootPath' => rtrim($argv[1] ?? dirname(__DIR__), '/\\') . '/',
    'docker_vars' => __DIR__ . '/missing', 'webSiteRootURL' => 'https://encoder.example.test/'];
require $global['systemRootPath'] . 'objects/Encoder.php';
ini_set('error_log', PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null');
$checks = 0;
ob_start();
function mbOptionalExpect($condition, $message) {
    global $checks;
    if (!$condition) { throw new RuntimeException($message); }
    ++$checks;
}
function mbOptionalValidUtf8($text) {
    return preg_match('//u', $text) === 1;
}
try {
    mbOptionalExpect(encoderCutUtf8('short', 200) === 'short', 'short text is kept');
    mbOptionalExpect(encoderCutUtf8(null, 200) === '', 'null becomes an empty string');
    foreach (['é' => 2, '€' => 3, '😀' => 4] as $char => $bytes) {
        for ($missing = 1; $missing < $bytes; $missing++) {
            $text = str_repeat('a', 200 - $bytes + $missing) . $char . 'tail';
            $cut = encoderCutUtf8($text, 200);
            mbOptionalExpect($cut === str_repeat('a', 200 - $bytes + $missing), "{$bytes}-byte character split by {$missing} byte(s) is dropped");
            mbOptionalExpect(mbOptionalValidUtf8($cut), "{$bytes}-byte cut stays valid UTF-8");
        }
        $text = str_repeat('a', 200 - $bytes) . $char . 'tail';
        mbOptionalExpect(encoderCutUtf8($text, 200) === str_repeat('a', 200 - $bytes) . $char, "complete {$bytes}-byte character at the limit is kept");
    }
    mbOptionalExpect(encoderSubstrUtf8('ação rápida', 3) === 'açã', 'character limit counts multibyte characters once');
    mbOptionalExpect(encoderSubstrUtf8('abc', 10) === 'abc', 'short text is kept by the character limit');
    $invalid = encoderSubstrUtf8("\xFF\xFEabcdef", 3);
    mbOptionalExpect(strlen($invalid) <= 3, 'invalid UTF-8 never exceeds the requested length');

    $summary = Format::summarizeFFmpegError(str_repeat("Error: invalid packet é\n", 300) . "error ação final\n", 1);
    mbOptionalExpect(mbOptionalValidUtf8($summary), 'FFmpeg summary stays valid UTF-8');
    mbOptionalExpect(strlen($summary) <= 1200 * 4, 'FFmpeg summary is bounded');

    $encoder = (new ReflectionClass('Encoder'))->newInstanceWithoutConstructor();
    $encoder->setStatus_obs(str_repeat('x', 199) . 'ção', false);
    mbOptionalExpect($encoder->getStatus_obs() === str_repeat('x', 199), 'setStatus_obs() works and keeps 200 bytes without splitting');
    $encoder->setReturn_vars('{}');
    $encoder->setFailureDetails('encoding', str_repeat('ç', 2000), 1);
    $failure = json_decode($encoder->getReturn_vars())->failure;
    mbOptionalExpect(strlen($failure->message) === 3200 && mbOptionalValidUtf8($failure->message), 'setFailureDetails() keeps 1600 characters');

    ob_end_clean();
    echo 'PASS: ' . $checks . ' UTF-8 truncation checks (mbstring ' . (extension_loaded('mbstring') ? 'loaded' : 'not loaded') . ')' . PHP_EOL;
} catch (Throwable $error) {
    ob_end_clean();
    fwrite(STDERR, 'FAIL: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
exit(0);
