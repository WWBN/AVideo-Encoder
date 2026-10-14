<?php
// Standalone regression test, without an installed database or Streamer configuration.
// php tests/encoder-ffmpeg-argv-regression.php [--ffmpeg]
if (PHP_SAPI !== 'cli') {
    exit(1);
}

$global = ['webSiteRootURL' => 'https://encoder.example.test/', 'allowPrivateNetworkURLs' => true];
require_once dirname(__DIR__) . '/objects/functions.php';

if (($argv[1] ?? '') === 'ffmpeg-wrapper') {
    // Exercise the endpoint's real fallback when palette generation fails.
    if (substr(end($argv), -11) === 'palette.png') { exit(1); }
    exit(execArgv(array_merge(['ffmpeg'], array_slice($argv, 2))));
}

function expectArgv($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
    echo "PASS: {$message}\n";
}

// Endpoint child processes run the actual source, replacing only installation bootstrapping.
if (($argv[1] ?? '') === 'endpoint') {
    $global['systemRootPath'] = $argv[3] . '/';
    $_sys_get_temp_dir = $argv[3];
    $_GET = ['base64Url' => base64_encode($argv[4]), 'format' => $argv[5], 'time' => 0];
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    class Encoder
    {
        public static function parseSecondsToDuration($seconds) { return parseSecondsToDuration($seconds); }
        public static function getThumbsFromLink($url, $streamer) { return json_encode([$url, $streamer]); }
    }
    class Login { public static function getStreamerId() { return $GLOBALS['argv'][6] ?? 'streamer-a'; } }
    if (($argv[6] ?? '') === 'no-palette') {
        $global['ffmpeg'] = PHP_BINARY . ' "' . __FILE__ . '" ffmpeg-wrapper';
    }
    $endpointSource = file_get_contents(dirname(__DIR__) . '/objects/' . $argv[2]);
    $endpointSource = preg_replace('/^[ \t]*require_once .*;\r?$/m', '', $endpointSource);
    ob_start();
    eval(substr($endpointSource, 5));
    exit;
}

