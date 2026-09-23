<?php

// New implementation: the existing Configuration version tracks SQL migrations only.
class EncoderVersion
{
    const REPOSITORY = 'https://github.com/WWBN/AVideo-Encoder';
    const CACHE_TTL = 3600;

    public static function installed($root)
    {
        $result = ['commit' => '', 'version' => '', 'branch' => '', 'modified' => null];
        // Require this checkout's own .git; never accidentally report the parent Streamer.
        if (file_exists($root . '/.git') && function_exists('exec')) {
            $git = 'git -C ' . escapeshellarg($root) . ' ';
            $sha = self::git($git . 'rev-parse --verify HEAD');
            if (preg_match('/^[a-f0-9]{40}$/D', $sha)) {
                $result['commit'] = $sha;
                $result['branch'] = self::git($git . 'symbolic-ref --short -q HEAD');
                $result['version'] = self::git($git . 'describe --tags --always');
                $status = self::git($git . 'status --porcelain --untracked-files=no', $ok);
                $result['modified'] = $ok ? $status !== '' : null;
            }
        }
        if (!$result['commit']) {
            // PHP's user may read Git metadata even when Git rejects repository ownership.
            $gitDirectory = $root . '/.git';
            if (is_file($gitDirectory)) {
                $pointer = trim(file_get_contents($gitDirectory));
                if (strpos($pointer, 'gitdir: ') === 0) {
                    $gitDirectory = self::resolvePath($root, substr($pointer, 8));
                }
            }
            if (is_readable($gitDirectory . '/HEAD')) {
                $head = trim(file_get_contents($gitDirectory . '/HEAD'));
                $commonDirectory = $gitDirectory;
                if (is_readable($gitDirectory . '/commondir')) {
                    $commonDirectory = self::resolvePath($gitDirectory, trim(file_get_contents($gitDirectory . '/commondir')));
                }
                if (strpos($head, 'ref: refs/heads/') === 0) {
                    $reference = substr($head, 5);
                    $result['branch'] = substr($reference, 11);
                    $head = '';
                    if (is_readable($commonDirectory . '/' . $reference)) {
                        $head = trim(file_get_contents($commonDirectory . '/' . $reference));
                    } elseif (is_readable($commonDirectory . '/packed-refs')) {
                        foreach (file($commonDirectory . '/packed-refs', FILE_IGNORE_NEW_LINES) as $line) {
                            if (substr($line, 41) === $reference) {
                                $head = substr($line, 0, 40);
                                break;
                            }
                        }
                    }
                }
                if (preg_match('/^[a-f0-9]{40}$/D', $head)) {
                    $result['commit'] = $head;
                }
            }
        }
        if (!$result['commit']) {
            $buildFile = $root . '/objects/encoder-build.json';
            $build = is_readable($buildFile) ? json_decode(file_get_contents($buildFile), true) : [];
            if (is_array($build) && preg_match('/^[a-f0-9]{40}$/D', $build['commit'] ?? '')) {
                $result['commit'] = $build['commit'];
                $version = $build['version'] ?? '';
                $result['version'] = strpos($version, '$Format:') === false ? $version : '';
            }
        }
        return $result;
    }

    private static function resolvePath($base, $path)
    {
        return preg_match('~^(?:[/\\\\]|[A-Za-z]:)~', $path) ? $path : $base . '/' . $path;
    }

    private static function git($command, &$ok = null)
    {
        $output = [];
        exec($command . ' 2>' . (DIRECTORY_SEPARATOR === '\\' ? 'NUL' : '/dev/null'), $output, $exitCode);
        $ok = $exitCode === 0;
        return $ok ? trim(implode("\n", $output)) : '';
    }

    public static function comparison($data)
    {
        // GitHub compares installed (base) to available (head): "ahead" means update available.
        $states = ['ahead' => 'update', 'identical' => 'current', 'behind' => 'newer', 'diverged' => 'diverged'];
        $state = $states[$data['status'] ?? ''] ?? 'unknown';
        return ['state' => $state, 'commits' => max(0, (int) ($data['ahead_by'] ?? 0))];
    }

    public static function check($installed, $request)
    {
        $result = ['installed' => $installed, 'release' => null, 'master' => null];
        $release = call_user_func($request, '/releases/latest');
        if (is_array($release) && empty($release['draft']) && empty($release['prerelease'])
            && preg_match('/^v?\d+\.\d+(?:\.\d+)?$/D', $release['tag_name'] ?? '')) {
            $result['release'] = ['name' => $release['tag_name'], 'url' => self::REPOSITORY . '/releases/tag/' . rawurlencode($release['tag_name']), 'state' => 'unknown', 'commits' => 0];
        }
        $master = call_user_func($request, '/commits/master');
        if (is_array($master) && preg_match('/^[a-f0-9]{40}$/D', $master['sha'] ?? '')) {
            $result['master'] = ['name' => substr($master['sha'], 0, 12), 'url' => self::REPOSITORY . '/commit/' . $master['sha'], 'state' => 'unknown', 'commits' => 0];
        }
        if (!preg_match('/^[a-f0-9]{40}$/D', $installed['commit'] ?? '')) {
            return $result;
        }
        foreach (['release' => $release['tag_name'] ?? '', 'master' => $master['sha'] ?? ''] as $channel => $ref) {
            if ($result[$channel] === null) {
                continue;
            }
            $comparison = $ref === $installed['commit'] ? ['status' => 'identical']
                : call_user_func($request, '/compare/' . $installed['commit'] . '...' . rawurlencode($ref) . '?per_page=1');
            $result[$channel] = array_merge($result[$channel], self::comparison(is_array($comparison) ? $comparison : []));
            $result[$channel]['changesUrl'] = self::REPOSITORY . '/compare/' . $installed['commit'] . '...' . rawurlencode($ref);
        }
        return $result;
    }

