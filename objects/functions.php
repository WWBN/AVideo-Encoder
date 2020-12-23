<?php

function local_get_contents($path) {
    if (function_exists('fopen')) {
        $myfile = fopen($path, "r") or die("Unable to open file!");
        $text = fread($myfile, filesize($path));
        fclose($myfile);
        return $text;
    }
    return @file_get_contents($path);
}

function get_ffmpeg($ignoreGPU=false) {
    global $global;
    //return 'ffmpeg -user_agent "'.getSelfUserAgent("FFMPEG").'" ';
    //return 'ffmpeg -headers "User-Agent: '.getSelfUserAgent("FFMPEG").'" ';
    $ffmpeg = 'ffmpeg  ';
    if (empty($ignoreGPU) && !empty($global['ffmpegGPU'])) {
        $ffmpeg .= ' --enable-nvenc ';
    }
    if (!empty($global['ffmpeg'])) {
        $ffmpeg = "{$global['ffmpeg']}{$ffmpeg}";
    }
    return $ffmpeg;
}

function get_ffprobe() {
    global $global;
    //return 'ffmpeg -user_agent "'.getSelfUserAgent("FFMPEG").'" ';
    //return 'ffmpeg -headers "User-Agent: '.getSelfUserAgent("FFMPEG").'" ';
    $ffmpeg = 'ffprobe  ';
    if (!empty($global['ffmpeg'])) {
        $ffmpeg = "{$global['ffmpeg']}{$ffmpeg}";
    }
    return $ffmpeg;
}

function url_set_file_context($Url, $ctx = "") {
    // I wasn't sure what to call this function because I'm not sure exactly what it does.
    // But I know that it is needed to display the progress indicators
    // on the main encoder web page.
    // It has been stripped of file_get_contents and fetch_http_file_contents 
    // which causes huge memory usage. If you upload a 9GB file the server must have a minimum of 18G 
    // in the old scheme. Now such large memory requirements are not necessary. 
    // the encoder has already downloaded the file
    // it just needs to be symlinked
    if (empty($ctx)) {
        $opts = array(
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
                "allow_self_signed" => true
            )
        );
        $context = stream_context_create($opts);
    } else {
        $context = $ctx;
    }
    // some times the path has special chars
    if (!filter_var($Url, FILTER_VALIDATE_URL)) {
        if (!file_exists($Url)) {
            $Url = utf8_decode($Url);
        }
    }
}

function getSelfUserAgent($complement = "") {
    global $global;
    $agent = 'AVideoEncoder ';
    $agent .= parse_url($global['webSiteRootURL'], PHP_URL_HOST);
    $agent .= " {$complement}";
    return $agent;
}

function url_get_contents($Url, $ctx = "") {
    global $global;
    $agent = getSelfUserAgent();
    if (empty($ctx)) {
        $opts = array(
            'http' => array('header' => "User-Agent: {$agent}\r\n"),
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
                "allow_self_signed" => true
            )
        );
        $context = stream_context_create($opts);
    } else {
        $context = $ctx;
    }

    // some times the path has special chars
    if (!filter_var($Url, FILTER_VALIDATE_URL)) {
        if (!file_exists($Url)) {
            $Url = utf8_decode($Url);
        }
    }

    if (ini_get('allow_url_fopen')) {
        try {
            $tmp = @file_get_contents($Url, false, $context);
            if ($tmp != false) {
                return remove_utf8_bom($tmp);
            }
        } catch (ErrorException $e) {
            try {
                fetch_http_file_contents($Url);
            } catch (ErrorException $e) {
                error_log("Error on get Content");
            }
        }
    } else if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_USERAGENT, $agent);
        curl_setopt($ch, CURLOPT_URL, $Url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $output = curl_exec($ch);
        curl_close($ch);
        return remove_utf8_bom($output);
    }
    $content = @file_get_contents($Url, false, $context);
    if (empty($content)) {
        return "";
    }
    return remove_utf8_bom($content);
}

