<?php

$watermark_fontsize = "(h/30)";
$watermark_color = "yellow";
$watermark_opacity = 0.5;
$hls_time = 10;
$skippFirstSegments = 30; // 5 min
$max_process_at_the_same_time = 5;
$encrypt = false; // if enable encryption it fails to play, probably an error on .ts timestamp
//$downloadCodec = " -c:v libx264 -acodec copy ";
$downloadCodec = " -c copy ";
//$watermarkCodec = " -c:v libx264 -preset ultrafast -profile:v main  ";
$watermarkCodec = " -c:v libx264 -acodec copy -movflags +faststart ";
//$maximumWatermarkPercentage = 100;
//$minimumWatermarkPercentage = 10;
$maxElements = 1;

require_once dirname(__FILE__) . '/../videos/configuration.php';
require_once $global['systemRootPath'] . 'objects/Encoder.php';
require_once $global['systemRootPath'] . 'objects/Login.php';
require_once $global['systemRootPath'] . 'objects/Streamer.php';
session_write_close();

if (!empty($global['mysqli'])) {
    $global['mysqli']->close();
}
ignore_user_abort(true);
set_time_limit(0);

ob_start();

//header("Access-Control-Allow-Origin: *");
if (!empty($_REQUEST['_'])) {
    $array = json_decode(base64_decode($_REQUEST['_']));
    foreach ($array as $key => $value) {
        $_REQUEST[$key] = $value;
    }
    unset($_REQUEST['_']);
}


$domain = get_domain($_REQUEST['file']);


if (!isSameDomain($_REQUEST['file'], $global['webSiteRootURL'])) {
    if (empty($global['watermarkDomainWhitelist'])) {
        die("Create an array in your configuration.php file with the allowed domains \$global['watermarkDomainWhitelist']");
    }
}

// create an array in your configuration.php file with the allowed domains $global['watermarkDomainWhitelist']
if (!empty($global['watermarkDomainWhitelist'])) {
    $found = false;
    foreach ($global['watermarkDomainWhitelist'] as $value) {
        if (isSameDomain($_REQUEST['file'], $value)) {
            $found = true;
            break;
        }
    }
    if (empty($found)) {
        die("Domain NOT allowed");
    }
}

$obj = new stdClass();
$obj->error = true;
$obj->msg = "";
$obj->time = time();
$obj->lastUpdate = $obj->time;
$obj->videos_id = intval($_REQUEST['videos_id']);
$obj->protectionToken = $_REQUEST['protectionToken'];
$obj->isMobile = !empty($_REQUEST['isMobile']);

if (empty($obj->videos_id)) {
    $obj->msg = "videos_id is empty";
    die(json_encode($obj));
}

$watermarkDir = $global['systemRootPath'] . 'videos/watermarked/';
$lockDir = "{$watermarkDir}lock/";
$lockFilePath = "{$lockDir}" . uniqid();
;


$dir = "{$watermarkDir}{$domain}/";
//error_log("Watermark: DIR $dir");
make_path($dir);

$clean = array('file', 'watermark_text', 'watermark_token');
foreach ($clean as $value) {
    $_REQUEST[$value] = preg_replace('/[^0-9a-z&.?\/=[\]:_-]/i', "", $_REQUEST[$value]);
}
$obj->file = $_REQUEST['file'];
$obj->watermark_text = $_REQUEST['watermark_text'];
$obj->watermark_token = $_REQUEST['watermark_token'];
$obj->resolution = intval($_REQUEST['resolution']);
if ($obj->resolution < 360) {
    $watermark_fontsize = "(h/20)";
}

$input = "{$_REQUEST['file']}" . ((strpos($_REQUEST['file'], '?') !== false) ? "&" : "?") . "watermark_token={$_REQUEST['watermark_token']}";

$text = $_REQUEST['watermark_text'];
$outputTextPath = "$dir{$_REQUEST['videos_id']}/" . md5("{$text}") . "/";
$outputPath = "{$outputTextPath}{$obj->resolution}";
$outputURL = str_replace($global['systemRootPath'], $global['webSiteRootURL'], $outputPath);

$outputHLS_index = "{$outputPath}/index.m3u8";

$jsonFile = "$outputPath/.obj.log";
$encFile = "$outputPath/enc_watermarked.key";
$keyInfoFile = "$outputPath/.keyInfo";
$encFileURL = "{$outputURL}/enc_watermarked.key";

