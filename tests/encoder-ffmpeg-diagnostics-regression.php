<?php
if (PHP_SAPI !== 'cli') { exit(1); }
class ObjectYPT {}
class Login {}
class Streamer {}
$global = ['systemRootPath' => rtrim($argv[1] ?? dirname(__DIR__), '/\\') . '/',
    'docker_vars' => __DIR__ . '/missing', 'webSiteRootURL' => 'https://encoder.example.test/'];
require $global['systemRootPath'] . 'objects/Encoder.php';
$dir = sys_get_temp_dir() . '/encoder-ffmpeg-diagnostic-' . bin2hex(random_bytes(6));
mkdir($dir);
mkdir($dir . '/videos');
$global['systemRootPath'] = $dir . '/';
ini_set('error_log', $dir . '/test.log');
$checks = 0;
ob_start();
function diagnosticExpect($condition, $message) {
    global $checks;
    if (!$condition) { throw new RuntimeException($message); }
    ++$checks;
}
try {
    $file = $dir . '/videos/high-fps.mp4';
    $command = removeUserAgentIfNotURL(get_ffmpeg(true) . ' -v error -y -f lavfi -i testsrc2=size=160x90:rate=1000 -t 2 -vf "select=not(mod(n\,33))" -vsync 0 -enc_time_base -1 -c:v libx264 ' . escapeshellarg($file));
    exec($command . ' 2>&1', $lines, $exit);
    diagnosticExpect($exit === 0, 'test media generation: ' . implode(' ', $lines));
    diagnosticExpect(!Format::videoFileHasErrors($file), 'valid VFR/high-fps MP4 is not rejected by null-muxer timestamp rounding');
    $log = $dir . '/conversion.log';
    file_put_contents($log, "Output #0, mp4, to '" . str_replace('\\', '/', $file) . "':\nframe=60\n");
    diagnosticExpect(!Format::progressFileHasVideosWithErrors($log), 'valid output reported by FFmpeg passes post-conversion validation');
    file_put_contents($file, 'not a video');
    diagnosticExpect(Format::videoFileHasErrors($file), 'nonzero decoder exit rejects corrupt media');
    diagnosticExpect(Format::getLastValidationError() !== '', 'decoder failure retains a useful explanation');
    file_put_contents($log, 'Error opening input https://user:SECRET@one.example/file?token=PRIVATE: Invalid data found when processing input');
    $message = Format::getFFmpegFailureMessage($log, 1);
    diagnosticExpect(strpos($message, 'exit 1') !== false && strpos($message, 'Invalid data') !== false, 'ffmpeg exit and concrete cause are retained');
    diagnosticExpect(strpos($message, 'SECRET') === false && strpos($message, 'PRIVATE') === false, 'URL credentials and query tokens are not displayed');
    file_put_contents($log, 'frame=0');
    diagnosticExpect(Format::progressFileHasVideosWithErrors($log), 'missing declared output is not silently considered success');
    diagnosticExpect(Format::videoFileHasErrors($dir . '/missing.mp4'), 'missing output is rejected');
    ob_end_clean();
    echo 'PASS: ' . $checks . ' real FFmpeg diagnostic checks' . PHP_EOL;
} catch (Throwable $error) {
    ob_end_clean();
    fwrite(STDERR, 'FAIL: ' . $error->getMessage() . PHP_EOL);
    $failed = true;
} finally {
    foreach (glob($dir . '/videos/*') as $path) { unlink($path); }
    rmdir($dir . '/videos');
    foreach (glob($dir . '/*') as $path) { unlink($path); }
    rmdir($dir);
}
exit(empty($failed) ? 0 : 1);
