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

function url_get_contents($Url, $ctx = "") {
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
    
    if (ini_get('allow_url_fopen')) {
        try {
            fetch_http_file_contents($Url);
        } catch (ErrorException $e) {
            try {
                $tmp = @file_get_contents($Url, false, $context);
                if ($tmp != false) {
                    return $tmp;
                }
            } catch (ErrorException $e) {
                error_log("Error on get Content");
            }
        }
    } else if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $Url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }
    
    return @file_get_contents($Url, false, $context);
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
    $minutes = intval(($durationParts[0]) * 60) + intval($durationParts[1]);
    return intval($durationParts[2]) + ($minutes * 60);
}

/**
 * 
 * @global type $global
 * @param type $mail
 * call it before send mail to let YouPHPTube decide the method
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
    $json_file = file_get_contents(Login::getStreamerURL() . "plugin/CustomizeAdvanced/advancedCustom.json.php");
    // convert the string to a json object
    $advancedCustom = json_decode($json_file);
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

function decideFormatOrder() {
    if(!empty($_GET['webm']) && empty($_POST['webm'])){
        $_POST['webm'] = $_GET['webm'];
    }
    if (empty($_POST['webm']) || $_POST['webm'] === 'false') {
        // mp4 only
        if (
                !empty($_POST['inputLow']) && $_POST['inputLow'] !== 'false' &&
                !empty($_POST['inputSD']) && $_POST['inputSD'] !== 'false' &&
                !empty($_POST['inputHD']) && $_POST['inputHD'] !== 'false'
        ) { // all resolutions
            error_log("MP4 All");
            return (80);
        } else if (
                !empty($_POST['inputLow']) && $_POST['inputLow'] !== 'false' &&
                !empty($_POST['inputHD']) && $_POST['inputHD'] !== 'false'
        ) {
            error_log("MP4 Low - HD");
            return (79);
        } else if (
                !empty($_POST['inputSD']) && $_POST['inputSD'] !== 'false' &&
                !empty($_POST['inputHD']) && $_POST['inputHD'] !== 'false'
        ) {
            error_log("MP4 SD - HD");
            return (78);
        } else if (
                !empty($_POST['inputLow']) && $_POST['inputLow'] !== 'false' &&
                !empty($_POST['inputSD']) && $_POST['inputSD'] !== 'false'
        ) {
            error_log("MP4 Low SD");
            return (77);
        } else if (
                !empty($_POST['inputHD']) && $_POST['inputHD'] !== 'false'
        ) {
            error_log("MP4 HD");
            return (76);
        } else if (
                !empty($_POST['inputSD']) && $_POST['inputSD'] !== 'false'
        ) {
            error_log("MP4 SD");
            return (75);
        } else if (
                !empty($_POST['inputLow']) && $_POST['inputLow'] !== 'false'
        ) {
            error_log("MP4 LOW");
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
        }else {
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
    $streamerEncrypt = json_decode(url_get_contents("{$streamerURL}objects/encryptPass.json.php?pass=". urlencode($password)));
    return $streamerEncrypt->encryptedPassword;
}