$localFileDownloadDir = "$dir{$_REQUEST['videos_id']}/{$_REQUEST['resolution']}";
$localFileDownload_lock = "$localFileDownloadDir/lock";
$localFileDownload_ts = "$localFileDownloadDir/%03d.ts";
$localFileDownload_index = "$localFileDownloadDir/index.m3u8";

createSymbolicLinks($localFileDownloadDir, $outputPath);
getIndexM3U8();
if (!allTSFilesAreSymlinks($outputPath)) {
    exit;
}

$totalFFMPEG = getHowManyFFMPEG();
if ($totalFFMPEG > $max_process_at_the_same_time) {
    //die("Too many FFMPEG processing now {$totalFFMPEG}");
    error_log("Too many FFMPEG processing now {$totalFFMPEG}/{$max_process_at_the_same_time}, using symlinks $outputPath");
    createFirstSegment();
    exit;
}

$startTime = microtime(true);
error_log("Watermark: start $outputHLS_index");
if (!isRunning($outputPath)) {
    startWaretmark();
    $localFileDownload_HLS = "  -hls_segment_filename \"{$localFileDownload_ts}\" \"{$localFileDownload_index}\" ";
    //$localFileDownloadDir$localFileName = "video.mp4";
    //$localFilePath = "$dir{$_REQUEST['videos_id']}/{$localFileName}";
    make_path($localFileDownloadDir);

    if (canIDownloadVideo($localFileDownloadDir)) {
        $startDownloadTime = microtime(true);
        file_put_contents($localFileDownload_lock, time());
        file_put_contents($localFileDownload_index, "");
        //$ffmpeg = "ffmpeg -i \"$input\" -c copy -bsf:a aac_adtstoasc {$localFilePath} ";
        $ffmpeg = "ffmpeg -i \"$input\" {$downloadCodec} -f hls -hls_time {$hls_time} -hls_list_size 0  -hls_playlist_type vod {$localFileDownload_HLS} ";

        error_log("Watermark: download video $ffmpeg");

        //var_dump($ffmpeg);exit;

        __exec($ffmpeg);

        unlink($localFileDownload_lock);
        error_log("Watermark: download video complete in " . (microtime(true) - $startDownloadTime) . " seconds");
        createFirstSegment();
    }



    $totalPidsRunning = totalPidsRunning($watermarkDir);
    //error_log("totalPidsRunning: $totalPidsRunning");
    if ($totalPidsRunning >= $max_process_at_the_same_time) {
        $obj->msg = "Too many running now, total: $totalPidsRunning from max of $max_process_at_the_same_time";
        endWaretmark();
        die(json_encode($obj));
    }
    if ($obj->isMobile) {
        $encFileURL .= "?isMobile=1";
    }
    
    if (canConvert($outputPath)) {
        //$cmd = "rm -fr {$outputTextPath}"; // this will make other process stops and saves CPU resources
        //__exec($cmd);

        stopAllPids($outputTextPath);


        make_path($outputPath);

        if ($encrypt) {
            error_log("Watermark: will be encrypted ");
            $cmd = "openssl rand 16 > {$encFile}";

            __exec($cmd);
        } else {
            //error_log("Watermark: will NOT be encrypted ");
        }

        if (file_exists($encFile)) {
            $keyInfo = $encFileURL . PHP_EOL . $encFile;
            file_put_contents($keyInfoFile, $keyInfo);
        }

        //$randomizeTimeX = random_int(100, 180);
        //$randomizeTimeY = random_int(100, 180);
        $commands = array();
        $allFiles = getAllTSFilesInDir($localFileDownloadDir);

        $watermarkingArray = getRandomSymlinkTSFileArray($localFileDownloadDir, $maxElements);

        error_log("Watermark: we will watermark " . count($watermarkingArray) . " " . json_encode($watermarkingArray));

        //$allFiles = array();
        $timeSpent = 0;
        $count = 0;
        $totalTimeStart = microtime(true);
        foreach ($allFiles as $tsFile) {
            if (empty($tsFile)) {
                continue;
            }
            $inputHLS_ts = "{$localFileDownloadDir}/{$tsFile}";
            $outputHLS_ts = "{$outputPath}/{$tsFile}";

            if (file_exists($outputHLS_ts) && !is_link($outputHLS_ts)) {
                continue;
            }

            $watermark = false;
            if (in_array($tsFile, $watermarkingArray)) {
                $watermark = true;
            }

            $command = getFFMPEGForSegment($tsFile, $watermark);
            if (!empty($command)) {
                $commands[] = $command;
            }
        }
        $totalTimeSpent = microtime(true) - $totalTimeStart;
        error_log("Watermark: took ($totalTimeSpent) seconds file [$outputHLS_index] ");

        $obj->ffmpeg = $commands;

        //$cmd = addcslashes(implode(" && ", $commands), '"');
        //$cmd = "bash -c \"{$cmd}\" ";
        $cmd = implode(" && ", $commands);

        error_log("Watermark: execute {$cmd} ");
        $obj->pid = __exec($cmd, true);

        file_put_contents($jsonFile, json_encode($obj));

        $tries = 0;
        while (1) {
            $tries++;
            //error_log("Watermark: checking file ({$tries}) ({$outputPath}) ");
            if (file_exists("{$outputPath}/000.ts") && $tries > 5) {
                //error_log("Watermark: file 000.ts");
                break;
            } else
            if (file_exists("{$outputPath}/003.ts")) {
                //error_log("Watermark: file 003.ts");
                break;
            } else if ($tries > 10) {
                //error_log("Watermark: file tries > 10");
                break;
            }
            sleep(1);
        }
    } else if (file_exists($jsonFile)) {
        $json = json_decode(file_get_contents($jsonFile));
        if (is_object($json)) {
            $json->lastUpdate = time();
            file_put_contents($jsonFile, json_encode($json));
            error_log("Watermark: Update $jsonFile");
        }
    }

    endWaretmark();
}

