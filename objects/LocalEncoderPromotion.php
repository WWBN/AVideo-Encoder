<?php

// Links and browser sign-in are scoped to the currently authenticated Streamer.
class EncoderLocalEncoderPromotion
{
    public static function getURL($streamerURL, $settings)
    {
        if (!is_object($settings) || empty($settings->showLocalEncoder) || !is_string($streamerURL)
            || !filter_var($streamerURL, FILTER_VALIDATE_URL)) {
            return '';
        }
        $parts = parse_url($streamerURL);
        if (empty($parts['host']) || !in_array(strtolower($parts['scheme'] ?? ''), array('http', 'https'), true)
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) {
            return '';
        }
        return rtrim($streamerURL, '/') . '/plugin/CustomizeAdvanced/localEncoder.php';
    }

    public static function getLaunchURL($encoderURL, $streamerURL, $settings)
    {
        return self::getURL($streamerURL, $settings) !== '' && self::getURL($encoderURL, $settings) !== ''
            ? rtrim($encoderURL, '/') . '/view/localEncoder.php' : '';
    }

    public static function requestLoginCode($challenge)
    {
        global $global;
        if (!is_string($challenge) || !preg_match('/^[A-Za-z0-9_-]{43}$/D', $challenge)
            || !Login::isLogged() || !Login::canUpload()) {
            return false;
        }
        $site = Login::getStreamerURL();
        if (self::getURL($site, getAdvancedCustomizedObjectData()) === '') { return false; }
        $url = getExternalHttpUrlForShell(rtrim($site, '/') . '/plugin/CustomizeAdvanced/localEncoderLogin.json.php', 'Local Encoder sign-in');
        if (empty($url)) { return false; }
        $curl = curl_init($url);
        if ($curl === false) { return false; }
        try {
            $verify = empty($global['disableSSLVerification']);
            curl_setopt_array($curl, array(CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => false, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 20,
                CURLOPT_SSL_VERIFYPEER => $verify, CURLOPT_SSL_VERIFYHOST => $verify ? 2 : 0,
                CURLOPT_USERAGENT => getSelfUserAgent(), CURLOPT_POSTFIELDS => http_build_query(array(
                    'action' => 'issue', 'challenge' => $challenge, 'user' => Login::getStreamerUser(),
                    'pass' => Login::getStreamerPass(), 'encodedPass' => '1'))));
            $body = curl_exec($curl);
            if (curl_getinfo($curl, CURLINFO_HTTP_CODE) !== 200 || !is_string($body)) { return false; }
            $grant = json_decode($body, true);
            if (!is_array($grant) || ($grant['error'] ?? true) !== false || !is_string($grant['code'] ?? null)
                || !preg_match('/^[a-f0-9]{64}$/D', $grant['code'])) { return false; }
            return array('error' => false, 'code' => $grant['code'], 'expires_in' => 120);
        } finally { curl_close($curl); }
    }

    public static function isFailedURLImport($job, $streamersId)
    {
        $url = $job['videoDownloadedLink'] ?? '';
        return !empty($streamersId) && (int) ($job['streamers_id'] ?? 0) === (int) $streamersId
            && ($job['status'] ?? '') === 'error' && empty($job['downloadedFileName'])
            && is_string($url) && filter_var($url, FILTER_VALIDATE_URL)
            && in_array(strtolower(parse_url($url, PHP_URL_SCHEME) ?? ''), array('http', 'https'), true);
    }

    public static function getSourceURL($job, $streamersId)
    {
        if (!self::isFailedURLImport($job, $streamersId)) {
            return '';
        }
        $url = $job['videoDownloadedLink'];
        $parts = parse_url($url);
        if (isset($parts['user']) || isset($parts['pass']) || preg_match('/[\x00-\x20\\\\]/', $url)) {
            return '';
        }
        return $url;
    }
}