$testDir = sys_get_temp_dir() . '/encoder-ffmpeg-argv-' . bin2hex(random_bytes(8));
mkdir($testDir, 0700);
ini_set('error_log', $testDir . '/test.log');
$server = null;
try {
    foreach (['"/opt/ffmpeg tools/ffmpeg"', "'/opt/ffmpeg tools/ffmpeg'"] as $executable) {
        $global['ffmpeg'] = 'nice -n 10 ' . $executable;
        expectArgv(getFFmpegArgv(false) === ['nice', '-n', '10', '/opt/ffmpeg tools/ffmpeg'], 'Quoted Encoder wrapper path');
    }
    unset($global['ffmpeg']);
    expectArgv(!in_array('-user_agent', getFFmpegArgv(false), true), 'Local input has no user agent');

    $url = 'https://example.com/video.mp4?token=~~~&other=1';
    $encoded = base64_encode($url);
    foreach ([$encoded, rtrim($encoded, '='), strtr($encoded, '+/', '-_'), str_replace('+', ' ', $encoded)] as $input) {
        expectArgv(decodeBase64UrlParameter($input) === $url, 'Base64 URL round trip');
        $_GET = ['base64Url' => $input];
        $linkSource = file_get_contents(dirname(__DIR__) . '/objects/getLinkInfo.json.php');
        $linkStart = strpos($linkSource, '$link = ');
        $linkEnd = strpos($linkSource, 'if ($link === false)', $linkStart);
        eval(substr($linkSource, $linkStart, $linkEnd - $linkStart));
        expectArgv($link === $url, 'getLinkInfo uses the same decoder as its security guard');
        $imageStart = strpos($linkSource, '$base64Url = ');
        $imageEnd = strpos($linkSource, '$obj->streamers_id', $imageStart);
        eval(substr($linkSource, $imageStart, $imageEnd - $imageStart));
        expectArgv($obj->imageJPGLink === $global['webSiteRootURL'] . 'getImage/' . $encoded . '/jpg', 'Generated thumbnail URL uses canonical base64 without spaces');
    }
    foreach (['a', '====', base64_encode("https://example.com/\0")] as $input) {
        expectArgv(decodeBase64UrlParameter($input) === false, 'Invalid base64 rejected');
    }
    $rewriteSource = file_get_contents(dirname(__DIR__) . '/.htaccess');
    foreach (['getImage' => '/jpg', 'getImageMP4' => '/jpg/0', 'getSpiritsFromVideo' => '/160/100/00:00:04', 'getLinkInfo' => ''] as $route => $suffix) {
        preg_match('/RewriteRule\s+(\^' . $route . '\/\S+)/', $rewriteSource, $rule);
        foreach ([$encoded, rtrim(strtr($encoded, '+/', '-_'), '=')] as $input) {
            expectArgv(preg_match('#' . $rule[1] . '#', $route . '/' . $input . $suffix) === 1, $route . ' accepts both base64 alphabets');
        }
    }
    foreach (['0.0.0.0', '127.0.0.1', '224.0.0.1', '::ffff:127.0.0.1', '64:ff9b::7f00:1', '2002:7f00:1::'] as $ip) {
        expectArgv(ip_is_private($ip), 'Private/reserved address blocked: ' . $ip);
    }
    foreach (['8.8.8.8', '1.1.1.1', '2001:4860:4860::8888', '::ffff:8.8.8.8', '64:ff9b::808:808', '2002:808:808::'] as $ip) {
        expectArgv(!ip_is_private($ip), 'Public address preserved: ' . $ip);
    }
    $global['allowPrivateNetworkURLs'] = false;
    $global['trustedPrivateDomains'] = ['streamer-a.example.test', 'streamer-b.example.test'];
    foreach ($global['trustedPrivateDomains'] as $host) {
        expectArgv(getExternalHttpUrlForShell('https://' . $host . '/video.mp4') !== false, 'Independent trusted Streamer: ' . $host);
    }
    $global['allowPrivateNetworkURLs'] = true;

    $literal = 'https://example.com/?a=1&b=2;$(echo unchanged)';
    expectArgv(execArgv([PHP_BINARY, '-r', 'echo $argv[1];', $literal], $output) === 0 && $output === [$literal], 'Arguments reach the process literally');
    $asyncFile = $testDir . '/async.json';
    $pid = execArgvAsync([PHP_BINARY, '-r', 'file_put_contents($argv[1], json_encode($argv[2]));', $asyncFile, $literal]);
    for ($attempt = 0; $attempt < 100 && !is_file($asyncFile); $attempt++) {
        usleep(20000);
    }
    expectArgv(is_file($asyncFile) && json_decode(file_get_contents($asyncFile)) === $literal, 'Async arguments reach the process literally');
    expectArgv(isWindows() || ctype_digit($pid), 'Async launcher returns its PID');

    foreach (['https://youtube.com/watch?v=one&other=two', 'https://vimeo.com/123456', 'https://rumble.com/example.html'] as $social) {
        foreach (['streamer-a', 'streamer-b'] as $streamer) {
            foreach (['getImage.php', 'getImageMP4.php'] as $endpoint) {
                execArgv([PHP_BINARY, __FILE__, 'endpoint', $endpoint, $testDir, $social, 'jpg', $streamer], $output);
                expectArgv(json_decode(implode("\n", $output), true) === [$social, $streamer], $endpoint . ' preserves the social URL and requesting Streamer');
            }
        }
    }

    if (in_array('--ffmpeg', $argv, true)) {
        $fixture = $testDir . '/sample.mp4';
        $create = array_merge(getFFmpegArgv(false, true), ['-v', 'error', '-y', '-f', 'lavfi', '-i', 'testsrc2=size=160x90:rate=10', '-f', 'lavfi', '-i', 'sine=frequency=440:sample_rate=44100', '-t', '5', '-c:v', 'libx264', '-threads', '1', '-c:a', 'aac', '-movflags', '+faststart', $fixture]);
        expectArgv(execArgv($create) === 0 && filesize($fixture) > 0, 'Faststart MP4 fixture generated');
        // A private, loopback-only PHP server inside the network-isolated test container.
        $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
        $address = stream_socket_get_name($socket, false);
        fclose($socket);
        $server = proc_open([PHP_BINARY, '-S', $address, '-t', $testDir], [1 => ['file', $testDir . '/server.log', 'a'], 2 => ['redirect', 1]], $pipes);
        for ($attempt = 0; $attempt < 100; $attempt++) {
            $ready = @stream_socket_client('tcp://' . $address, $errno, $error, 0.05);
            if ($ready) { fclose($ready); break; }
            usleep(20000);
        }
        $remote = 'http://' . $address . '/sample.mp4?a=1&b=2';
        mkdir($testDir . '/view/img', 0700, true);
        foreach (['OnAir.png', 'OnAir.jpg', 'notfound.gif'] as $dummy) {
            file_put_contents($testDir . '/view/img/' . $dummy, 'placeholder');
        }
        foreach (['getImage.php' => ['png', 'jpg', 'gif', 'webp'], 'getImageMP4.php' => ['jpg', 'gif', 'webp']] as $endpoint => $formats) {
            foreach ($formats as $format) {
                $destination = $testDir . '/' . md5($remote) . '.' . $format;
                @unlink($destination);
                $process = proc_open([PHP_BINARY, __FILE__, 'endpoint', $endpoint, $testDir, $remote, $format], [1 => ['file', $testDir . '/response', 'w'], 2 => ['file', $testDir . '/endpoint.log', 'a']], $pipes);
                $exit = proc_close($process);
                for ($attempt = 0; $attempt < 300; $attempt++) {
                    clearstatcache(true, $destination);
                    if (is_file($destination) && filesize($destination) > 0) { break; }
                    usleep(20000);
                }
                expectArgv($exit === 0 && is_file($destination) && filesize($destination) > 0, $endpoint . ' generates ' . $format . ' from a URL with query parameters');
                $image = getimagesize($destination);
                expectArgv(is_array($image) && $image[0] > 0 && $image[1] > 0, $endpoint . ' produces valid ' . $format . ' media');
            }
        }
        $endpointLog = file_get_contents($testDir . '/endpoint.log');
        if (strpos($endpointLog, 'Create Gif with Pallet:') === false) {
            // This filter error also exists in HEAD before the argv changes: flags belongs to
            // scale, but the old filter appends it to pad. Do not hide it as an argv regression.
            expectArgv(strpos($endpointLog, "Error applying option 'flags' to filter 'pad'") !== false, 'Existing palette filter failure falls back to a valid GIF');
            echo "KNOWN BASELINE FAILURE: GIF palette filters append flags=lanczos to pad; the palette branch is not verified.\n";
        } else {
            echo "PASS: GIF palette branch executed\n";
        }
        $gif = $testDir . '/' . md5($remote) . '.gif';
        $palette = $testDir . '/' . md5($remote) . 'palette.png';
        @unlink($gif);
        @unlink($palette);
        $process = proc_open([PHP_BINARY, __FILE__, 'endpoint', 'getImageMP4.php', $testDir, $remote, 'gif', 'no-palette'], [1 => ['file', $testDir . '/response', 'w'], 2 => ['file', $testDir . '/endpoint.log', 'a']], $pipes);
        expectArgv(proc_close($process) === 0 && is_array(getimagesize($gif)), 'GIF fallback works when palette generation fails');
    }
} catch (Throwable $exception) {
    if (is_file($testDir . '/endpoint.log')) { fwrite(STDERR, file_get_contents($testDir . '/endpoint.log')); }
    throw $exception;
} finally {
    if (is_resource($server)) { proc_terminate($server); proc_close($server); }
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($testDir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) {
        if ($file->isDir()) { rmdir($file->getPathname()); } else { unlink($file->getPathname()); }
    }
    rmdir($testDir);
}