$endTime = microtime(true)-$startTime;
error_log("Watermark: finish {$outputHLS_index} took: {$endTime} seconds");

function getIndexM3U8($tries = 0, $getFirstSegments = 0) {
    global $localFileDownloadDir, $localFileDownload_index, $outputHLS_index, $outputPath, $outputURL, $encFile, $encFileURL, $jsonFile, $keyInfoFile, $hls_time, $getIndexM3U8;
    if (!empty($getIndexM3U8)) {
        return "";
    }
    $getIndexM3U8 = 1;
    //error_log("Watermark: getIndexM3U8 start");

    header('Content-Transfer-Encoding: binary');
    header('Content-Disposition: attachment; filename="index.m3u8"');
    if (!allTSFilesAreSymlinks($outputPath) && !isRunning($outputPath)) {
        $fsize = filesize($localFileDownload_index);
        header('Content-Length: ' . $fsize);
        //stopAllPids($outputTextPath);
        $handle = fopen($localFileDownload_index, "r");
        if ($handle) {
            $count = 0;
            while (($line = fgets($handle)) !== false) {
                if (preg_match('/EXT-X-PLAYLIST-TYPE:VOD/', $line)) {
                    if (file_exists($encFile)) {
                        echo $line . "#EXT-X-KEY:METHOD=AES-128,URI=\"{$encFileURL}\",IV=0x00000000000000000000000000000000" . PHP_EOL;
                    } else {
                        echo $line;
                    }
                } else if (preg_match('/[0-9]+.ts/', $line)) {
                    $count++;
                    if (!empty($getFirstSegments) && $count > $getFirstSegments) {
                        return false;
                    }
                    echo "{$outputURL}/{$line}";
                } else if (preg_match('/enc_watermarked.key/', $line)) {
                    $json = json_decode(file_get_contents($jsonFile));
                    if (is_object($json) && $obj->isMobile) {
                        echo str_replace("enc_watermarked.key", "enc_watermarked.key?isMobile=1", $line);
                    } else {
                        echo $line;
                    }
                } else {
                    echo $line;
                }
            }
            fclose($handle);
        } else {
            // error opening the file.
        }
        if (file_exists($keyInfoFile)) {
            unlink($keyInfoFile);
        }
    } else if (is_dir($outputPath)) {

        echo "#EXTM3U
#EXT-X-VERSION:3
#EXT-X-TARGETDURATION:{$hls_time}
#EXT-X-MEDIA-SEQUENCE:0
#EXT-X-DISCONTINUITY";

        if (file_exists($encFile)) {
            echo PHP_EOL . "#EXT-X-KEY:METHOD=AES-128,URI=\"{$encFileURL}\",IV=0x00000000000000000000000000000000" . PHP_EOL;
        }
        $files = getTSFiles($outputPath);
        if (empty($files) && $tries < 5) {
            sleep(5);
            return getIndexM3U8($tries + 1);
        }
        $count = 0;
        while (empty($files)) {
            $count++;
            if ($count > 60) {
                endWaretmark();
                die("TS file does not respond");
            }
            sleep(1);
            $files = getTSFiles($outputPath);
        }

        echo PHP_EOL . implode(PHP_EOL, $files);
    }
    header("Content-Encoding: none");
    header('Connection: close');
    header('Content-Length: ' . ob_get_length());
    ob_end_flush();
    ob_flush();
    flush();
    //error_log("Watermark: getIndexM3U8 end");
}

