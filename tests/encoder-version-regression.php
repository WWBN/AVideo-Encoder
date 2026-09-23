<?php
// Standalone version identity checks; no database, network or installed Git changes.
if (PHP_SAPI !== 'cli') {
    exit(1);
}
require_once __DIR__ . '/../objects/EncoderVersion.php';
$root = sys_get_temp_dir() . '/encoder-version-' . bin2hex(random_bytes(8));
mkdir($root, 0700);
$oldBuild = getenv('ENCODER_BUILD_COMMIT');
putenv('ENCODER_BUILD_COMMIT');
function versionExpect($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
    echo "PASS: $message\n";
}
function versionGit($root, ...$arguments)
{
    $process = proc_open(array_merge(['git', '-C', $root, '-c', 'user.name=Version test',
        '-c', 'user.email=test@example.invalid', '-c', 'commit.gpgsign=false', '-c', 'core.hooksPath=/dev/null'], $arguments),
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    fclose($pipes[0]);
    $result = stream_get_contents($pipes[1]);
    $error = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    if (proc_close($process) !== 0) {
        throw new RuntimeException('Git fixture failed: ' . $error);
    }
    return trim($result);
}
try {
    versionExpect(EncoderVersion::installed($root)['sha'] === null, 'missing metadata is unknown, not a database version');
    mkdir($root . '/update');
    file_put_contents($root . '/update/build-info.json', '{"sha":"$Format:%H$"}');
    versionExpect(EncoderVersion::installed($root)['sha'] === null, 'unexpanded archive placeholders are ignored');
    $archiveSha = str_repeat('a', 40);
    file_put_contents($root . '/update/build-info.json', json_encode(['sha' => $archiveSha]));
    versionExpect(EncoderVersion::installed($root)['sha'] === $archiveSha, 'source archive identity is read');
    putenv('ENCODER_BUILD_COMMIT=' . str_repeat('b', 40));
    versionExpect(EncoderVersion::installed($root)['source'] === 'build', 'Docker build identity works without Git');
    putenv('ENCODER_BUILD_COMMIT');
    versionGit($root, 'init', '-b', 'master');
    versionGit($root, 'add', 'update/build-info.json');
    versionGit($root, 'commit', '-m', 'fixture');
    $sha = versionGit($root, 'rev-parse', 'HEAD');
    versionGit($root, 'tag', '-a', 'v8.1.0', '-m', 'release');
    $info = EncoderVersion::installed($root);
    versionExpect($info['sha'] === $sha && $info['branch'] === 'master' && $info['tag'] === 'v8.1.0', 'Git commit, branch and annotated tag are identified separately');
    versionExpect($info['dirty'] === false, 'clean checkout is recognized');
    file_put_contents($root . '/runtime.log', 'runtime data');
    versionExpect(EncoderVersion::installed($root)['dirty'] === false, 'untracked runtime files do not mark source as modified');
    file_put_contents($root . '/update/build-info.json', '{}');
    versionExpect(EncoderVersion::installed($root)['dirty'] === true, 'tracked source changes are reported');
    mkdir($root . '/nested');
    versionExpect(EncoderVersion::installed($root . '/nested')['sha'] === null, 'parent repositories are not mistaken for the installation');
    versionGit($root, 'checkout', '--detach', 'HEAD');
    $info = EncoderVersion::installed($root);
    versionExpect($info['sha'] === $sha && $info['branch'] === null, 'detached installations retain their exact commit');
    putenv('ENCODER_BUILD_COMMIT=' . str_repeat('b', 40));
    versionExpect(EncoderVersion::installed($root)['sha'] === $sha, 'mounted checkout takes precedence over Docker image metadata');
} catch (Throwable $error) {
    fwrite(STDERR, 'FAIL: ' . $error->getMessage() . "\n");
    $failed = true;
} finally {
    putenv($oldBuild === false ? 'ENCODER_BUILD_COMMIT' : 'ENCODER_BUILD_COMMIT=' . $oldBuild);
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) {
        if ($file->isDir() && !$file->isLink()) {
            rmdir($file->getPathname());
        } else {
            @chmod($file->getPathname(), 0600);
            unlink($file->getPathname());
        }
    }
    rmdir($root);
}
exit(empty($failed) ? 0 : 1);