function fetch_http_file_contents($url) {
    $hostname = parse_url($url, PHP_URL_HOST);

    if ($hostname == FALSE) {
        return FALSE;
    }

    $host_has_ipv6 = FALSE;
    $host_has_ipv4 = FALSE;
    $file_response = FALSE;
    $dns_records = @dns_get_record($hostname, DNS_AAAA + DNS_A);
    if (!empty($dns_records) && is_array($dns_records)) {
        foreach ($dns_records as $dns_record) {
            if (isset($dns_record['type'])) {
                switch ($dns_record['type']) {
                    case 'AAAA':
                        $host_has_ipv6 = TRUE;
                        break;
                    case 'A':
                        $host_has_ipv4 = TRUE;
                        break;
                }
            }
        }
    }
    if ($host_has_ipv6 === TRUE) {
        $file_response = file_get_intbound_contents($url, '[0]:0');
    }
    if ($host_has_ipv4 === TRUE && $file_response == FALSE) {
        $file_response = file_get_intbound_contents($url, '0:0');
    }
    return $file_response;
}

function file_get_intbound_contents($url, $bindto_addr_family) {
    $stream_context = stream_context_create(
            array(
                'socket' => array(
                    'bindto' => $bindto_addr_family
                ),
                'http' => array(
                    'timeout' => 20,
                    'method' => 'GET'
    )));

    return file_get_contents($url, FALSE, $stream_context);
}

// Returns a file size limit in bytes based on the PHP upload_max_filesize
// and post_max_size
function file_upload_max_size() {
    static $max_size = -1;

    if ($max_size < 0) {
        // Start with post_max_size.
        $max_size = parse_size(ini_get('post_max_size'));

        // If upload_max_size is less, then reduce. Except if upload_max_size is
        // zero, which indicates no limit.
        $upload_max = parse_size(ini_get('upload_max_filesize'));
        if ($upload_max > 0 && $upload_max < $max_size) {
            $max_size = $upload_max;
        }
    }
    return $max_size;
}

function parse_size($size) {
    $unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
    $size = preg_replace('/[^0-9\.]/', '', $size); // Remove the non-numeric characters from the size.
    if ($unit) {
        // Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
        return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
    } else {
        return round($size);
    }
}

function humanFileSize($size, $unit = "") {
    if ((!$unit && $size >= 1 << 30) || $unit == "GB")
        return number_format($size / (1 << 30), 2) . "GB";
    if ((!$unit && $size >= 1 << 20) || $unit == "MB")
        return number_format($size / (1 << 20), 2) . "MB";
    if ((!$unit && $size >= 1 << 10) || $unit == "KB")
        return number_format($size / (1 << 10), 2) . "KB";
    return number_format($size) . " bytes";
}

function get_max_file_size() {
    return humanFileSize(file_upload_max_size());
}

function humanTiming($time) {
    $time = time() - $time; // to get the time since that moment
    $time = ($time < 1) ? 1 : $time;
    $tokens = array(
        31536000 => __('year'),
        2592000 => __('month'),
        604800 => __('week'),
        86400 => __('day'),
        3600 => __('hour'),
        60 => __('minute'),
        1 => __('second')
    );

    foreach ($tokens as $unit => $text) {
        if ($time < $unit)
            continue;
        $numberOfUnits = floor($time / $unit);
        return $numberOfUnits . ' ' . $text . (($numberOfUnits > 1) ? 's' : '');
    }
}

function checkVideosDir() {
    $dir = "../videos";
    if (file_exists($dir)) {
        if (is_writable($dir)) {
            return true;
        } else {
            return false;
        }
    } else {
        return mkdir($dir);
    }
}

function isApache() {
    if (strpos($_SERVER['SERVER_SOFTWARE'], 'Apache') !== false)
        return true;
    else
        return false;
}

function isPHP($version = "'7.0.0'") {
    if (version_compare(PHP_VERSION, $version) >= 0) {
        return true;
    } else {
        return false;
    }
}

function modRewriteEnabled() {
    if (!function_exists('apache_get_modules')) {
        ob_start();
        phpinfo(INFO_MODULES);
        $contents = ob_get_contents();
        ob_end_clean();
        return (strpos($contents, 'mod_rewrite') !== false);
    } else {
        return in_array('mod_rewrite', apache_get_modules());
    }
}

function isFFMPEG() {
    return trim(shell_exec('which ffmpeg'));
}

function isYoutubeDL() {
    return trim(shell_exec('which youtube-dl'));
}

function isExifToo() {
    return trim(shell_exec('which exiftool'));
}

function getPathToApplication() {
    return str_replace("install/index.php", "", $_SERVER["SCRIPT_FILENAME"]);
}

function getURLToApplication() {
    $url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $url = explode("install/index.php", $url);
    $url = $url[0];
    return $url;
}