function getTSFiles($dir) {
    global $hls_time, $text, $outputURL;
    if ($dh = opendir($dir)) {
        $files = array();
        $ignoreFiles = array('.', '..', 'index.m3u8', 'enc_watermarked.key', '.keyInfo', '.obj.log');
        while (($file = readdir($dh)) !== false) {
            if (!in_array($file, $ignoreFiles) && !preg_match('/.json/', $file)) {
                //error_log("Watermark: adding ts {$file} on ($text)");
                $filePath = "$dir/{$file}";
                if (filemtime($filePath) < strtotime("-2 seconds")) {
                    $ts_file = "{$outputURL}/{$file}";
                    //$duration = getTSDuration($ts_file);
                    if (empty($duration)) {
                        $duration = $hls_time;
                    }
                    $files[] = ("#EXTINF:{$duration}," . PHP_EOL . $ts_file);
                }
            }
        }
        if (empty($files)) {
            return array();
        }
        sort($files);
        error_log("Watermark: adding ts on ($text) count: " . count($files));
        closedir($dh);
        return $files;
    }
}

function __exec($cmd, $async = false) {
    if (!$async) {
        exec($cmd . " 2>&1", $output, $return_val);
        if ($return_val !== 0) {
            error_log("Watermark: exec {$cmd} " . PHP_EOL . json_encode($output));
            return false;
        }
        return true;
    } else {
        return exec($cmd . ' > /dev/null 2>&1 & echo $!; ', $output);
    }
}

function stopAllPids($dir) {
    if (!is_dir($dir)) {
        //error_log("stopAllPids: is not a dir {$dir}");
        return false;
    }
    //error_log("stopAllPids: Searching {$dir}");
    $jsonFile = "{$dir}.obj.log";
    if (file_exists($jsonFile)) {
        $json = json_decode(file_get_contents($jsonFile));
        if (is_object($json) && isset($json->pid) && $json->pid) {
            if (isPIDRunning($json->pid)) {
                error_log("stopAllPids: Found {$jsonFile}");
                $cmd = "kill -9 {$json->pid}";
                error_log("stopAllPids: {$cmd}");
                exec($cmd);
                $json->pid = 0;
            } else {
                $json->pid = -1; // means is complete
            }
            file_put_contents($jsonFile, json_encode($json));
        } else {
            //error_log("stopAllPids: PID not running or not found ($json->pid)");
        }
    } else {
        if ($dh = opendir($dir)) {
            while (($file = readdir($dh)) !== false) {
                if ($file == '.' || $file == '..') {
                    continue;
                }
                $newDir = "{$dir}{$file}/";
                if (is_dir($newDir)) {
                    stopAllPids($newDir);
                }
            }
            closedir($dh);
        }
    }
}

function isRunning($dir) {
    global $isRunning;
    if (!is_dir($dir)) {
        //error_log("isRunning: is not a dir {$dir}");

        return false;
    }
    //error_log("isRunning: Searching {$dir}");
    $jsonFile = "{$dir}.obj.log";
    if (file_exists($jsonFile)) {
        $json = json_decode(file_get_contents($jsonFile));
        if (is_object($json) && isset($json->pid) && $json->pid) {
            if (isPIDRunning($json->pid)) {
                return true;
            }
        }
    }
    return false;
}

function totalPidsRunning($dir) {
    if (!is_dir($dir)) {
        error_log("totalPidsRunning: is not a dir {$dir}");
        return 0;
    }
    $total = 0;
    //error_log("totalPidsRunning: Searching {$dir}");
    $jsonFile = "{$dir}.obj.log";
    if (file_exists($jsonFile)) {
        $json = json_decode(file_get_contents($jsonFile));
        if (is_object($json) && isset($json->pid) && $json->pid) {
            if (isPIDRunning($json->pid)) {
                $total++;
            }
        }
    } else {
        if ($dh = opendir($dir)) {
            while (($file = readdir($dh)) !== false) {
                if ($file == '.' || $file == '..') {
                    continue;
                }
                $newDir = "{$dir}{$file}/";
                if (is_dir($newDir)) {
                    $total += totalPidsRunning($newDir);
                }
            }
            closedir($dh);
        }
    }
    return $total;
}

