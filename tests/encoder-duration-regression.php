<?php
// Standalone integration test: php tests/encoder-duration-regression.php [encoder root]
// Uses real FFmpeg/FFprobe and temporary media, without a database or streamer requests.
if (PHP_SAPI !== 'cli') {
    exit(1);
}

class ObjectYPT {}
class Login {}
class Streamer {}

$global = [
    'systemRootPath' => rtrim($argv[1] ?? dirname(__DIR__), '/\\') . '/',
    'webSiteRootURL' => 'http://localhost/',
    'docker_vars' => __DIR__ . '/nonexistent-docker-vars'
];
require_once $global['systemRootPath'] . 'objects/Encoder.php';

$testDir = sys_get_temp_dir() . '/encoder-duration-' . bin2hex(random_bytes(8));
mkdir($testDir, 0700);
$global['logfile'] = $testDir . '/test.log';
ini_set('error_log', $global['logfile']);

function expectDuration($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
    echo "PASS: {$message}\n";
}

function createDurationFixture($arguments)
{
    $command = removeUserAgentIfNotURL(get_ffmpeg(true) . ' -v error -y ' . $arguments);
    exec($command . ' 2>&1', $output, $exitCode);
    if ($exitCode !== 0) {
        throw new RuntimeException('Fixture creation failed: ' . implode("\n", $output));
    }
}

try {
    $source = $testDir . '/source.mp4';
    createDurationFixture('-f lavfi -i color=size=160x90:rate=25 -t 2 -an -c:v libx264 '
        . escapeshellarg($source));
    expectDuration(Encoder::getDurationFromFile($source) === '0:00:02', 'ordinary MP4 duration');

    $hls = $testDir . '/encrypted';
    mkdir($hls);
    file_put_contents($hls . '/enc_fixture.key', random_bytes(16));
    file_put_contents($hls . '/keyinfo', "enc_fixture.key\n{$hls}/enc_fixture.key\n");
    createDurationFixture('-i ' . escapeshellarg($source) . ' -c copy -hls_time 1 -hls_list_size 0 '
        . '-hls_key_info_file ' . escapeshellarg($hls . '/keyinfo') . ' '
        . escapeshellarg($hls . '/media.m3u8'));
    file_put_contents($hls . '/index.m3u8', "#EXTM3U\n#EXT-X-STREAM-INF:BANDWIDTH=100000\nmedia.m3u8\n");

    expectDuration(Encoder::getDurationFromFile($hls . '/index.m3u8') === '0:00:02',
        'encrypted HLS master playlist with a .key file');
    expectDuration(Encoder::getDurationFromFile($hls . '.zip') === '0:00:02',
        'HLS ZIP duration resolves to its extracted playlist');

    // A real broken HLS output must still be rejected after a successful probe.
    unlink($hls . '/enc_fixture.key');
    $getDurationFromFile = [];
    expectDuration(Encoder::getDurationFromFile($hls . '/index.m3u8') === 'EE:EE:EE',
        'missing encryption key is rejected');
    file_put_contents($testDir . '/invalid.mp4', 'not a video');
    expectDuration(Encoder::getDurationFromFile($testDir . '/invalid.mp4') === 'EE:EE:EE',
        'invalid MP4 is rejected');
    expectDuration(Encoder::getDurationFromFile($testDir . '/missing.mp4') === 'EE:EE:EE',
        'missing media is rejected');
    expectDuration(Encoder::getDurationFromFile('') === 'EE:EE:EE', 'empty media path is rejected');
} catch (Throwable $error) {
    fwrite(STDERR, 'FAIL: ' . $error->getMessage() . "\n");
    $failed = true;
} finally {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($testDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($files as $file) {
        if ($file->isDir()) {
            rmdir($file->getPathname());
        } else {
            unlink($file->getPathname());
        }
    }
    rmdir($testDir);
}
exit(empty($failed) ? 0 : 1);
