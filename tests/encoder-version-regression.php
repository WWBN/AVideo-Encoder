<?php
// php tests/encoder-version-regression.php -- no database or network required.
if (PHP_SAPI !== 'cli') { exit(1); }
require_once dirname(__DIR__) . '/objects/EncoderVersion.php';
function _error_log($message) { /* Expected unavailable/rate-limit cases in this fixture. */ }

function expectVersion($condition, $message)
{
    if (!$condition) { throw new RuntimeException($message); }
    echo "PASS: {$message}\n";
}

$sha = str_repeat('a', 40);
$remoteSha = str_repeat('b', 40);
$installed = ['commit' => $sha, 'version' => '8.2', 'branch' => 'master', 'modified' => false];
foreach (['ahead' => 'update', 'behind' => 'newer', 'identical' => 'current', 'diverged' => 'diverged'] as $remoteState => $expected) {
    $result = EncoderVersion::check($installed, function ($path) use ($remoteState, $remoteSha) {
        if ($path === '/releases/latest') { return ['tag_name' => 'v8.3.0']; }
        if ($path === '/commits/master') { return ['sha' => $remoteSha]; }
        return ['status' => $remoteState, 'ahead_by' => 7];
    });
    expectVersion($result['release']['state'] === $expected && $result['master']['state'] === $expected,
        'correct comparison direction: ' . $remoteState);
    expectVersion($result['release']['commits'] === 7, 'preserves new commit count');
    expectVersion(strpos($result['release']['changesUrl'], $sha . '...v8.3.0') !== false, 'comparison links use installed commit as base');
}
$calls = [];
$result = EncoderVersion::check($installed, function ($path) use ($sha, &$calls) {
    $calls[] = $path;
    return $path === '/commits/master' ? ['sha' => $sha] : null;
});
expectVersion($result['master']['state'] === 'current' && count($calls) === 2, 'matching SHA needs no extra API request');
expectVersion($result['release'] === null, 'missing release never claims up to date');
$result = EncoderVersion::check($installed, function ($path) use ($remoteSha) {
    if ($path === '/releases/latest') { return ['tag_name' => '8.2']; }
    if ($path === '/commits/master') { return ['sha' => $remoteSha]; }
    return ['message' => 'API rate limit exceeded'];
});
expectVersion($result['release']['state'] === 'unknown' && $result['master']['state'] === 'unknown', 'rate limits and invalid comparisons remain unknown');
$calls = [];
$result = EncoderVersion::check(['commit' => ''], function ($path) use ($remoteSha, &$calls) {
    $calls[] = $path;
    return $path === '/commits/master' ? ['sha' => $remoteSha] : ['tag_name' => 'v8.3.0'];
});
expectVersion(count($calls) === 2 && $result['release']['state'] === 'unknown', 'missing installed metadata does not guess from database version');
foreach ([['tag_name' => 'v8.3.0', 'prerelease' => true], ['tag_name' => 'v8.3.0', 'draft' => true], ['tag_name' => '<bad>']] as $release) {
    $result = EncoderVersion::check($installed, function () use ($release) { return $release; });
    expectVersion($result['release'] === null, 'rejects non-stable or invalid release');
}

$now = 1700000000;
expectVersion(EncoderVersion::retryAt(200, [], $now) === 0, 'ordinary responses do not start a cooldown');
expectVersion(EncoderVersion::retryAt(403, ['X-RateLimit-Remaining' => '0', 'X-RateLimit-Reset' => (string) ($now + 7200)], $now) === $now + 7200,
    'primary limit respects reset beyond the cache TTL');
expectVersion(EncoderVersion::retryAt(429, ['Retry-After' => '120'], $now) === $now + 120, 'secondary limit respects Retry-After seconds');
expectVersion(EncoderVersion::retryAt(429, ['Retry-After' => gmdate('D, d M Y H:i:s \G\M\T', $now + 180)], $now) === $now + 180,
    'Retry-After HTTP dates are supported');
expectVersion(EncoderVersion::retryAt(403, ['retry-after' => '120', 'x-ratelimit-remaining' => '0', 'x-ratelimit-reset' => (string) ($now + 300)], $now) === $now + 300,
    'uses the later of both server deadlines');
expectVersion(EncoderVersion::retryAt(429, [], $now) === $now + 3600, 'missing limit headers pause for an hour');
expectVersion(EncoderVersion::retryAt(403, ['retry-after' => 'invalid'], $now) === $now + 3600, 'invalid limit headers pause for an hour');
expectVersion(EncoderVersion::retryAt(429, ['retry-after' => '0'], $now) === $now + 60, 'zero wait still prevents immediate retries');
expectVersion(EncoderVersion::retryAt(200, ['x-ratelimit-remaining' => '0', 'x-ratelimit-reset' => (string) ($now + 120)], $now) === $now + 120,
    'last successful request starts the shared cooldown');