function canConvert($dir) {
    $jsonFile = "{$dir}/.obj.log";
    $outputHLS_index = "{$dir}/index.m3u8";
    if (file_exists($jsonFile)) {
        //error_log("canConvert: $jsonFile exists");
        $fileContent = file_get_contents($jsonFile);
        $json = json_decode($fileContent);
        if (is_object($json) && !empty($json->pid)) {
            /*
              // if index exist or it still processing, do not convert again
              if(file_exists($outputHLS_index)){
              error_log("canConvert: $outputHLS_index exists");
              return false;
              }
             * 
             */
            if (!allTSFilesAreSymlinks($dir)) {
                error_log("canConvert: NOT allTSFilesAreSymlinks");
                return false;
            }

            if (isPIDRunning($json->pid)) {
                error_log("canConvert: pid still running");
                return false;
            }
        } else {
            error_log("canConvert: pid is empty {$fileContent}");
        }
    } else {
        error_log("canConvert: $jsonFile file not found");
    }
    //error_log("canConvert: said yes");
    return true;
}

function getTSDuration($ts_file) {
    $cmd = get_ffprobe()." -loglevel quiet -print_format flat -show_entries format=duration {$ts_file}";
    exec($cmd, $output);
    if (preg_match('/format.duration="([0-9.]+)"/', $output[0], $matches)) {
        if (!empty($matches[1])) {
            return floatval($matches[1]);
        }
    }
    return 0;
}

function createSymbolicLinks($fromDir, $toDir) {
    //error_log("createSymbolicLinks($fromDir, $toDir)");
    make_path($toDir);

    if ($dh = opendir($fromDir)) {
        while (($file = readdir($dh)) !== false) {
            $destinationFile = "{$toDir}/{$file}";
            if (file_exists($destinationFile) || $file == '.' || $file == '..') {
                //error_log("createSymbolicLinks: ignored $destinationFile)");
                continue;
            }
            $cmd = "ln -sf {$fromDir}/{$file} $destinationFile";
            //error_log($cmd);
            __exec($cmd);
        }
        closedir($dh);
    }
}

function allTSFilesAreSymlinks($dir) {
    if (!is_dir($dir)) {
        //error_log("allTSFilesAreSymlinks::Checking: {$dir}  Is NOT a dir ");
        return true;
    }
    if ($dh = opendir($dir)) {
        $count = 0;
        while (($file = readdir($dh)) !== false) {
            if ($file == '.' || $file == '..' || !preg_match('/\.ts$/', $file)) {
                continue;
            }
            //error_log("allTSFilesAreSymlinks::Checking: {$dir}/{$file}");
            if (!is_link("{$dir}/{$file}")) {
                $count++;
                if ($count > 1) { // make sure you ignore the first segment that is always encode
                    //error_log("allTSFilesAreSymlinks::Checking: {$dir}/{$file} Is NOT a symlynk ");
                    return false;
                }
            }
            //error_log("allTSFilesAreSymlinks::Checking: Is not a lynk ". json_encode(linkinfo("{$dir}/{$file}")));
        }
        return true;
    }
    return false;
}

function canIDownloadVideo($dir) {
    global $localFileDownload_lock;
    if (file_exists($localFileDownload_lock)) {
        $time = file_get_contents($localFileDownload_lock);
        $newerThen10Min = $time > strtotime("-10 min");
        if ($newerThen10Min) {
            return false;
        }
    }

    if (getTotalTSFilesInDir($dir) > 0) {
        return false;
    }
    $localFileDownload_index = "$dir/index.m3u8";
    if (file_exists($localFileDownload_index)) {
        $newerThen5Min = filectime($localFileDownload_index) > strtotime("-5 min");
        if ($newerThen5Min) {
            error_log("canIDownloadVideo: index file exists and olderThen5Min");
            if (!filesize($localFileDownload_index)) {
                error_log("canIDownloadVideo: index is empty ");
                unlink($localFileDownload_index);
                return true;
            }
        }
        return false;
    }
    error_log("canIDownloadVideo: index does not exists ");
    return true;
}

