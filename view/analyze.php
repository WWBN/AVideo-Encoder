<?php
$config = dirname(__FILE__) . '/../videos/configuration.php';
if (!file_exists($config)) {
    header("Location: install/index.php");
}
//header('Access-Control-Allow-Origin: *');
require_once $config;
require_once '../objects/Encoder.php';
require_once '../objects/Configuration.php';
require_once '../objects/Format.php';
require_once '../objects/Streamer.php';
require_once '../objects/Login.php';
require_once '../locale/function.php';

if (!Login::isLogged()) {
    die('Must Login');
}

$encoder_queue_id = intval(@$_REQUEST['encoder_queue_id']);
if (empty($encoder_queue_id)) {
    die('Queue ID is required');
}

$encoder = new Encoder($encoder_queue_id);

if ($encoder->getStreamers_id() != Login::getStreamerId() && !Login::isAdmin()) {
    die('You cannot see this video');
}

$files = Encoder::getTmpFiles($encoder_queue_id);
$dstFilepath = $global['systemRootPath'] . "videos/";
$sourceFilename = "{$dstFilepath}{$encoder_queue_id}_tmpFile.mp4";
// Example usage
$inputVideo = $sourceFilename;  // Replace with the actual input video path
$outputVideo = $files[0];  // Replace with the actual output video path

function getVideoDetails($videoPath)
{
    // Get video details using ffprobe
    $details = shell_exec("ffprobe -v error -show_entries format=bit_rate,duration,size:stream=width,height -of default=noprint_wrappers=1 \"$videoPath\"");
    $parsedDetails = parseDetails($details);

    // Check for VBV underflow in the ffmpeg output log
    $outputLog = shell_exec("ffmpeg -i \"$videoPath\" 2>&1");
    $underflowErrors = substr_count($outputLog, 'VBV underflow');

    // Calculate file size in MB
    $fileSizeMB = $parsedDetails['size'] / (1024 * 1024);

    return [
        'file_path' => $videoPath,
        'file_size_mb' => round($fileSizeMB, 2),
        'duration_sec' => round($parsedDetails['duration'], 2),
        'bit_rate_kbps' => round($parsedDetails['bit_rate'] / 1000, 2),
        'resolution' => $parsedDetails['width'] . 'x' . $parsedDetails['height'],
        'vbv_underflows' => $underflowErrors
    ];
}

function parseDetails($details)
{
    $parsed = [];
    $lines = explode("\n", trim($details));
    foreach ($lines as $line) {
        list($key, $value) = explode('=', $line);
        $parsed[$key] = (float)$value;
    }
    return $parsed;
}

?>
<!DOCTYPE html>
<html lang="<?php echo strtolower(@$_SESSION['lang']); ?>">

<head>

    <?php
    include __DIR__ . '/index.header.php';
    ?>
</head>

<body>
    <div class="container-fluid main-container">
        <?php

        function printVideoAnalysis($inputDetails, $outputDetails)
        {
        ?>
            <div class="container">
                <h3><i class="fa fa-file-video-o"></i> Video Analysis</h3>

                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4><i class="fa fa-file"></i> Input Video</h4>
                    </div>
                    <div class="panel-body">
                        <p><strong>File Path:</strong> <?php echo $inputDetails['file_path']; ?></p>
                        <p><strong>File Size:</strong> <?php echo $inputDetails['file_size_mb']; ?> MB</p>
                        <p><strong>Duration:</strong> <?php echo $inputDetails['duration_sec']; ?> seconds</p>
                        <p><strong>Bitrate:</strong> <?php echo $inputDetails['bit_rate_kbps']; ?> kbps</p>
                        <p><strong>Resolution:</strong> <?php echo $inputDetails['resolution']; ?></p>

                        <?php if ($inputDetails['bit_rate_kbps'] > 5000): ?>
                            <p class="text-warning"><i class="fa fa-exclamation-triangle"></i> The input video has an unusually high bitrate. This may explain why the file size is so large. Consider reducing the bitrate.</p>
                        <?php else: ?>
                            <p class="text-success"><i class="fa fa-check"></i> The input video's bitrate seems normal for its resolution and duration.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4><i class="fa fa-file"></i> Output Video</h4>
                    </div>
                    <div class="panel-body">
                        <p><strong>File Path:</strong> <?php echo $outputDetails['file_path']; ?></p>
                        <p><strong>File Size:</strong> <?php echo $outputDetails['file_size_mb']; ?> MB</p>
                        <p><strong>Duration:</strong> <?php echo $outputDetails['duration_sec']; ?> seconds</p>
                        <p><strong>Bitrate:</strong> <?php echo $outputDetails['bit_rate_kbps']; ?> kbps</p>
                        <p><strong>Resolution:</strong> <?php echo $outputDetails['resolution']; ?></p>

                        <?php if ($outputDetails['vbv_underflows'] > 0): ?>
                            <p class="text-danger"><i class="fa fa-exclamation-circle"></i> Warning: The output video experienced VBV underflow issues (<?php echo $outputDetails['vbv_underflows']; ?> times). This indicates the encoder struggled to maintain the target bitrate, possibly leading to degraded video quality.</p>
                        <?php endif; ?>

                        <?php if ($outputDetails['file_size_mb'] > $inputDetails['file_size_mb']): ?>
                            <p class="text-warning"><i class="fa fa-exclamation-triangle"></i> The output file is larger than the original file. This can occur if the target bitrate wasn't effectively applied or if the video complexity was too high for the chosen encoding settings.</p>
                        <?php else: ?>
                            <p class="text-success"><i class="fa fa-check"></i> The output video does not appear to have any significant encoding issues.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php
        }

        $inputDetails = getVideoDetails($inputVideo);
        $outputDetails = getVideoDetails($outputVideo);

        printVideoAnalysis($inputDetails, $outputDetails);
        ?>
    </div>
</body>

</html>