//max_execution_time = 7200
function check_max_execution_time() {
    $max_size = ini_get('max_execution_time');
    $recomended_size = 7200;
    if ($recomended_size > $max_size) {
        return false;
    } else {
        return true;
    }
}

//post_max_size = 100M
function check_post_max_size() {
    $max_size = parse_size(ini_get('post_max_size'));
    $recomended_size = parse_size('100M');
    if ($recomended_size > $max_size) {
        return false;
    } else {
        return true;
    }
}

//upload_max_filesize = 100M
function check_upload_max_filesize() {
    $max_size = parse_size(ini_get('upload_max_filesize'));
    $recomended_size = parse_size('100M');
    if ($recomended_size > $max_size) {
        return false;
    } else {
        return true;
    }
}

//memory_limit = 100M
function check_memory_limit() {
    $max_size = parse_size(ini_get('memory_limit'));
    $recomended_size = parse_size('512M');
    if ($recomended_size > $max_size) {
        return false;
    } else {
        return true;
    }
}

function check_mysqlnd() {
    return function_exists('mysqli_fetch_all');
}

function base64DataToImage($imgBase64) {
    $img = $imgBase64;
    $img = str_replace('data:image/png;base64,', '', $img);
    $img = str_replace(' ', '+', $img);
    return base64_decode($img);
}