$dir = sys_get_temp_dir() . '/encoder-version-' . bin2hex(random_bytes(8));
mkdir($dir);
mkdir($dir . '/objects');
mkdir($dir . '/.git');
try {
    file_put_contents($dir . '/.git/HEAD', $sha . "\n");
    expectVersion(EncoderVersion::installed($dir)['commit'] === $sha, 'readable detached HEAD without a valid Git object database');
    file_put_contents($dir . '/.git/HEAD', "ref: refs/heads/master\n");
    file_put_contents($dir . '/.git/packed-refs', "# pack-refs with: peeled\n{$sha} refs/heads/master\n");
    $result = EncoderVersion::installed($dir);
    expectVersion($result['commit'] === $sha && $result['branch'] === 'master', 'packed branch metadata fallback');
    mkdir($dir . '/.git/refs');
    mkdir($dir . '/.git/refs/heads');
    file_put_contents($dir . '/.git/refs/heads/master', $remoteSha . "\n");
    expectVersion(EncoderVersion::installed($dir)['commit'] === $remoteSha, 'loose refs override stale packed refs');
    unlink($dir . '/.git/refs/heads/master');
    rmdir($dir . '/.git/refs/heads');
    rmdir($dir . '/.git/refs');
    unlink($dir . '/.git/HEAD');
    unlink($dir . '/.git/packed-refs');
    rmdir($dir . '/.git');
    copy(dirname(__DIR__) . '/objects/encoder-build.json', $dir . '/objects/encoder-build.json');
    expectVersion(EncoderVersion::installed($dir)['commit'] === '', 'unexpanded archive placeholder is not an installed version');
    file_put_contents($dir . '/objects/encoder-build.json', json_encode(['commit' => $sha, 'version' => 'v8.3.0']));
    $result = EncoderVersion::installed($dir);
    expectVersion($result['commit'] === $sha && $result['version'] === 'v8.3.0', 'archive or image build metadata');
    unlink($dir . '/objects/encoder-build.json');
    expectVersion(EncoderVersion::installed($dir)['commit'] === '', 'no metadata never searches for a parent repository');
    $cacheFile = $dir . '/encoder-version-' . hash('sha256', '/releases/latest') . '.json';
    file_put_contents($cacheFile, json_encode(['data' => ['tag_name' => 'v8.3.0']]));
    touch($cacheFile, time() - 1800);
    expectVersion(EncoderVersion::github('/releases/latest', $dir)['tag_name'] === 'v8.3.0', 'reuses cached GitHub responses without networking');
    file_put_contents($cacheFile, json_encode(['data' => null]));
    expectVersion(EncoderVersion::github('/releases/latest', $dir) === null, 'cached failure remains unknown without retrying GitHub');
    unlink($cacheFile);

    $requests = 0;
    $success = function ($path) use (&$requests) {
        $requests++;
        return ['status' => 200, 'headers' => [], 'data' => ['tag_name' => 'v8.4.0']];
    };
    file_put_contents($cacheFile, json_encode(['data' => ['tag_name' => 'v8.3.0']]));
    touch($cacheFile, time() - 3601);
    expectVersion(EncoderVersion::github('/releases/latest', $dir, $success)['tag_name'] === 'v8.4.0' && $requests === 1,
        'expired hourly cache is refreshed once');
    EncoderVersion::github('/releases/latest', $dir, $success);
    expectVersion($requests === 1, 'repeated checks reuse the refreshed result');

    $until = time() + 7200;
    EncoderVersion::github('/commits/master', $dir, function () use (&$requests, $until) {
        $requests++;
        return ['status' => 403, 'headers' => ['x-ratelimit-remaining' => '0', 'x-ratelimit-reset' => (string) $until], 'data' => null];
    });
    $rateFile = $dir . '/encoder-version-rate-limit.json';
    expectVersion(json_decode(file_get_contents($rateFile), true)['retryAt'] === $until, 'rate-limit deadline persists across requests');
    expectVersion(EncoderVersion::github('/compare/new-commit...master', $dir, $success) === null && $requests === 2,
        'cooldown blocks another endpoint and new installed commits');
    expectVersion(EncoderVersion::github('/releases/latest', $dir, $success)['tag_name'] === 'v8.4.0' && $requests === 2,
        'valid cached results remain available during cooldown');
    touch($cacheFile, time() - 3601);
    expectVersion(EncoderVersion::github('/releases/latest', $dir, $success) === null && $requests === 2,
        'cache expiry does not bypass a longer server deadline');
    file_put_contents($rateFile, json_encode(['retryAt' => time() - 1]));
    expectVersion(EncoderVersion::github('/releases/latest', $dir, $success)['tag_name'] === 'v8.4.0' && $requests === 3,
        'requests resume after cooldown and cache expire');
    $lock = fopen($dir . '/encoder-version.lock', 'c');
    flock($lock, LOCK_EX);
    try {
        expectVersion(EncoderVersion::github('/compare/concurrent...master', $dir, $success) === null && $requests === 3,
            'concurrent cache misses do not duplicate requests');
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
    expectVersion(EncoderVersion::github('/releases/latest', $dir . '/missing-directory', $success) === null && $requests === 3,
        'unavailable shared cache never sends unthrottled requests');
} finally {
    foreach (['/.git/HEAD', '/.git/packed-refs', '/objects/encoder-build.json'] as $file) {
        if (is_file($dir . $file)) { unlink($dir . $file); }
    }
    if (is_dir($dir . '/.git')) { rmdir($dir . '/.git'); }
    foreach (glob($dir . '/encoder-version*') as $file) {
        if (is_file($file)) { unlink($file); }
    }
    rmdir($dir . '/objects');
    rmdir($dir);
}
