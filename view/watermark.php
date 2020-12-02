<?php

$watermark_fontsize = "(h/30)";
$watermark_color = "yellow";
$watermark_opacity = 0.5;
$hls_time = 10;
$max_process_at_the_same_time = 5;


require_once dirname(__FILE__) . '/../videos/configuration.php';
require_once $global['systemRootPath'] . 'objects/Encoder.php';
require_once $global['systemRootPath'] . 'objects/Login.php';
require_once $global['systemRootPath'] . 'objects/Streamer.php';
//header("Access-Control-Allow-Origin: *");

if (!empty($_REQUEST['_'])) {
    $array = json_decode(base64_decode($_REQUEST['_']));
    foreach ($array as $key => $value) {
        $_REQUEST[$key] = $value;
    }
    unset($_REQUEST['_']);
}


$domain = get_domain($_REQUEST['file']);


if(!isSameDomain($_REQUEST['file'], $global['webSiteRootURL'])){
    if(empty($global['watermarkDomainWhitelist'])){
        die("Create an array in your configuration.php file with the allowed domains \$global['watermarkDomainWhitelist']");
    }
}

// create an array in your configuration.php file with the allowed domains $global['watermarkDomainWhitelist']
if(!empty($global['watermarkDomainWhitelist'])){
    $found = false;
    foreach ($global['watermarkDomainWhitelist'] as $value) {
        if(isSameDomain($_REQUEST['file'], $value)){
            $found = true;
            break;
        }
    }
    if(empty($found)){
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

//error_log("Watermark: Start " . json_encode($_REQUEST));

$watermarkDir = $global['systemRootPath'] . 'videos/watermarked/';

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

$input = "{$_REQUEST['file']}" . ((strpos($_REQUEST['file'], '?') !== false) ? "&" : "?") . "watermark_token={$_REQUEST['watermark_token']}";

$text = $_REQUEST['watermark_text'];
$outputTextPath = "$dir{$_REQUEST['videos_id']}/" . md5("{$text}")."/";
$outputPath = "{$outputTextPath}{$obj->resolution}";
$outputURL = str_replace($global['systemRootPath'], $global['webSiteRootURL'], $outputPath);

$outputHLS_ts = "{$outputPath}/%03d.ts";
$outputHLS_index = "{$outputPath}/index.m3u8";
$outputHLS = "  -hls_segment_filename \"{$outputHLS_ts}\" \"{$outputHLS_index}\" ";

$jsonFile = "$outputPath/.obj.log";
$encFile = "$outputPath/enc_watermarked.key";
$keyInfoFile = "$outputPath/.keyInfo";
$encFileURL = "{$outputURL}/enc_watermarked.key";

if(!amIrunning($outputPath)){
    
    $localFileName = "video_{$_REQUEST['videos_id']}.mp4";
    $localFilePath = "$dir{$_REQUEST['videos_id']}/{$localFileName}";
    make_path("$dir{$_REQUEST['videos_id']}/");
    if(!file_exists($localFilePath)){
        $ffmpeg = "ffmpeg -i \"$input\" -c copy -bsf:a aac_adtstoasc {$localFilePath} ";

        error_log("Watermark: download video $ffmpeg");

        //var_dump($ffmpeg);exit;
        $obj->pid = __exec($ffmpeg);
    }
    
    $totalPidsRunning = totalPidsRunning($watermarkDir);
    //error_log("totalPidsRunning: $totalPidsRunning");
    if($totalPidsRunning>=$max_process_at_the_same_time){
        $obj->msg = "Too many running now, total: $totalPidsRunning from max of $max_process_at_the_same_time";
        die(json_encode($obj));
    }

    if ($obj->isMobile) {
        $encFileURL .= "?isMobile=1";
    }

    error_log("Watermark: $outputHLS_index");
    if (canConvert($outputPath)) {
        //$cmd = "rm -fr {$outputTextPath}"; // this will make other process stops and saves CPU resources
        //__exec($cmd);
        stopAllPids($outputTextPath);

        make_path($outputPath);

        $cmd = "openssl rand 16 > {$encFile}";
        __exec($cmd);

        $keyInfo = $encFileURL . PHP_EOL . $encFile;
        file_put_contents($keyInfoFile, $keyInfo);

        $randomizeTimeX = random_int(100, 180);
        $randomizeTimeY = random_int(100, 180);
        $ffmpeg = "ffmpeg -i \"$localFilePath\" "
                . " -vf \"drawtext=fontfile=font.ttf:fontsize={$watermark_fontsize}:fontcolor={$watermark_color}@{$watermark_opacity}:text='{$text}': "
                . ' x=if(eq(mod(n\,' . $randomizeTimeX . ')\,0)\,rand(0\,(W-tw))\,x): '
                . ' y=if(eq(mod(n\,' . $randomizeTimeY . ')\,0)\,rand(0\,(H-th))\,y)" '
                . "  -f hls -force_key_frames \"expr:gte(t,n_forced*{$hls_time})\"  -segment_list_size 0 -segment_time {$hls_time} " // I need that to be able to create the m3u8 before finish the transcoding
                . " -hls_key_info_file \"{$keyInfoFile}\" "
                . " -hls_time {$hls_time} -hls_list_size 0  -hls_playlist_type vod {$outputHLS} ";

        $obj->ffmpeg = $ffmpeg;
        error_log("Watermark: $ffmpeg");

        //var_dump($ffmpeg);exit;
        $obj->pid = __exec($ffmpeg, true);

        file_put_contents($jsonFile, json_encode($obj));

        $tries = 0;
        while (1) {
            $tries++;
            error_log("Watermark: checking file ({$tries}) ({$outputPath}) ");
            if (file_exists("{$outputPath}/000.ts") && $tries > 5) {
                error_log("Watermark: file 000.ts");
                break;
            } else
            if (file_exists("{$outputPath}/003.ts")) {
                error_log("Watermark: file 003.ts");
                break;
            } else if ($tries > 10) {
                error_log("Watermark: file tries > 10");
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
}

header('Content-Transfer-Encoding: binary');
header('Content-Disposition: attachment; filename="index.m3u8"');
//header('Connection: Keep-Alive');
//header('Expires: 0');
//header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
//header('Pragma: public');
//header('Content-Type: application/vnd.apple.mpegurl');
//header('Content-Type: application/x-mpegURL');
//header("Content-Type: text/plain");
//echo $outputPath;
// Open a directory, and read its contents
if (file_exists($outputHLS_index)) {
    $fsize = filesize($outputHLS_index);
    header('Content-Length: ' . $fsize);
    stopAllPids($outputTextPath);
    $handle = fopen($outputHLS_index, "r");
    if ($handle) {
        while (($line = fgets($handle)) !== false) {
            if (preg_match('/[0-9]+.ts/', $line)) {
                echo "{$outputURL}/{$line}";
            } else if (preg_match('/enc_watermarked.key/', $line)) {
                $json = json_decode(file_get_contents($jsonFile));
                if (is_object($json) && $obj->isMobile) {
                    echo str_replace("enc_watermarked.key", "enc_watermarked.key?isMobile=1", $line);
                }else{
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
    if(file_exists($keyInfoFile)){
        unlink($keyInfoFile);
    }
    exit;
} else if (is_dir($outputPath)) {
    echo "#EXTM3U
#EXT-X-VERSION:3
#EXT-X-TARGETDURATION:{$hls_time}
#EXT-X-MEDIA-SEQUENCE:0
#EXT-X-DISCONTINUITY
#EXT-X-KEY:METHOD=AES-128,URI=\"{$encFileURL}\",IV=0x00000000000000000000000000000000";
        $files = getTSFiles($outputPath);
        $count = 0;
        while(empty($files)){
            $count++;
            if($count>60){
                die("TS file does not respond");
            }
            sleep(1);
            $files = getTSFiles($outputPath);
        }     
        
        echo PHP_EOL . implode( PHP_EOL, $files);
        exit;
}
error_log("Watermark: finish");

function getTSFiles($dir){
    global $hls_time, $text, $outputURL;
    if ($dh = opendir($dir)) {
        $files = array();
        $ignoreFiles = array('.', '..', 'index.m3u8', 'enc_watermarked.key', '.keyInfo', '.obj.log');
        while (($file = readdir($dh)) !== false) {
            if (!in_array($file, $ignoreFiles) && !preg_match('/.json/', $file)) {
                //error_log("Watermark: adding ts {$file} on ($text)");
                $filePath = "$dir/{$file}";
                if(filemtime($filePath) < strtotime("-2 seconds")){
                    $ts_file = "{$outputURL}/{$file}";
                    //$duration = getTSDuration($ts_file);
                    if(empty($duration)){
                       $duration =  $hls_time;
                    }
                    $files[] = ("#EXTINF:{$duration}," . PHP_EOL .$ts_file);
                }
            }
        }
        if(empty($files)){
            return array();
        }
        sort($files);
        error_log("Watermark: adding ts on ($text) count: ". count($files));
        closedir($dh);
        return $files;
    }
}

function __exec($cmd, $async = false) {
    ob_flush();
    if (!$async) {
        exec($cmd . " 2>&1", $output, $return_val);
        if ($return_val !== 0) {
            error_log("Watermark: " . json_encode($output));
            return false;
        }
        return true;
    } else {
        return exec($cmd . ' > /dev/null 2>&1 & echo $!; ', $output);
    }
}

function stopAllPids($dir) {
    if(!is_dir($dir)){
        error_log("stopAllPids: is not a dir {$dir}");
        return false;
    }
    error_log("stopAllPids: Searching {$dir}");
    $jsonFile = "{$dir}.obj.log";
    if (file_exists($jsonFile)) {
        $json = json_decode(file_get_contents($jsonFile));
        if (is_object($json) && $json->pid) {
            if(isPIDRunning($json->pid)){
                error_log("stopAllPids: Found {$jsonFile}");
                $cmd = "kill -9 {$json->pid}";
                error_log("stopAllPids: {$cmd}");
                exec($cmd);
                $json->pid = 0;
            }else{
                $json->pid = -1; // means is complete
            }
            file_put_contents($jsonFile, json_encode($json));
        }else{
            error_log("stopAllPids: PID not running or not found ($json->pid)");
        }
    } else {
        if ($dh = opendir($dir)) {
            while (($file = readdir($dh)) !== false) {
                if($file=='.' || $file == '..'){
                    continue;
                }
                $newDir = "{$dir}{$file}/";
                if(is_dir($newDir)){
                    stopAllPids($newDir);
                }
            }
            closedir($dh);
        }
    }
}

function amIrunning($dir){
    $jsonFile = "{$dir}.obj.log";
    if (file_exists($jsonFile)) {
        $json = json_decode(file_get_contents($jsonFile));
        if (is_object($json) && $json->pid) {
            if(isPIDRunning($json->pid)){
                return true;
            }
        }
    }
    return false;
}

function totalPidsRunning($dir) {
    if(!is_dir($dir)){
        error_log("totalPidsRunning: is not a dir {$dir}");
        return 0;
    }
    $total = 0;
    //error_log("totalPidsRunning: Searching {$dir}");
    $jsonFile = "{$dir}.obj.log";
    if (file_exists($jsonFile)) {
        $json = json_decode(file_get_contents($jsonFile));
        if (is_object($json) && $json->pid) {
            if(isPIDRunning($json->pid)){
                $total++;
            }
        }
    } else {
        if ($dh = opendir($dir)) {
            while (($file = readdir($dh)) !== false) {
                if($file=='.' || $file == '..'){
                    continue;
                }
                $newDir = "{$dir}{$file}/";
                if(is_dir($newDir)){
                    $total += totalPidsRunning($newDir);
                }
            }
            closedir($dh);
        }
    }
    return $total;
}

function canConvert($dir){
    $jsonFile = "{$dir}/.obj.log";
    $outputHLS_index = "{$dir}/index.m3u8";
    if (file_exists($jsonFile)) {
        error_log("canConvert: $jsonFile exists");
        $fileContent = file_get_contents($jsonFile);
        $json = json_decode($fileContent);
        if (is_object($json) && !empty($json->pid)) {
            // if index exist or it still processing, do not convert again
            if(file_exists($outputHLS_index)){
                error_log("canConvert: $outputHLS_index exists");
                return false;
            }
            
            if(isPIDRunning($json->pid)){
                error_log("canConvert: pid still running");
                return false;
            }
        }else{
            error_log("canConvert: pid is empty {$fileContent}");
        }
    }else{
        error_log("canConvert: $jsonFile file not found");
    }
    error_log("canConvert: said yes");
    return true;
}

function getTSDuration($ts_file){
    $cmd = "ffprobe -loglevel quiet -print_format flat -show_entries format=duration {$ts_file}";
    exec($cmd, $output);
    if(preg_match('/format.duration="([0-9.]+)"/', $output[0], $matches)){
        if(!empty($matches[1])){
            return floatval($matches[1]);
        }
    }
    return 0;
}