function getRealIpAddr() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {   //check ip from share internet
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {   //to check ip is pass from proxy
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

function cleanString($text) {
    $utf8 = array(
        '/[áàâãªä]/u' => 'a',
        '/[ÁÀÂÃÄ]/u' => 'A',
        '/[ÍÌÎÏ]/u' => 'I',
        '/[íìîï]/u' => 'i',
        '/[éèêë]/u' => 'e',
        '/[ÉÈÊË]/u' => 'E',
        '/[óòôõºö]/u' => 'o',
        '/[ÓÒÔÕÖ]/u' => 'O',
        '/[úùûü]/u' => 'u',
        '/[ÚÙÛÜ]/u' => 'U',
        '/ç/' => 'c',
        '/Ç/' => 'C',
        '/ñ/' => 'n',
        '/Ñ/' => 'N',
        '/–/' => '-', // UTF-8 hyphen to "normal" hyphen
        '/[’‘‹›‚]/u' => ' ', // Literally a single quote
        '/[“”«»„]/u' => ' ', // Double quote
        '/ /' => ' ', // nonbreaking space (equiv. to 0x160)
    );
    return preg_replace(array_keys($utf8), array_values($utf8), $text);
}

/**
 * @brief return true if running in CLI, false otherwise
 * if is set $_GET['ignoreCommandLineInterface'] will return false
 * @return boolean
 */
function isCommandLineInterface() {
    return (empty($_GET['ignoreCommandLineInterface']) && php_sapi_name() === 'cli');
}

/**
 * @brief show status message as text (CLI) or JSON-encoded array (web)
 *
 * @param array $statusarray associative array with type/message pairs
 * @return string
 */
function status($statusarray) {
    if (isCommandLineInterface()) {
        foreach ($statusarray as $status => $message) {
            echo $status . ":" . $message . "\n";
        }
    } else {
        echo json_encode(array_map(
                        function($text) {
                    return nl2br($text);
                }
                        , $statusarray));
    }
}

/**
 * @brief show status message and die
 *
 * @param array $statusarray associative array with type/message pairs
 */
function croak($statusarray) {
    status($statusarray);
    die;
}

function getSecondsTotalVideosLength() {
    $configFile = dirname(__FILE__) . '/../videos/configuration.php';
    require_once $configFile;
    global $global;
    $sql = "SELECT * FROM videos v ";
    $res = $global['mysqli']->query($sql);
    $seconds = 0;
    while ($row = $res->fetch_assoc()) {
        $seconds += parseDurationToSeconds($row['duration']);
    }
    return $seconds;
}

function getMinutesTotalVideosLength() {
    $seconds = getSecondsTotalVideosLength();
    return floor($seconds / 60);
}

function parseDurationToSeconds($str) {
    $durationParts = explode(":", $str);
    if (empty($durationParts[1]))
        return 0;
    $minutes = (intval($durationParts[0]) * 60) + intval($durationParts[1]);
    return intval($durationParts[2]) + ($minutes * 60);
}

function secondsToVideoTime($seconds) {
    if (!is_numeric($seconds)) {
        return $seconds;
    }
    $seconds = round($seconds);
    $hours = floor($seconds / 3600);
    $mins = floor($seconds / 60 % 60);
    $secs = floor($seconds % 60);
    return sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
}

function parseSecondsToDuration($seconds) {
    return secondsToVideoTime($seconds);
}

/**
 * 
 * @global type $global
 * @param type $mail
 * call it before send mail to let AVideo decide the method
 */
function setSiteSendMessage(&$mail) {
    global $global;
    require_once $global['systemRootPath'] . 'objects/configuration.php';
    $config = new Configuration();

    if ($config->getSmtp()) {
        $mail->IsSMTP(); // enable SMTP
        $mail->SMTPAuth = true; // authentication enabled
        $mail->SMTPSecure = $config->getSmtpSecure(); // secure transfer enabled REQUIRED for Gmail
        $mail->Host = $config->getSmtpHost();
        $mail->Port = $config->getSmtpPort();
        $mail->Username = $config->getSmtpUsername();
        $mail->Password = $config->getSmtpPassword();
    } else {
        $mail->isSendmail();
    }
}

function decideFromPlugin() {
    $json_file = url_get_contents(Login::getStreamerURL() . "plugin/CustomizeAdvanced/advancedCustom.json.php");
    // convert the string to a json object
    $advancedCustom = json_decode($json_file);
    fixAdvancedCustom($advancedCustom);
    if (!empty($advancedCustom->showOnlyEncoderAutomaticResolutions)) {
        return array("mp4" => 7, "webm" => 8);
    }
    if (
            empty($advancedCustom->doNotShowEncoderResolutionLow) && empty($advancedCustom->doNotShowEncoderResolutionSD) && empty($advancedCustom->doNotShowEncoderResolutionHD)) {
        return array("mp4" => 80, "webm" => 87);
    }
    if (
            empty($advancedCustom->doNotShowEncoderResolutionLow) && empty($advancedCustom->doNotShowEncoderResolutionSD)) {
        return array("mp4" => 77, "webm" => 84);
    }
    if (
            empty($advancedCustom->doNotShowEncoderResolutionLow) && empty($advancedCustom->doNotShowEncoderResolutionHD)) {
        return array("mp4" => 79, "webm" => 86);
    }
    if (
            empty($advancedCustom->doNotShowEncoderResolutionSD) && empty($advancedCustom->doNotShowEncoderResolutionHD)) {
        return array("mp4" => 78, "webm" => 85);
    }
    if (empty($advancedCustom->doNotShowEncoderResolutionLow)) {
        return array("mp4" => 74, "webm" => 81);
    }
    if (empty($advancedCustom->doNotShowEncoderResolutionSD)) {
        return array("mp4" => 75, "webm" => 82);
    }
    if (empty($advancedCustom->doNotShowEncoderResolutionHD)) {
        return array("mp4" => 76, "webm" => 83);
    }
    return array("mp4" => 80, "webm" => 87);
}

/**
 * Return the formats table column order
 * @return int
 */
function decideFormatOrder() {
    if (!empty($_GET['webm']) && empty($_POST['webm'])) {
        $_POST['webm'] = $_GET['webm'];
    }
    error_log("decideFormatOrder: " . json_encode($_POST));
    if (!empty($_POST['inputAutoHLS']) && strtolower($_POST['inputAutoHLS']) !== "false") {
        error_log("decideFormatOrder: auto HLS");
        $_SESSION['format'] = 'inputAutoHLS';
        return (6);
    } else
    if (!empty($_POST['inputAutoMP4']) && strtolower($_POST['inputAutoMP4']) !== "false") {
        error_log("decideFormatOrder: auto MP4");
        $_SESSION['format'] = 'inputAutoMP4';
        return (7);
    } else
    if (!empty($_POST['inputAutoWebm']) && strtolower($_POST['inputAutoWebm']) !== "false") {
        error_log("decideFormatOrder: auto WebM");
        $_SESSION['format'] = 'inputAutoWebm';
        return (8);
    } else
    if (!empty($_POST['inputAutoAudio']) && strtolower($_POST['inputAutoAudio']) !== "false") {
        error_log("decideFormatOrder: auto Audio");
        $_SESSION['format'] = 'inputAutoAudio';
        return (60);
    } else
    if (!empty($_POST['inputHLS']) && strtolower($_POST['inputHLS']) !== "false") {
        error_log("decideFormatOrder: Multi bitrate HLS encrypted");
        return (9);
    } else
    if (empty($_POST['webm']) || $_POST['webm'] === 'false') {
        // mp4 only
        if (
                !empty($_POST['inputLow']) && $_POST['inputLow'] !== 'false' &&
                !empty($_POST['inputSD']) && $_POST['inputSD'] !== 'false' &&
                !empty($_POST['inputHD']) && $_POST['inputHD'] !== 'false'
        ) { // all resolutions
            error_log("decideFormatOrder: MP4 All");
            return (80);
        } else if (
                !empty($_POST['inputLow']) && $_POST['inputLow'] !== 'false' &&
                !empty($_POST['inputHD']) && $_POST['inputHD'] !== 'false'
        ) {
            error_log("decideFormatOrder: MP4 Low - HD");
            return (79);
        } else if (
                !empty($_POST['inputSD']) && $_POST['inputSD'] !== 'false' &&
                !empty($_POST['inputHD']) && $_POST['inputHD'] !== 'false'
        ) {
            error_log("decideFormatOrder: MP4 SD - HD");
            return (78);
        } else if (
                !empty($_POST['inputLow']) && $_POST['inputLow'] !== 'false' &&
                !empty($_POST['inputSD']) && $_POST['inputSD'] !== 'false'
        ) {
            error_log("decideFormatOrder: MP4 Low SD");
            return (77);
        } else if (
                !empty($_POST['inputHD']) && $_POST['inputHD'] !== 'false'
        ) {
            error_log("decideFormatOrder: MP4 HD");
            return (76);
        } else if (
                !empty($_POST['inputSD']) && $_POST['inputSD'] !== 'false'
        ) {
            error_log("decideFormatOrder: MP4 SD");
            return (75);
        } else if (
                !empty($_POST['inputLow']) && $_POST['inputLow'] !== 'false'
        ) {
            error_log("decideFormatOrder: MP4 LOW");
            return (74);
        } else {
            $decide = decideFromPlugin();
            return $decide['mp4'];
        }
    } else {
        // mp4 and webm
        if (
                !empty($_POST['inputLow']) && $_POST['inputLow'] !== 'false' &&
                !empty($_POST['inputSD']) && $_POST['inputSD'] !== 'false' &&
                !empty($_POST['inputHD']) && $_POST['inputHD'] !== 'false'
        ) { // all resolutions
            return (87);
        } else if (
                !empty($_POST['inputLow']) && $_POST['inputLow'] !== 'false' &&
                !empty($_POST['inputHD']) && $_POST['inputHD'] !== 'false'
        ) {
            return (86);
        } else if (
                !empty($_POST['inputSD']) && $_POST['inputSD'] !== 'false' &&
                !empty($_POST['inputHD']) && $_POST['inputHD'] !== 'false'
        ) {
            return (85);
        } else if (
                !empty($_POST['inputLow']) && $_POST['inputLow'] !== 'false' &&
                !empty($_POST['inputSD']) && $_POST['inputSD'] !== 'false'
        ) {
            return (84);
        } else if (
                !empty($_POST['inputHD']) && $_POST['inputHD'] !== 'false'
        ) {
            return (83);
        } else if (
                !empty($_POST['inputSD']) && $_POST['inputSD'] !== 'false'
        ) {
            return (82);
        } else if (
                !empty($_POST['inputLow']) && $_POST['inputLow'] !== 'false'
        ) {
            return (81);
        } else {
            $decide = decideFromPlugin();
            return $decide['webm'];
        }
    }
    return 1;
}

function getUpdatesFiles() {
    global $config, $global;
    $files1 = scandir($global['systemRootPath'] . "update");
    $updateFiles = array();
    foreach ($files1 as $value) {
        preg_match("/updateDb.v([0-9.]*).sql/", $value, $match);
        if (!empty($match)) {
            if ($config->currentVersionLowerThen($match[1])) {
                $updateFiles[] = array('filename' => $match[0], 'version' => $match[1]);
            }
        }
    }
    return $updateFiles;
}

function ip_is_private($ip) {
    $pri_addrs = array(
        '10.0.0.0|10.255.255.255', // single class A network
        '172.16.0.0|172.31.255.255', // 16 contiguous class B network
        '192.168.0.0|192.168.255.255', // 256 contiguous class C network
        '169.254.0.0|169.254.255.255', // Link-local address also refered to as Automatic Private IP Addressing
        '127.0.0.0|127.255.255.255' // localhost
    );

    $long_ip = ip2long($ip);
    if ($long_ip != -1) {

        foreach ($pri_addrs AS $pri_addr) {
            list ($start, $end) = explode('|', $pri_addr);

            // IF IS PRIVATE
            if ($long_ip >= ip2long($start) && $long_ip <= ip2long($end)) {
                return true;
            }
        }
    }

    return false;
}

/**
 * webservice to use the streamer to encode the password
 * @param type $password
 * @param type $streamerURL
 * @return type
 */
function encryptPassword($password, $streamerURL) {
    $url = "{$streamerURL}objects/encryptPass.json.php?pass=" . urlencode($password);
    $streamerEncrypt = json_decode(url_get_contents($url));
    if (empty($streamerEncrypt) || empty($streamerEncrypt->encryptedPassword)) {
        error_log("ERROR on encryptPassword " . $url);
    }
    return $streamerEncrypt->encryptedPassword;
}

function zipDirectory($destinationFile) {
    // Get real path for our folder
    $rootPath = realpath($destinationFile);
    $zipPath = rtrim($destinationFile, "/") . ".zip";
    // Initialize archive object
    $zip = new ZipArchive();
    if(!is_object($zip)){
        $zip = new \ZipArchive;
    }
    $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    // Create recursive directory iterator
    /** @var SplFileInfo[] $files */
    $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($rootPath), RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $name => $file) {
        // Skip directories (they would be added automatically)
        if (!$file->isDir()) {
            // Get real and relative path for current file
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($rootPath) + 1);

            // Add current file to archive
            $zip->addFile($filePath, $relativePath);
        }
    }

    // Zip archive will be created only after closing object
    $zip->close();
    return $zipPath;
}

