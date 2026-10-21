<?php
// php tests/encoder-watermark-regression.php [encoder root]
// Uses real FFmpeg and temporary files; does not load the installation configuration.
if (PHP_SAPI !== 'cli') { exit(1); }
$watermarkTestRoot = rtrim($argv[1] ?? dirname(__DIR__), '/\\') . '/';
$global = ['systemRootPath' => $watermarkTestRoot, 'webSiteRootURL' => 'http://localhost/'];
require_once $watermarkTestRoot . 'objects/functions.php';
$watermarkTestSource = file_get_contents($watermarkTestRoot . 'view/watermark.php');
eval(substr($watermarkTestSource, strpos($watermarkTestSource, 'function getIndexM3U8(')));

if (($argv[2] ?? '') === '--manifest') {
    $localFileDownloadDir = $argv[3] . '/source';
    $localFileDownload_index = $localFileDownloadDir . '/index.m3u8';
    $outputPath = $argv[3] . '/viewer/240';
    $outputURL = 'https://encoder.example/videos/viewer/240';
    $encFile = $outputPath . '/enc_watermarked.key';
    $encFileURL = $outputURL . '/enc_watermarked.key';
    $jsonFile = $outputPath . '/.obj.log';
    $keyInfoFile = $outputPath . '/.keyInfo';
    $hls_time = 10;
    ob_start();
    getIndexM3U8();
    exit;
}

$watermarkTestDir = sys_get_temp_dir() . '/encoder-watermark-' . bin2hex(random_bytes(8));
mkdir($watermarkTestDir, 0700);
ini_set('error_log', $watermarkTestDir . '/test.log');
function watermarkExpect($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
    echo 'PASS: ' . $message . PHP_EOL;
}
set_error_handler(function ($severity, $message) { throw new RuntimeException($message); });
try {
    $outputPath = $watermarkTestDir . '/viewer/240';
    $localFileDownloadDir = $watermarkTestDir . '/source';
    createSymbolicLinks($localFileDownloadDir, $outputPath);
    watermarkExpect(is_dir($outputPath), 'cold cache creates the full output directory without opening a missing source');
    make_path($localFileDownloadDir . '/');
    $cmd = removeUserAgentIfNotURL(get_ffmpeg(true) . ' -v error -y -f lavfi -i color=c=blue:size=320x180:rate=25 -t 2 -c:v libx264 -g 25 ' . escapeshellarg($localFileDownloadDir . '/000.ts'));
    exec($cmd . ' 2>&1', $output, $code);
    watermarkExpect($code === 0, 'real FFmpeg creates the source segment');
    file_put_contents($localFileDownloadDir . '/index.m3u8', "#EXTM3U\n#EXT-X-VERSION:3\n#EXT-X-TARGETDURATION:10\n#EXT-X-PLAYLIST-TYPE:VOD\n#EXTINF:2.0,\n000.ts\n#EXT-X-ENDLIST\n");
    createSymbolicLinks($localFileDownloadDir, $outputPath);
    watermarkExpect(is_link($outputPath . '/000.ts') && allTSFilesAreSymlinks($outputPath), 'unconverted cache initially uses the source symlink');
    $skipFirstSegments = 30;
    $watermark_fontsize = '(h/20)';
    $watermark_color = 'yellow';
    $watermark_opacity = 0.5;
    $watermarkCodec = ' -c:v libx264 -acodec copy -movflags +faststart ';
    $text = 'QA-watermark';
    $keyInfoFile = $outputPath . '/.keyInfo';
    $encFile = $outputPath . '/enc_watermarked.key';
    createFirstSegment();
    watermarkExpect(!is_link($outputPath . '/000.ts') && filesize($outputPath . '/000.ts') > 0, 'first segment is burned into real media, not left as a symlink');
    watermarkExpect(hash_file('sha256', $outputPath . '/000.ts') !== hash_file('sha256', $localFileDownloadDir . '/000.ts'), 'watermarked bytes differ from the source');
    watermarkExpect(!allTSFilesAreSymlinks($outputPath), 'a one-segment video is complete after watermarking');
    $pipes = [];
    $process = proc_open([PHP_BINARY, __FILE__, $watermarkTestRoot, '--manifest', $watermarkTestDir], [1 => ['pipe', 'w'], 2 => ['file', $watermarkTestDir . '/child.log', 'a']], $pipes);
    $manifest = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $code = proc_close($process);
    watermarkExpect($code === 0 && strpos($manifest, '#EXTM3U') === 0 && strpos($manifest, '#EXT-X-ENDLIST') !== false, 'warm request returns a complete VOD playlist');
    watermarkExpect(strpos($manifest, 'https://encoder.example/videos/viewer/240/000.ts') !== false, 'playlist references viewer-specific output');
    copy($localFileDownloadDir . '/000.ts', $localFileDownloadDir . '/001.ts');
    createSymbolicLinks($localFileDownloadDir, $outputPath);
    watermarkExpect(allTSFilesAreSymlinks($outputPath), 'longer videos keep converting beyond the first watermarked segment');
    $process = proc_open([PHP_BINARY, __FILE__, $watermarkTestRoot, '--manifest', $watermarkTestDir], [1 => ['pipe', 'w'], 2 => ['file', $watermarkTestDir . '/child.log', 'a']], $pipes);
    $manifest = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $code = proc_close($process);
    watermarkExpect($code === 0 && substr_count($manifest, '/240/00') === 2, 'new viewer playlist waits for fresh segments instead of returning an empty response');
} catch (Throwable $error) {
    fwrite(STDERR, 'FAIL: ' . $error->getMessage() . PHP_EOL);
    $failed = true;
} finally {
    restore_error_handler();
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($watermarkTestDir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) {
        if ($file->isDir() && !$file->isLink()) { rmdir($file->getPathname()); } else { unlink($file->getPathname()); }
    }
    rmdir($watermarkTestDir);
}
exit(empty($failed) ? 0 : 1);
