<?php

class MP3Processor
{
    public static function createMP3($pathFileName, $destinationFile)
    {
        // Define encoding settings for MP3
        $audioBitrate = 128; // Set a standard bitrate for MP3 encoding

        // Generate the FFmpeg command for creating an MP3 file
        $command = self::generateFFmpegCommand($pathFileName, $destinationFile, $audioBitrate);

        // Execute the FFmpeg command
        _error_log("MP3Processor: Executing FFmpeg command: $command");
        exec($command, $output, $resultCode);

        if ($resultCode !== 0) {
            _error_log("MP3Processor: FFmpeg failed with output: " . json_encode($output));
            throw new Exception("Failed to create MP3 file.");
        }

        _error_log("MP3Processor: MP3 file created successfully at $destinationFile");
    }

    private static function generateFFmpegCommand($inputFile, $outputFile, $audioBitrate)
    {
        $ffmpeg = get_ffmpeg() . " -i $inputFile " .
            "-vn -c:a libmp3lame -b:a {$audioBitrate}k " .
            "-movflags +faststart " .
            "$outputFile";
        return removeUserAgentIfNotURL($ffmpeg);
    }
}