function directorysize($dir) {

    $command = "du -sb {$dir}";
    exec($command . " 2>&1", $output, $return_val);
    if ($return_val !== 0) {
        $size = 0;
        foreach (glob(rtrim($dir, '/') . '/*', GLOB_NOSORT) as $each) {
            $size += is_file($each) ? filesize($each) : directorysize($each);
        }
        return $size;
    } else {
        if (!empty($output[0])) {
            preg_match("/^([0-9]+).*/", $output[0], $matches);
        }
        if (!empty($matches[1])) {
            return intval($matches[1]);
        }

        return 0;
    }
}

function make_path($path) {
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
}

/**
 * Overwrite all advanced custom configurations with the $global configuration
 * @global type $global
 * @param type $advancedCustom
 */
function fixAdvancedCustom(&$advancedCustom) {
    global $global;
    foreach ($global as $key => $value) {
        if (isset($advancedCustom->$key)) {
            $advancedCustom->$key = $value;
        }
    }
}

function json_error() {
    switch (json_last_error()) {
        case JSON_ERROR_NONE:
            return ' - No errors';
            break;
        case JSON_ERROR_DEPTH:
            return ' - Maximum stack depth exceeded';
            break;
        case JSON_ERROR_STATE_MISMATCH:
            return ' - Underflow or the modes mismatch';
            break;
        case JSON_ERROR_CTRL_CHAR:
            return ' - Unexpected control character found';
            break;
        case JSON_ERROR_SYNTAX:
            return ' - Syntax error, malformed JSON';
            break;
        case JSON_ERROR_UTF8:
            return ' - Malformed UTF-8 characters, possibly incorrectly encoded';
            break;
        default:
            return ' - Unknown error';
            break;
    }
}