function allTSFilesAreNONZeroB($dir) {
    if (!is_dir($dir)) {
        return false;
    }
    if ($dh = opendir($dir)) {
        while (($file = readdir($dh)) !== false) {
            if ($file == '.' || $file == '..' || !preg_match('/\.ts$/', $file)) {
                continue;
            }
            //error_log("allTSFilesAreSymlinks::Checking: {$dir}/{$file}");
            if (!filesize("{$dir}/{$file}")) {
                error_log("allTSFilesAreNONZeroB::Checking: {$dir}/{$file} Is a 0 B ");
                return false;
            }
            //error_log("allTSFilesAreSymlinks::Checking: Is not a lynk ". json_encode(linkinfo("{$dir}/{$file}")));
        }
        return true;
    }
    return false;
}

function getTotalTSFilesInDir($dir) {
    global $getTotalTSFilesInDir;
    if (!empty($getTotalTSFilesInDir)) {
        return $getTotalTSFilesInDir;
    }
    $total = 0;
    if ($dh = opendir($dir)) {
        while (($file = readdir($dh)) !== false) {
            if ($file == '.' || $file == '..' || !preg_match('/\.ts$/', $file)) {
                continue;
            }
            $total++;
        }
    }
    $getTotalTSFilesInDir = $total;
    return $total;
}

function getAllTSFilesInDir($dir) {
    global $getAllTSFilesInDir;
    if (!empty($getAllTSFilesInDir)) {
        return $getAllTSFilesInDir;
    }
    $files = array();
    if ($dh = opendir($dir)) {
        while (($file = readdir($dh)) !== false) {
            if ($file == '.' || $file == '..' || !preg_match('/\.ts$/', $file)) {
                continue;
            }
            $files[] = $file;
        }
    }
    sort($files);
    $getAllTSFilesInDir = $files;
    return $files;
}

function getRandomSymlinkTSFileArray($dir, $total) {
    global $skippFirstSegments;
    $totalTSFiles = getTotalTSFilesInDir($dir);
    error_log("getRandomSymlinkTSFileArray: ($totalTSFiles) ($total) {$dir}");
    $firstfile = sprintf('%03d.ts', $skippFirstSegments);
    if (!file_exists("{$dir}/{$firstfile}")) {
        $firstfile = "000.ts";
    }
    $lastfile = sprintf('%03d.ts', $totalTSFiles - 2);
    if (!file_exists("{$dir}/{$lastfile}")) {
        $lastfile = sprintf('%03d.ts', $totalTSFiles);
    }
    $files = array($firstfile, sprintf('%03d.ts', floor($totalTSFiles / 2)), $lastfile);
    for ($i = 0; $i < $total; $i++) {
        $newFile = getRandomTSFile($dir);
        //error_log("getRandomSymlinkTSFileArray: \$newFile {$newFile}");
        if (!empty($newFile) && !in_array($newFile, $files)) {
            //error_log("getRandomSymlinkTSFileArray: added {$newFile}");
            $files[] = $newFile;
        }
    }
    $files = array_unique($files);
    sort($files);
    //error_log("getRandomSymlinkTSFileArray: sort(\$files) " . json_encode($files));
    return $files;
}

function createFirstSegment() {
    global $skippFirstSegments, $outputPath, $localFileDownloadDir;
    $firstfile = sprintf('%03d.ts', $skippFirstSegments);
    $inputHLS_ts = "{$localFileDownloadDir}/{$firstfile}";
    if (!file_exists($inputHLS_ts)) {
        $firstfile = "000.ts";
        $inputHLS_ts = "{$localFileDownloadDir}/{$firstfile}";
    }
    if (!file_exists($inputHLS_ts)) {
        return false;
    }

    $outputHLS_ts = "{$outputPath}/{$firstfile}";
    if (file_exists($outputHLS_ts) && !is_link($outputHLS_ts)) {
        return false;
    }

    $ffmpegCOmmand = createWatermarkFFMPEG($inputHLS_ts, $outputHLS_ts, true);
    $start = microtime(true);
    __exec($ffmpegCOmmand);
    error_log("createFirstSegment: took " . (microtime(true) - $start) . " seconds {$outputPath}");
}

