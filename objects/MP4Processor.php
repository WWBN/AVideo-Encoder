<?php

class MP4Processor
{
    
    public static function createMP4MaxResolutionFromQueueId($pathFileName, $encoder_queue_id, $maxResolution = 1080){
        $inputResolution = self::getResolution($pathFileName);
        if($inputResolution> $maxResolution){
            $inputResolution = $maxResolution;
        }
        $destinationFile = Encoder::getTmpFileName($encoder_queue_id, 'mp4', $inputResolution);
        return self::createMP4($pathFileName, $destinationFile, $encoder_queue_id, $inputResolution);
    }

    public static function createMP4MaxResolution($pathFileName, $destinationFile, $maxResolution = 1080){
        
        $inputResolution = self::getResolution($pathFileName);
        if($inputResolution> $maxResolution){
            $inputResolution = $maxResolution;
        }
        return self::createMP4($pathFileName, $destinationFile, 0, $inputResolution);
    }

    public static function createMP4($pathFileName, $destinationFile, $encoder_queue_id = 0, $inputResolution = null)
    {
        global $global;
        // Get allowed resolutions from Format::ENCODING_SETTINGS
        $allowedResolutions = array_keys(Format::ENCODING_SETTINGS);

        // Get the resolution of the input file
        if ($inputResolution === null) {
            $inputResolution = self::getResolution($pathFileName);
        }

        // Determine the target resolution
        $targetResolution = self::getClosestResolution($inputResolution, $allowedResolutions);

        if ($targetResolution === null) {
            throw new Exception("No valid resolution found for the input file.");
        }

        // Load encoding settings for the target resolution
        $encodingSettings = Format::ENCODING_SETTINGS[$targetResolution];

        // Create the FFmpeg command
        $command = self::generateFFmpegCommand(
            $pathFileName,
            $destinationFile,
            $targetResolution,
            $encodingSettings
        );

        if(!empty($encoder_queue_id)){
            $progressFile = "{$global['systemRootPath']}videos/{$encoder_queue_id}_tmpFile_progress.txt";
            $command = "{$command} > 1 $progressFile 2>&1";
        }

        // Execute the FFmpeg command
        _error_log("MP4Processor: Executing FFmpeg command: $command");
        exec($command, $output, $resultCode);

        if ($resultCode !== 0) {
            _error_log("MP4Processor: FFmpeg failed with output: " . json_encode($output));
            throw new Exception("Failed to create MP4 file.");
        }

        _error_log("MP4Processor: MP4 file created successfully at $destinationFile");
    }

    private static function getResolution($pathFileName)
    {
        $command = get_ffprobe() . " -v error -select_streams v:0 -show_entries stream=height -of csv=p=0 $pathFileName";
        return (int) shell_exec($command);
    }

    private static function getClosestResolution($inputResolution, $allowedResolutions)
    {
        // Sort resolutions in descending order
        rsort($allowedResolutions);

        foreach ($allowedResolutions as $resolution) {
            if ($inputResolution >= $resolution) {
                return $resolution;
            }
        }

        // Return the lowest resolution if no match found
        return $allowedResolutions[count($allowedResolutions) - 1] ?? null;
    }

    private static function generateFFmpegCommand($inputFile, $outputFile, $resolution, $encodingSettings)
    {
        $ffmpeg = get_ffmpeg() . " -i $inputFile " .
            '-preset veryfast '.
            "-vf scale=-2:$resolution " .
            "-b:v {$encodingSettings['maxrate']}k " .
            "-minrate {$encodingSettings['minrate']}k " .
            "-maxrate {$encodingSettings['maxrate']}k " .
            "-bufsize {$encodingSettings['bufsize']}k " .
            "-c:v h264 -pix_fmt yuv420p " .
            "-c:a aac -b:a {$encodingSettings['audioBitrate']}k " .
            "-movflags +faststart " .
            "$outputFile";
            
        return removeUserAgentIfNotURL($ffmpeg);
    }
}