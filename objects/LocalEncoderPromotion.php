<?php

// Presentation only: every link is scoped to the currently authenticated Streamer.
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

    public static function isFailedURLImport($job, $streamersId)
    {
        $url = $job['videoDownloadedLink'] ?? '';
        return !empty($streamersId) && (int) ($job['streamers_id'] ?? 0) === (int) $streamersId
            && ($job['status'] ?? '') === 'error' && empty($job['downloadedFileName'])
            && is_string($url) && filter_var($url, FILTER_VALIDATE_URL)
            && in_array(strtolower(parse_url($url, PHP_URL_SCHEME) ?? ''), array('http', 'https'), true);
    }
}
