<?php
// Standalone regression test, without an installed database or Streamer configuration (Linux only).
// php tests/encoder-watermark-request-regression.php [encoder root]
// Runs the real view/watermark.php in a child process from a temporary copy of the Encoder (stub
// configuration.php that still applies objects/security.php) and checks that videos_id and
// resolution, also when sent inside the base64 "_" parameter, only reach the cache path and the
// shell commands as integers.
if (PHP_SAPI !== 'cli') {
    exit(1);
}
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    echo "SKIP: view/watermark.php runs sh commands; run this test on Linux\n";
    exit(0);
}

if (($argv[1] ?? '') === '--child') {
    $watermarkRequestScenario = json_decode(base64_decode($argv[2]), true);
    $_GET = $watermarkRequestScenario['get'];
    $_REQUEST = $_GET;
    require $watermarkRequestScenario['root'] . 'view/watermark.php';
    exit(0);
}

function expectWatermarkRequest($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
    echo "PASS: {$message}\n";
}

function removeWatermarkRequestDir($dir)
{
    if (is_link($dir) || is_file($dir)) {
        unlink($dir);
        return;
    }
    if (!is_dir($dir)) {
        return;
    }
    foreach (scandir($dir) as $file) {
        if ($file !== '.' && $file !== '..') {
            removeWatermarkRequestDir($dir . '/' . $file);
        }
    }
    rmdir($dir);
}

function runWatermarkRequestScenario($encoderRoot, array $get)
{
    $dir = sys_get_temp_dir() . '/encoder-watermark-request-' . bin2hex(random_bytes(8));
    $root = $dir . '/encoder/';
    foreach (['view', 'objects', 'videos'] as $folder) {
        mkdir($root . $folder, 0700, true);
    }
    copy($encoderRoot . 'view/watermark.php', $root . 'view/watermark.php');
    copy($encoderRoot . 'objects/functions.php', $root . 'objects/functions.php');
    copy($encoderRoot . 'objects/security.php', $root . 'objects/security.php');
    foreach (['Encoder', 'Login', 'Streamer'] as $class) {
        file_put_contents($root . "objects/{$class}.php", "<?php\n");
    }
    file_put_contents($root . 'videos/configuration.php', "<?php\n\$global = ['systemRootPath' => " . var_export($root, true) . ", 'webSiteRootURL' => 'https://encoder.example.test/'];\nrequire_once \$global['systemRootPath'] . 'objects/security.php';\n");

    $scenario = ['root' => $root, 'get' => $get];
    $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' --child ' . escapeshellarg(base64_encode(json_encode($scenario)));
    $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $dir);
    stream_get_contents($pipes[1]);
    stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($process);

    $cache = $root . 'videos/watermarked/encoder.example.test/';
    $result = [
        'markers' => glob($dir . '/marker-*') ?: [],
        'cacheEntries' => is_dir($cache) ? array_values(array_diff(scandir($cache), ['.', '..'])) : [],
        'sourceIndex' => is_file($cache . '7/480/index.m3u8'),
        'escaped' => is_dir($dir . '/outside') || is_dir($root . 'outside') || is_dir($root . 'videos/outside'),
    ];
    removeWatermarkRequestDir($dir);
    return $result;
}

$encoderRoot = rtrim($argv[1] ?? dirname(__DIR__), '/') . '/';
$file = 'https://encoder.example.test/videos/clip_480.mp4';

// The Streamer (SecureVideosDirectory::redirectToEncoderWatermarkIfNeed()) sends every value in "_".
$legitimate = runWatermarkRequestScenario($encoderRoot, ['_' => base64_encode(json_encode([
    'file' => $file,
    'watermark_token' => 'token',
    'watermark_text' => 'viewer',
    'videos_id' => 7,
    'resolution' => 480,
    'protectionToken' => 'x',
    'isMobile' => false,
]))]);
expectWatermarkRequest($legitimate['cacheEntries'] === ['7'], 'legitimate request keeps the videos_id cache folder');
expectWatermarkRequest($legitimate['sourceIndex'], 'legitimate request still prepares the source download in <videos_id>/<resolution>');

$shellValue = '$(touch marker-%s)';
$injected = runWatermarkRequestScenario($encoderRoot, ['_' => base64_encode(json_encode([
    'file' => $file,
    'watermark_token' => 'token',
    'watermark_text' => 'viewer',
    'videos_id' => '7' . sprintf($shellValue, 'videos-id'),
    'resolution' => '480' . sprintf($shellValue, 'resolution'),
]))]);
expectWatermarkRequest($injected['markers'] === [], 'shell syntax in videos_id/resolution inside "_" is never executed');
expectWatermarkRequest($injected['cacheEntries'] === ['7'] && $injected['sourceIndex'], 'videos_id/resolution inside "_" are reduced to their integer values');

$traversal = runWatermarkRequestScenario($encoderRoot, [
    'file' => $file,
    'watermark_token' => 'token',
    'watermark_text' => 'viewer',
    'videos_id' => '7',
    'resolution' => '../../../../outside',
]);
expectWatermarkRequest(!$traversal['escaped'], 'a direct resolution parameter cannot move the cache outside the videos_id folder');
expectWatermarkRequest($traversal['cacheEntries'] === ['7'], 'a direct resolution parameter stays inside the videos_id folder');
