<?php
// php tests/encoder-transfer-errors-regression.php [encoder root]
// Real classifier and transfer dispatch; transport stops at a sentinel before any HTTP.
if (PHP_SAPI !== 'cli') { exit(1); }
class ObjectYPT {}
class Login {}
class Streamer {}
class Format {}
$global = ['systemRootPath' => rtrim($argv[1] ?? dirname(__DIR__), '/\\') . '/',
    'docker_vars' => __DIR__ . '/nonexistent-docker-vars'];
require_once $global['systemRootPath'] . 'objects/Encoder.php';
class RejectionFixture extends Encoder
{
    public static $pull;
    public static function sendFileToDownload($file, $return_vars, $format, Encoder $encoder = null, $resolution = '', $try = 0)
    {
        return clone self::$pull;
    }
    public function getStreamers_id() { throw new LogicException('chunk transport reached'); }
}
$checks = 0;
function checkTransfer($condition, $message)
{
    global $checks;
    if (!$condition) { throw new RuntimeException($message); }
    ++$checks;
}
$cases = [
    [(object) ['code' => 'destination_unavailable'], 'destination_unavailable', 'removed or this account cannot edit'],
    [(object) ['msg' => 'Permission denied to edit a video: {"pass":"SECRET"}'], 'destination_unavailable', 'removed or this account cannot edit'],
    [(object) ['msg' => 'Permission denied to receive a file: SECRET'], 'streamer_access_denied', 'Renew the account access'],
    [(object) ['msg' => 'Permission denied to Notify Done: SECRET'], 'streamer_access_denied', 'Renew the account access'],
    [(object) ['curl_errno' => 28, 'response_raw' => ''], 'transfer_timeout', 'Timed out'],
    [(object) ['curl_errno' => 6], 'transfer_connection_failed', 'Could not communicate'],
    [(object) ['http_code' => 413], 'transfer_too_large', 'upload size limit'],
    [(object) ['http_code' => 403], 'streamer_access_denied', 'Renew the account access'],
    [(object) ['http_code' => 503], 'streamer_unavailable', 'server error'],
    [(object) ['response_raw' => '<html>SECRET</html>'], 'invalid_streamer_response', 'invalid response'],
    [(object) ['code' => 'completion_in_progress'], 'completion_in_progress', 'still processing'],
    [(object) ['code' => 'completion_failed'], 'completion_failed', 'could not finish'],
    [(object) ['code' => 'output_missing'], 'output_missing', 'file is missing'],
    [(object) ['msg' => 'Corrupted output'], 'output_corrupted', 'failed validation'],
    [(object) ['msg' => 'SECRET', 'code' => '<script>SECRET</script>'], '', 'without a recognized cause']
];
foreach ($cases as [$result, $expectedCode, $phrase]) {
    $result->error = true;
    foreach ([$result, (object) ['sends' => [(object) ['error' => false], (object) ['error' => true, 'response' => $result]]]] as $wrapped) {
        checkTransfer(Encoder::getTransferErrorCode($wrapped) === $expectedCode, 'error classification: ' . $expectedCode);
        $message = Encoder::getTransferErrorMessage($wrapped, 5221);
        checkTransfer(strpos($message, $phrase) !== false && strpos($message, '5221') !== false, 'action and destination are visible');
        checkTransfer(strlen(Encoder::getTransferErrorMessage($wrapped, PHP_INT_MAX)) <= 200, 'entire message fits persisted status');
        checkTransfer(strpos($message, 'SECRET') === false && strpos($message, '<script>') === false, 'untrusted diagnostics are never displayed');
    }
}
checkTransfer(strpos(Encoder::getTransferErrorMessage((object) ['curl_errno' => 28], 5221, true), 'Processing may still be running') !== false, 'completion timeout does not claim files were rejected');
$file = sys_get_temp_dir() . '/encoder-transfer-' . bin2hex(random_bytes(8)) . '.zip';
file_put_contents($file, 'fixture');
try {
    foreach ([$cases[0][0], $cases[1][0], $cases[2][0], $cases[7][0]] as $rejected) {
        RejectionFixture::$pull = $rejected;
        $result = RejectionFixture::sendFileChunk($file, (object) ['videos_id' => 5221], 'zip', new RejectionFixture());
        checkTransfer($result->error && Encoder::isTransferRejected($result), 'rejection returns before chunk transport');
        checkTransfer(strpos($result->msg, 'SECRET') === false, 'legacy rejection is sanitized without losing classification');
        checkTransfer(file_get_contents($file) === 'fixture', 'refusal preserves output');
    }
    foreach ([$cases[4][0], $cases[5][0], $cases[8][0], $cases[14][0]] as $retryable) {
        RejectionFixture::$pull = $retryable;
        try {
            RejectionFixture::sendFileChunk($file, (object) ['videos_id' => 5221], 'zip', new RejectionFixture());
            throw new RuntimeException('transport fallback was skipped');
        } catch (LogicException $expected) {
            checkTransfer($expected->getMessage() === 'chunk transport reached', 'non-terminal pull failure still permits fallback');
        }
    }
    RejectionFixture::$pull = (object) ['error' => false];
    checkTransfer(!RejectionFixture::sendFileChunk($file, (object) ['videos_id' => 5221], 'zip', new RejectionFixture())->error, 'successful pull needs no chunk upload');
} finally {
    unlink($file);
}
echo "PASS: {$checks} transfer diagnostic checks\n";