    public static function retryAt($status, $headers, $now)
    {
        $headers = array_change_key_case($headers, CASE_LOWER);
        $exhausted = isset($headers['x-ratelimit-remaining']) && trim($headers['x-ratelimit-remaining']) === '0';
        if (!in_array($status, [403, 429], true) && !$exhausted) {
            return 0;
        }
        $until = 0;
        $retry = trim($headers['retry-after'] ?? '');
        if ($retry !== '') {
            $until = ctype_digit($retry) ? $now + (int) $retry : (int) strtotime($retry);
        }
        if ($exhausted && ctype_digit(trim($headers['x-ratelimit-reset'] ?? ''))) {
            $until = max($until, (int) $headers['x-ratelimit-reset']);
        }
        // Never retry immediately; missing rate-limit headers use a conservative one-hour pause.
        return $until > 0 ? max($now + 60, $until) : $now + self::CACHE_TTL;
    }

    public static function github($path, $cacheDirectory, $request = null)
    {
        $file = $cacheDirectory . '/encoder-version-' . hash('sha256', $path) . '.json';
        // Serialize cache misses across admins and PHP workers, without blocking the UI.
        // If the shared cache is unavailable, do not issue requests that cannot be throttled.
        if (!is_dir($cacheDirectory) || !is_writable($cacheDirectory)) {
            _error_log('Encoder update check requires a writable cache directory.');
            return null;
        }
        $lock = fopen($cacheDirectory . '/encoder-version.lock', 'c');
        if ($lock === false) {
            _error_log('Could not open the Encoder update check lock.');
            return null;
        }
        if (!flock($lock, LOCK_EX | LOCK_NB)) {
            fclose($lock);
            return null;
        }
        try {
            clearstatcache(true, $file);
            if (is_readable($file) && filemtime($file) > time() - self::CACHE_TTL) {
                $cached = json_decode(file_get_contents($file), true);
                if (is_array($cached) && array_key_exists('data', $cached)) {
                    return $cached['data'];
                }
            }
            // One cooldown covers every GitHub endpoint, including checks after a deployment.
            $rateFile = $cacheDirectory . '/encoder-version-rate-limit.json';
            $rate = is_readable($rateFile) ? json_decode(file_get_contents($rateFile), true) : [];
            if (($rate['retryAt'] ?? 0) > time()) {
                return null;
            }
            $response = $request === null ? self::githubResponse($path) : call_user_func($request, $path);
            $status = (int) ($response['status'] ?? 0);
            $data = $status === 200 && is_array($response['data'] ?? null) ? $response['data'] : null;
            $retryAt = self::retryAt($status, $response['headers'] ?? [], time());
            if ($retryAt && file_put_contents($rateFile, json_encode(['retryAt' => $retryAt]), LOCK_EX) === false) {
                _error_log('Could not cache the GitHub rate-limit deadline.');
            }
            if ($data === null) {
                _error_log('Encoder update check unavailable (GitHub HTTP ' . $status . ').');
            }
            // Cache failures too. The check button always respects this cache and the cooldown.
            if (file_put_contents($file, json_encode(['data' => $data]), LOCK_EX) === false) {
                _error_log('Could not cache the Encoder update check.');
            }
            return $data;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private static function githubResponse($path)
    {
        // url_get_contents() does not enforce a timeout in its cURL fallback.
        // This bounded, fixed-host request must not stall the Encoder UI.
        $response = ['status' => 0, 'headers' => [], 'data' => null];
        if (function_exists('curl_init')) {
            $curl = curl_init('https://api.github.com/repos/WWBN/AVideo-Encoder' . $path);
            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_TIMEOUT => 6,
                CURLOPT_USERAGENT => 'AVideo-Encoder-Update-Check',
                CURLOPT_HTTPHEADER => ['Accept: application/vnd.github+json'],
                CURLOPT_HEADERFUNCTION => function ($curl, $line) use (&$response) {
                    $parts = explode(':', $line, 2);
                    if (count($parts) === 2) {
                        $response['headers'][strtolower(trim($parts[0]))] = trim($parts[1]);
                    }
                    return strlen($line);
                },
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2
            ]);
            $body = curl_exec($curl);
            $response['status'] = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            if ($response['status'] === 200 && is_string($body)) {
                $decoded = json_decode($body, true);
                $response['data'] = is_array($decoded) ? $decoded : null;
            }
        } else {
            _error_log('Encoder update check requires the PHP cURL extension.');
        }
        return $response;
    }
}
