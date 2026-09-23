<?php

/** Read-only installation identity. Database versions are deliberately separate. */
class EncoderVersion
{
    private static function git($root, array $arguments)
    {
        if (!function_exists('proc_open')) {
            return null;
        }
        $process = @proc_open(array_merge(['git', '-c', 'safe.directory=' . $root, '-C', $root], $arguments),
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (!is_resource($process)) {
            return null;
        }
        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        $output = '';
        $deadline = microtime(true) + 2;
        do {
            $output .= stream_get_contents($pipes[1]);
            stream_get_contents($pipes[2]);
            $status = proc_get_status($process);
            if (!$status['running']) {
                break;
            }
            usleep(10000);
        } while (microtime(true) < $deadline);
        if ($status['running']) {
            proc_terminate($process);
        }
        $output .= stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);
        return !$status['running'] && $status['exitcode'] === 0 ? trim($output) : null;
    }

    public static function installed($root)
    {
        $root = realpath($root);
        $info = ['sha' => null, 'branch' => null, 'tag' => null, 'dirty' => null, 'source' => 'unknown'];
        if (!$root) {
            return $info;
        }
        // Do not accidentally identify a parent repository as the Encoder.
        if (file_exists($root . '/.git')) {
            $sha = self::git($root, ['rev-parse', '--verify', 'HEAD']);
            if (is_string($sha) && preg_match('/\A[0-9a-f]{40}\z/', $sha)) {
                $info['sha'] = $sha;
                $info['source'] = 'git';
                $info['branch'] = self::git($root, ['symbolic-ref', '--quiet', '--short', 'HEAD']);
                $info['tag'] = self::git($root, ['describe', '--tags', '--exact-match', 'HEAD']);
                // Ignore runtime files; report modifications to tracked source files.
                $changes = self::git($root, ['status', '--porcelain', '--untracked-files=no']);
                $info['dirty'] = $changes === null ? null : $changes !== '';
            }
            return $info;
        }
        // Docker builds have no .git directory. The workflow embeds the tested SHA.
        $sha = getenv('ENCODER_BUILD_COMMIT');
        if (is_string($sha) && preg_match('/\A[0-9a-f]{40}\z/', $sha)) {
            $info['sha'] = $sha;
            $info['source'] = 'build';
            return $info;
        }
        // git archive / GitHub source archives expand this tracked placeholder.
        $metadata = $root . '/update/build-info.json';
        $archive = is_readable($metadata) ? json_decode(file_get_contents($metadata), true) : null;
        if (is_array($archive) && is_string($archive['sha'] ?? null)
            && preg_match('/\A[0-9a-f]{40}\z/', $archive['sha'])) {
            $info['sha'] = $archive['sha'];
            $info['source'] = 'archive';
        }
        return $info;
    }
}
