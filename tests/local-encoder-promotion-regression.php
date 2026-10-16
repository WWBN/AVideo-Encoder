<?php
// php tests/local-encoder-promotion-regression.php
if (PHP_SAPI !== 'cli') { exit(1); }
require_once dirname(__DIR__) . '/objects/LocalEncoderPromotion.php';

$checks = 0;
function checkLocalEncoderPromotion($condition, $message)
{
    global $checks;
    if (!$condition) { throw new RuntimeException($message); }
    ++$checks;
}

$settings = (object) ['showLocalEncoder' => true];
$page = '/plugin/CustomizeAdvanced/localEncoder.php';
checkLocalEncoderPromotion(EncoderLocalEncoderPromotion::getURL('https://site-a.test/videos/', $settings) === 'https://site-a.test/videos' . $page, 'preserve the authenticated site subdirectory');
checkLocalEncoderPromotion(EncoderLocalEncoderPromotion::getURL('https://site-b.test:8443', $settings) === 'https://site-b.test:8443' . $page, 'another Streamer keeps its own URL');
foreach ([null, false, new stdClass(), (object) ['showLocalEncoder' => false]] as $disabled) {
    checkLocalEncoderPromotion(EncoderLocalEncoderPromotion::getURL('https://site-a.test/', $disabled) === '', 'disabled and older Streamers have no promotion');
}
foreach (['javascript:alert(1)', 'ftp://site-a.test/', 'https://user:secret@site-a.test/', 'https://site-a.test/?pass=secret', 'https://site-a.test/#fragment', '', null, []] as $url) {
    checkLocalEncoderPromotion(EncoderLocalEncoderPromotion::getURL($url, $settings) === '', 'reject an invalid or credential-bearing page address');
}

$failed = ['streamers_id' => 7, 'status' => 'error', 'videoDownloadedLink' => 'https://provider.test/watch?id=1', 'downloadedFileName' => ''];
checkLocalEncoderPromotion(EncoderLocalEncoderPromotion::isFailedURLImport($failed, 7), 'an individual URL download failed on the current Streamer');
checkLocalEncoderPromotion(!EncoderLocalEncoderPromotion::isFailedURLImport($failed, 8), 'an Encoder admin must not see another Streamer promotion on the job');
checkLocalEncoderPromotion(!EncoderLocalEncoderPromotion::isFailedURLImport($failed, 0), 'no authenticated Streamer');
foreach ([['status' => 'done'], ['status' => 'encoding'], ['downloadedFileName' => '/work/video.mp4'], ['videoDownloadedLink' => ''], ['videoDownloadedLink' => 'ftp://provider.test/video.mp4'], ['videoDownloadedLink' => []]] as $override) {
    checkLocalEncoderPromotion(!EncoderLocalEncoderPromotion::isFailedURLImport(array_merge($failed, $override), 7), 'do not recommend a URL retry for another kind of failure');
}
checkLocalEncoderPromotion(!EncoderLocalEncoderPromotion::isFailedURLImport([], 7), 'legacy incomplete queue rows are safe');
echo "Local Encoder promotion: {$checks} checks passed\n";