function getFFMPEGForSegment($segment, $watermarkIt) {
    $segment = intval($segment);
    global $outputPath, $localFileDownloadDir;
    $file = sprintf('%03d.ts', $segment);
    $inputHLS_ts = "{$localFileDownloadDir}/{$file}";
    if (!file_exists($inputHLS_ts)) {
        return false;
    }
    $outputHLS_ts = "{$outputPath}/{$file}";
    if (file_exists($outputHLS_ts) && !is_link($outputHLS_ts)) {
        return false;
    }

    $ffmpegCOmmand = createWatermarkFFMPEG($inputHLS_ts, $outputHLS_ts, $watermarkIt);
    return $ffmpegCOmmand;
}

function createWatermarkFFMPEG($inputHLS_ts, $outputHLS_ts, $watermarkIt = true) {
    global $watermark_fontsize, $watermark_color, $watermark_opacity, $watermarkCodec, $text, $keyInfoFile, $encFile;
    $randX = random_int(60, 120);
    $randY = random_int(60, 120);
    $command = "ffmpeg -i \"$inputHLS_ts\" ";
    if ($watermarkIt) {
        //error_log("Watermark:  {$inputHLS_ts} will have watermark");
        @unlink($outputHLS_ts);
        $command .= " -vf \"drawtext=fontfile=font.ttf:fontsize={$watermark_fontsize}:fontcolor={$watermark_color}@{$watermark_opacity}:text='{$text}' "
                . ' :x=if(eq(mod(n\,' . $randX . ')\,0)\,rand(0\,(W-tw))\,x) '
                . ' :y=if(eq(mod(n\,' . $randY . ')\,0)\,rand(0\,(H-th))\\,y) " '
                . " {$watermarkCodec} -copyts  ";
    } else {
        if (file_exists($keyInfoFile) && file_exists($encFile)) {
            $command .= " -c copy -copyts  ";
        } else {
            return false;
        }
    }
    if (file_exists($keyInfoFile) && file_exists($encFile)) {
        $command .= " -hls_key_info_file \"{$keyInfoFile}\" ";
    }
    $command .= " -y \"{$outputHLS_ts}\" ";
    return $command;
}

function getRandomSymlinkTSFile($dir) {
    $ts = rand(0, getTotalTSFilesInDir($dir));
    //error_log("getRandomSymlinkTSFile: ($ts)");
    if ($dh = opendir($dir)) {
        while (($file = readdir($dh)) !== false) {
            if ($file == '.' || $file == '..' || !preg_match('/([0-9]+)\.ts$/', $file, $matches)) {
                continue;
            }
            $fileNum = intval($matches[1]);
            if ($ts == $fileNum) {
                //error_log("getRandomSymlinkTSFile: ($file) ($ts) == ({$fileNum})");
                if (is_link("{$dir}/{$file}")) {
                    return $file;
                } else {
                    //error_log("getRandomSymlinkTSFile: ({$dir}/{$file}) not a symlink ". json_encode(linkinfo("{$dir}/{$file}")));
                    $ts++;
                }
            }
        }
    }
    return false;
}

function getRandomTSFile($dir) {
    $total = getTotalTSFilesInDir($dir);
    $ts = rand(0, $total);
    $file = sprintf('%03d.ts', $ts);
    $filePath = "{$dir}/{$file}";
    if (filesize($filePath)) {
        return $file;
    }
    return false;
}

function startWaretmark() {
    global $lockDir, $lockFilePath, $max_process_at_the_same_time;

    if (!is_dir($lockDir)) {
        mkdir($lockDir, 0755, true);
    }

    $fi = new FilesystemIterator($lockDir, FilesystemIterator::SKIP_DOTS);
    $totalFiles = iterator_count($fi);
    if ($totalFiles > $max_process_at_the_same_time) {
        endWaretmark();
        die("startWaretmark: too many processing now {$totalFiles}");
    }
}

function endWaretmark() {
    global $lockDir, $lockFilePath;
    @unlink($lockFilePath);
}

function getHowManyFFMPEG() {
    $cmd = "ps -aux | grep -i \"ffmpeg.*drawtext\"";
    exec($cmd, $output);
    return count($output) - 1;
}

function detectEmptyTS($line) {
    global $localFileDownloadDir;
    if ($dh = opendir($localFileDownloadDir)) {
        while (($file = readdir($dh)) !== false) {
            if ($file == '.' || $file == '..' || !preg_match('/\.ts$/', $file)) {
                continue;
            }
            $filename = "{$localFileDownloadDir}/{$file}";
            if (!filesize($filename)) {
                error_log("detectEmptyTS: ($line) $filename");
            }
        }
    }
}