function rrmdir($dir) {
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (is_dir($dir . "/" . $object))
                    rrmdir($dir . "/" . $object);
                else
                    unlink($dir . "/" . $object);
            }
        }
        rmdir($dir);
    }
}

function xss_esc($text) {
    if (empty($text)) {
        return "";
    }
    $result = @htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    if (empty($result)) {
        $result = str_replace(array('"', "'", "\\"), array("", "", ""), strip_tags($text));
    }
    return $result;
}

function xss_esc_back($text) {
    $text = htmlspecialchars_decode($text, ENT_QUOTES);
    $text = str_replace(array('&amp;', '&#039;', "#039;"), array(" ", "`", "`"), $text);
    return $text;
}

function remove_utf8_bom($text) {
    if (empty($text)) {
        return "";
    }
    if (strlen($text) > 1000000) {
        return $text;
    }
    $bom = pack('H*', 'EFBBBF');
    $text = preg_replace("/^$bom/", '', $text);
    return $text;
}

function getSessionMD5() {
    global $global;
    return md5($global['webSiteRootURL'] . $global['systemRootPath']);
}

function getSessionId() {
    global $global;
    $obj = new stdClass();
    $obj->md5 = getSessionMD5();
    $obj->uniqueId = uniqid();
    return base64_encode(json_encode($obj));
}

function validateSessionId($PHPSESSID) {
    $json = base64_decode($PHPSESSID);
    $obj = json_decode($json);
    if (is_object($obj) && $obj->md5 == getSessionMD5()) {
        return true;
    }
    return false;
}

function recreateSessionIdIfNotValid() {
    $PHPSESSID = session_id();
    if (!validateSessionId($PHPSESSID)) {
        session_id(getSessionId());
    }
}

function _session_id($PHPSESSID) {
    if (validateSessionId($PHPSESSID)) {
        session_id($PHPSESSID);
    } else {
        recreateSessionIdIfNotValid();
    }
}

function _session_start(Array $options = array()) {
    global $global;
    try {
        if (session_status() == PHP_SESSION_NONE) {
            $md5 = getSessionMD5();
            if (!empty($_REQUEST['PHPSESSID'])) {
                //_session_id($_REQUEST['PHPSESSID']);
                session_id($_REQUEST['PHPSESSID']);
            } else {
                $_GET['PHPSESSID'] = "";
            }
            //recreateSessionIdIfNotValid();
            session_name("encoder{$md5}");
            return session_start($options);
        }
    } catch (Exception $exc) {
        _error_log("_session_start: " . $exc->getTraceAsString());
        return false;
    }
}

function getFileInfo($file) {
    if (empty($file) || !file_exists($file)) {
        return false;
    }
    $obj = new stdClass();
    if (is_dir($file)) {
        $obj->extension = "HLS";
        $obj->resolution = "Adaptive";
        $obj->size = directorysize($file);
    } else {
        $path_parts = pathinfo($file);
        $obj->extension = $path_parts['extension'];
        if ($obj->extension === "zip") {
            $obj->extension = "HLS";
            $obj->resolution = "Compressed";
        } else {
            preg_match("/([^_]{0,4})\.{$obj->extension}$/", $path_parts['basename'], $matches);
            $obj->resolution = @$matches[1];
        }
        $obj->size = filesize($file);
    }
    $obj->humansize = humanFileSize($obj->size);
    $obj->text = strtoupper($obj->extension) . " {$obj->resolution}: {$obj->humansize}";

    return $obj;
}

function getPHPSessionIDURL() {
    if (!empty($_GET['PHPSESSID'])) {
        $p = $_GET['PHPSESSID'];
    } else {
        $p = session_id();
    }
    return "PHPSESSID={$p}";
}

function isSameDomain($url1, $url2) {
    if (empty($url1) || empty($url2)) {
        return false;
    }
    return (get_domain($url1) === get_domain($url2));
}

function get_domain($url) {
    $pieces = parse_url($url);
    $domain = isset($pieces['host']) ? $pieces['host'] : '';
    if (empty($domain)) {
        return false;
    }
    if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $domain, $regs)) {
        return rtrim($regs['domain'], '/');
    } else {
        $isIp = (bool) ip2long($pieces['host']);
        if ($isIp) {
            return $pieces['host'];
        }
    }
    return false;
}

function isPIDRunning($pid) {
    if ($pid < 1) {
        return false;
    }
    return file_exists("/proc/$pid");
}

function execAsync($command) {
    // If windows, else
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        //$pid = system($command . " > NUL");
        pclose($pid = popen("start /B ". $command, "r")); 
    } else {
        $pid = exec($command . " > /dev/null 2>&1 & echo $!; ");
    }
    return $pid;
}

function execRun() {
    global $global;
    $php = getPHP() . " -f";
    $cmd = "{$php} {$global['systemRootPath']}view/run.php";
    return execAsync($cmd);
}

function getPHP() {
    global $global;
    if (!empty($global['php'])) {
        $php = $global['php'];
        if (file_exists($php)) {
            return $php;
        }
    }
    $php = PHP_BINDIR . "/php";
    if (file_exists($php)) {
        return $php;
    }
    return "php";
}
