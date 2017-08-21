<?php

require_once $global['systemRootPath'] . 'objects/Format.php';
require_once $global['systemRootPath'] . 'objects/Login.php';
require_once $global['systemRootPath'] . 'objects/Streamer.php';

class Encoder extends Object {

    protected $id, $fileURI, $filename, $status, $status_obs, $return_vars, $priority, $created, $modified, $formats_id, $title, $videoDownloadedLink, $downloadedFileName, $streamers_id;

    protected static function getSearchFieldsNames() {
        return array('filename');
    }

    protected static function getTableName() {
        return 'encoder_queue';
    }

    function save() {
        global $global;
        if (empty($this->id)) {
            $this->setStatus("queue");
        }
        $this->setTitle($global['mysqli']->real_escape_string($this->getTitle()));
        $this->setStatus_obs($global['mysqli']->real_escape_string($this->getStatus_obs()));
        return parent::save();
    }

    static function getAll($onlyMine = false) {
        global $global;
        $sql = "SELECT * FROM  " . static::getTableName() . " WHERE 1=1 ";
        if ($onlyMine && !Login::isAdmin()) {
            $sql .= " AND streamers_id = ".Login::getStreamerId()." ";
        }
        $sql .= self::getSqlFromPost();
        
        $global['lastQuery'] = $sql;
        $res = $global['mysqli']->query($sql);
        $rows = array();
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $rows[] = $row;
            }
        } else {
            die($sql . '\nError : (' . $global['mysqli']->errno . ') ' . $global['mysqli']->error);
        }
        return $rows;
    }
    
    
    static function getTotal($onlyMine = false) {
        //will receive 
        //current=1&rowCount=10&sort[sender]=asc&searchPhrase=
        global $global;
        $sql = "SELECT id FROM  ".static::getTableName()." WHERE 1=1  ";
        if ($onlyMine && !Login::isAdmin()) {
            $sql .= " AND streamers_id = ".Login::getStreamerId()." ";
        }
        $sql .= self::getSqlSearchFromPost();

        $global['lastQuery'] = $sql;
        $res = $global['mysqli']->query($sql);


        return $res->num_rows;
    }

    function getId() {
        return $this->id;
    }

    function getFileURI() {
        return $this->fileURI;
    }

    function getFilename() {
        return $this->filename;
    }

    function getStatus() {
        return $this->status;
    }

    function getStatus_obs() {
        return $this->status_obs;
    }

    function getReturn_vars() {
        return $this->return_vars;
    }

    function getPriority() {
        return intval($this->priority);
    }

    function getCreated() {
        return $this->created;
    }

    function getModified() {
        return $this->modified;
    }

    function getFormats_id() {
        return $this->formats_id;
    }

    function setFileURI($fileURI) {
        $this->fileURI = $fileURI;
    }

    function setFilename($filename) {
        $this->filename = $filename;
    }

    function setStatus($status) {
        $this->status = $status;
    }

    function setStatus_obs($status_obs) {
        $this->status_obs = $status_obs;
    }

    function setReturn_vars($return_vars) {
        $this->return_vars = $return_vars;
    }

    function setPriority($priority) {
        $this->priority = intval($priority);
    }

    function setCreated($created) {
        $this->created = $created;
    }

    function setModified($modified) {
        $this->modified = $modified;
    }

    function getTitle() {
        return $this->title;
    }

    function setTitle($title) {
        $this->title = $title;
    }

    function getVideoDownloadedLink() {
        return $this->videoDownloadedLink;
    }

    function setVideoDownloadedLink($videoDownloadedLink) {
        $this->videoDownloadedLink = $videoDownloadedLink;
    }

    function getDownloadedFileName() {
        return $this->downloadedFileName;
    }

    function setDownloadedFileName($downloadedFileName) {
        $this->downloadedFileName = $downloadedFileName;
    }

    function getStreamers_id() {
        return $this->streamers_id;
    }

    function setStreamers_id($streamers_id) {
        $this->streamers_id = $streamers_id;
    }

    function setFormats_id($formats_id) {
        if (!preg_match('/^[0-9]+$/', $formats_id)) {
            $formats_id = Format::createIfNotExists($formats_id);
        }
        $this->formats_id = $formats_id;
    }

    static function getNext() {
        global $global;
        $sql = "SELECT * FROM  " . static::getTableName() . " WHERE status = 'queue' ";
        $sql .= " ORDER BY priority ASC, id ASC LIMIT 1";

        $res = $global['mysqli']->query($sql);
        if ($res) {
            return $res->fetch_assoc();
        } else {
            die($sql . '\nError : (' . $global['mysqli']->errno . ') ' . $global['mysqli']->error);
        }
        return false;
    }

    static function downloadFile($queue_id) {
        global $global;
        $obj = new stdClass();
        $q = new Encoder($queue_id);
        $url = $q->getFileURI();
        $f = new Format($q->getFormats_id());
        $dstFilepath = $global['systemRootPath'] . "videos/";
        $filename = "{$queue_id}_tmpFile." . $f->getExtension_from();
        if (!is_dir($dstFilepath)) {
            mkdir($dstFilepath);
        }

        $obj->error = true;
        $obj->filename = $filename;
        $obj->pathFileName = $dstFilepath . $filename;

        $e = Encoder::getFromFileURI($url);
        if (!empty($e['downloadedFileName'])) {
            $obj->pathFileName = $e['downloadedFileName'];
            $q->setDownloadedFileName($obj->pathFileName);
            $q->save();
            $obj->error = false;
            return $obj;
        }
        // do not download the file twice
        if (!file_exists($obj->pathFileName)) {

            if (!empty($q->getVideoDownloadedLink())) {
                $response = static::getYoutubeDl($q->getVideoDownloadedLink(), $queue_id);
            } else {
                $response = static::getVideoFile($url, $queue_id);
            }
            if ($response) {
                if (file_put_contents($obj->pathFileName, $response)) {
                    $q->setDownloadedFileName($obj->pathFileName);
                    $q->save();
                    $obj->error = false;
                } else {
                    $obj->msg = "Could not save file {$url} in {$dstFilepath}{$filename}";
                }
            } else {
                $obj->msg = "Could not get file {$url}";
            }
        } else {
            $obj->error = false;
        }
        return $obj;
    }

    static function getYoutubeDl($videoURL, $queue_id) {
        global $global;
        $tmpfname = tempnam(sys_get_temp_dir(), 'youtubeDl');
        //$cmd = "youtube-dl -o {$tmpfname}.mp4 -f 'bestvideo[ext=mp4]+bestaudio[ext=m4a]/mp4' {$videoURL}";
        $cmd = "youtube-dl -k -o {$tmpfname}.mp4 {$videoURL}";
        //echo "\n**Trying Youtube DL **".$cmd;
        error_log("Getting from Youtube DL {$cmd}");
        exec($cmd . "  1> {$global['systemRootPath']}videos/{$queue_id}_tmpFile_downloadProgress.txt  2>&1", $output, $return_val);
        if ($return_val !== 0) {
            //echo "\n**ERROR Youtube DL **".$code . "\n" . print_r($output, true);
            error_log($code . "\n" . print_r($output, true));
            return false;
        } else {
            $file = $tmpfname . ".mp4";
            if (!file_exists($file)) {
                $dl = static::getYoutubeDlProgress($queue_id);
                $file = $dl->filename;
            }

            return file_get_contents($file);
        }
    }

    static function getYoutubeDlProgress($queue_id) {
        global $global;
        $obj = new stdClass();
        $obj->filename = "";
        $obj->progress = 0;
        $file = "{$global['systemRootPath']}videos/{$queue_id}_tmpFile_downloadProgress.txt";
        if (!file_exists($file)) {
            return $obj;
        }
        $text = file_get_contents($file);
        preg_match('/Merging formats into "([\/a-z0-9._]+)"/i', $text, $matches);
        if (!empty($matches[1])) {
            $obj->filename = $matches[1];
        }
        preg_match_all('/\[download\] +([0-9.]+)% of/', $text, $matches, PREG_SET_ORDER);
//$m = end($matches);
//$obj->progress = empty($m[1]) ? 0 : intval($m[1]);
        foreach ($matches as $m) {
            $obj->progress = empty($m[1]) ? 0 : intval($m[1]);
            if ($obj->progress == 100) {
                break;
            }
        }
        return $obj;
    }

    static function getVideoFile($videoURL, $queue_id) {
        global $global;
        $arrContextOptions = array(
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
            ),
        );
        $global['queue_id'] = $queue_id;
        $ctx = stream_context_create($arrContextOptions);
        stream_context_set_params($ctx, array("notification" => "stream_notification_callback"));
        error_log("Getting Video File {$videoURL}");
        return file_get_contents($videoURL, false, $ctx);
    }

    static function isEncoding() {
        global $global;
        $sql = "SELECT f.*, e.* FROM  " . static::getTableName() . " e "
                . " LEFT JOIN formats f ON f.id = formats_id WHERE status = 'encoding' OR  status = 'downloading' LIMIT 1 ";

        $res = $global['mysqli']->query($sql);

        $sql .= " ORDER BY priority ASC, e.id ASC LIMIT 1";

        if ($res) {
            $result = $res->fetch_assoc();
            if (!empty($result)) {
                $result['return_vars'] = json_decode($result['return_vars']);
                $s = new Streamer($result['streamers_id']);
                $result['streamer_site'] = $s->getSiteURL();
                $result['streamer_priority'] = $s->getPriority();
                return $result;
            } else {
                return false;
            }
        } else {
            die($sql . '\nError : (' . $global['mysqli']->errno . ') ' . $global['mysqli']->error);
        }
        return false;
    }

    static function getAllQueue() {
        global $global;
        $sql = "SELECT f.*, e.* FROM  " . static::getTableName() . " e "
                . " LEFT JOIN formats f ON f.id = formats_id WHERE (status = 'encoding' OR  status = 'downloading' OR status = 'queue' OR status = 'error') ";

        $sql .= " ORDER BY priority ASC, e.id ASC ";
        $res = $global['mysqli']->query($sql);
        $rows = array();
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $row['return_vars'] = json_decode($row['return_vars']);
                $s = new Streamer($row['streamers_id']);
                $row['streamer_site'] = $s->getSiteURL();
                $row['streamer_priority'] = $s->getPriority();
                $rows[] = $row;
            }
        } else {
            die($sql . '\nError : (' . $global['mysqli']->errno . ') ' . $global['mysqli']->error);
        }
        return $rows;
    }

    static function getFromFilename($filename) {
        global $global;
        $sql = "SELECT * FROM  " . static::getTableName() . " WHERE filename = '$filename' LIMIT 1 ";

        $res = $global['mysqli']->query($sql);
        if ($res) {
            return $res->fetch_assoc();
        } else {
            die($sql . '\nError : (' . $global['mysqli']->errno . ') ' . $global['mysqli']->error);
        }
        return false;
    }

    static function getFromFileURI($fileURI) {
        global $global;
        $sql = "SELECT * FROM  " . static::getTableName() . " WHERE fileURI = '$fileURI' LIMIT 1 ";

        $res = $global['mysqli']->query($sql);
        if ($res) {
            return $res->fetch_assoc();
        } else {
            die($sql . '\nError : (' . $global['mysqli']->errno . ') ' . $global['mysqli']->error);
        }
        return false;
    }

    static function run() {
        $obj = new stdClass();
        $obj->error = true;
        // check if is encoding something
        $row = static::isEncoding();
        if (empty($row['id'])) {
            $row = static::getNext();
            if (empty($row)) {
                $obj->msg = "There is no file on queue";
            } else {
                $encoder = new Encoder($row['id']);
                $return_vars = json_decode($encoder->getReturn_vars());
                $encoder->setStatus("downloading");
                $encoder->setStatus_obs("Start in " . date("Y-m-d H:i:s"));
                $encoder->save();
                $objFile = static::downloadFile($encoder->getId());
                if ($objFile->error) {
                    $obj->msg = $objFile->msg;
                } else {
                    $encoder->setStatus("encoding");
                    $encoder->save();
                    // get the encode code and convert it
                    $code = new Format($encoder->getFormats_id());
                    $resp = $code->run($objFile->pathFileName, $encoder->getId());
                    if ($resp->error) {
                        $obj->msg = "Execute code error " . print_r($resp->msg, true) . " \n Code: {$resp->code}";
                        error_log(print_r($obj, true));
                        $encoder->setStatus("error");
                        $encoder->setStatus_obs($obj->msg);
                        $encoder->save();
                    } else {
                        $obj->error = false;
                        $obj->msg = $resp->code;
                        $videos_id = 0;
                        if (!empty($return_vars->videos_id)) {
                            $videos_id = $return_vars->videos_id;
                        }
                        // notify YouPHPTube it is done
                        $response = $encoder->send();
                        if (!$response->error) {
                            // update queue status
                            $encoder->setStatus("done");
                        } else {
                            $encoder->setStatus("error");
                            $encoder->setStatus_obs("Send message error = " . $response->msg);
                        }
                        $encoder->save();
                        // TODO remove file
                        // run again
                        static::run();
                    }
                }
            }
        } else {
            $obj->msg = "The file [{$row['id']}] {$row['filename']} is encoding";
        }

        return $obj;
    }

    function send() {
        global $global;
        $f = new Format($this->getFormats_id());
        $return_vars = json_decode($this->getReturn_vars());
        $videos_id = (!empty($return_vars->videos_id) ? $return_vars->videos_id : 0);
        $return = new stdClass();
        $return->sends = array();
        $return->formats_id = $this->getFormats_id();
        $return->error = false;
        // if is a bulk encode id >= 7 send multiple files 
        if ($f->getId() >= 7) {
            $codes = explode("-", $f->getCode());
            foreach ($codes as $value) {
                $f = new Format($value);
                $file = $global['systemRootPath'] . "videos/{$this->id}_tmpFile_converted." . $f->getExtension();
                $format = $f->getExtension();
                $r = static::sendFile($file, $videos_id, $format, $this);
                if (!empty($r->response->video_id)) {
                    $videos_id = $r->response->video_id;
                }
                if ($r->error) {
                    $return->error = true;
                    $return->msg = $r->msg;
                }
                $return->sends[] = $r;
            }
        } else {
            $file = $global['systemRootPath'] . "videos/{$this->id}_tmpFile_converted." . $f->getExtension();
            $format = $f->getExtension();
            $r = static::sendFile($file, $videos_id, $format, $this);
            if ($r->error) {
                $return->error = true;
                $return->msg = $r->msg;
            }
            $return->sends[] = $r;
        }
        return $return;
    }

    static function sendFile($file, $videos_id, $format, $encoder = null) {
        global $global;

        $obj = new stdClass();
        $obj->error = true;
        $obj->format = $format;
        $obj->file = $file;

        $duration = static::getDurationFromFile($file);
        $title = $encoder->getTitle();
        $videoDownloadedLink = $encoder->getVideoDownloadedLink();
        $streamers_id = $encoder->getStreamers_id();
        $s = new Streamer($streamers_id);
        $youPHPTubeURL = $s->getSiteURL();
        $user = $s->getUser();
        $pass = $s->getPass();

        $target = $youPHPTubeURL . "youPHPTubeEncoder.json";
        $obj->target = $target;
        error_log("YouPHPTube-Encoder sending file to {$target}");
        error_log("YouPHPTube-Encoder reading file from {$file}");
        $postFields = array(
            'duration' => $duration,
            'title' => $title,
            'videos_id' => $videos_id,
            'format' => $format,
            'videoDownloadedLink' => $videoDownloadedLink,
            'user' => $user,
            'password' => $pass
        );

        if (!empty($file)) {
            $postFields['video'] = new CURLFile($file);
            if ($format == "mp4") {
                $postFields['image'] = new CURLFile(static::getImage($file, intval(static::parseDurationToSeconds($duration) / 2)));
                $postFields['gifimage'] = new CURLFile(static::getGifImage($file, intval(static::parseDurationToSeconds($duration) / 2), 3));
            }
        }
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $target);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: multipart/form-data'));
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_SAFE_UPLOAD, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        $r = curl_exec($curl);
        error_log("YouPHPTube-Streamer answer {$r}");
        $obj->response_raw = $r;
        $obj->response = json_decode($r);
        if ($errno = curl_errno($curl)) {
            $error_message = curl_strerror($errno);
            //echo "cURL error ({$errno}):\n {$error_message}";
            $obj->msg = "cURL error ({$errno}):\n {$error_message} \n {$file} \n {$target}";
        } else {
            $obj->error = false;
        }
        curl_close($curl);
        //var_dump($obj);exit;
        return $obj;
    }

    static function getVideoConversionStatus($encoder_queue_id) {
        global $global;
        $progressFilename = "{$global['systemRootPath']}videos/{$encoder_queue_id}_tmpFile_progress.txt";
        $content = @file_get_contents($progressFilename);
        if (!empty($content)) {
            return self::parseProgress($content);
        }

        return false;
    }

    static private function parseProgress($content) {
        //get duration of source

        $obj = new stdClass();

        $obj->duration = 0;
        $obj->currentTime = 0;
        $obj->progress = 0;
        $obj->from = '';
        $obj->to = '';
        //var_dump($content);exit;
        preg_match("/Duration: (.*?), start:/", $content, $matches);
        if (!empty($matches[1])) {

            $rawDuration = $matches[1];

            //rawDuration is in 00:00:00.00 format. This converts it to seconds.
            $ar = array_reverse(explode(":", $rawDuration));
            $duration = floatval($ar[0]);
            if (!empty($ar[1])) {
                $duration += intval($ar[1]) * 60;
            }
            if (!empty($ar[2])) {
                $duration += intval($ar[2]) * 60 * 60;
            }

            //get the time in the file that is already encoded
            preg_match_all("/time=(.*?) bitrate/", $content, $matches);

            $rawTime = array_pop($matches);

            //this is needed if there is more than one match
            if (is_array($rawTime)) {
                $rawTime = array_pop($rawTime);
            }

            //rawTime is in 00:00:00.00 format. This converts it to seconds.
            $ar = array_reverse(explode(":", $rawTime));
            $time = floatval($ar[0]);
            if (!empty($ar[1])) {
                $time += intval($ar[1]) * 60;
            }
            if (!empty($ar[2])) {
                $time += intval($ar[2]) * 60 * 60;
            }

            if (!empty($duration)) {
                //calculate the progress
                $progress = round(($time / $duration) * 100);
            } else {
                $progress = 'undefined';
            }
            $obj->duration = $duration;
            $obj->currentTime = $time;
            $obj->progress = $progress;
        }

        //Input #0, mov,mp4,m4a,3gp,3g2,mj2, from '/home/daniel/Dropbox/htdocs/YouPHPTube-Encoder/videos/284_tmpFile_converted.mp4':
        preg_match("/Input[a-z0-9 #,]+from '(.*_tmpFile_converted.*)'/", $content, $matches);
        if (!empty($matches[1])) {
            $path_parts = pathinfo($matches[1]);
            $obj->from = $path_parts['extension'];
        }

        //Output #0, webm, to '/home/daniel/Dropbox/htdocs/YouPHPTube-Encoder/videos/284_tmpFile_converted.webm':preg_match("/Input[a-z0-9 #,]+from '(.*_tmpFile_converted.*)'/", $content, $matches);
        preg_match("/Output[a-z0-9 #,]+to '(.*_tmpFile_converted.*)'/", $content, $matches);
        if (!empty($matches[1])) {
            $path_parts = pathinfo($matches[1]);
            $obj->to = $path_parts['extension'];
        }

        return $obj;
    }

    static function getDurationFromFile($file) {
        global $config;
        // get movie duration HOURS:MM:SS.MICROSECONDS
        if (!file_exists($file)) {
            error_log('{"status":"error", "msg":"getDurationFromFile ERROR, File (' . $file . ') Not Found"}');
            return "EE:EE:EE";
        }
        //$cmd = 'ffprobe -i ' . $file . ' -sexagesimal -show_entries  format=duration -v quiet -of csv="p=0"';
        eval('$cmd="ffprobe -i {$file} -sexagesimal -show_entries  format=duration -v quiet -of csv=\'p=0\'";');
        exec($cmd . ' 2>&1', $output, $return_val);
        if ($return_val !== 0) {
            error_log('{"status":"error", "msg":' . json_encode($output) . ' ,"return_val":' . json_encode($return_val) . ', "where":"getDuration", "cmd":"' . $cmd . '"}');
            // fix ffprobe
            $duration = "EE:EE:EE";
        } else {
            preg_match("/([0-9]+:[0-9]+:[0-9]{2})/", $output[0], $match);
            if (!empty($match[1])) {
                $duration = $match[1];
            } else {
                error_log('{"status":"error", "msg":' . json_encode($output) . ' ,"match_not_found":' . json_encode($match) . ' ,"return_val":' . json_encode($return_val) . ', "where":"getDuration", "cmd":"' . $cmd . '"}');
                $duration = "EE:EE:EE";
            }
        }
        error_log("Duration founded: {$duration}");
        return $duration;
    }

    static function getImage($pathFileName, $seconds = 5) {
        global $global;
        $destinationFile = "{$pathFileName}.jpg";
        // do not encode again
        if (file_exists($destinationFile)) {
            return $destinationFile;
        }
        //eval('$ffmpeg ="ffmpeg -ss {$seconds} -i {$pathFileName} -qscale:v 2 -vframes 1 -y {$destinationFile}";');
        $duration = static::parseSecondsToDuration($seconds);
        eval('$ffmpeg ="ffmpeg -i {$pathFileName} -ss {$duration} -vframes 1 -y {$destinationFile}";');
        exec($ffmpeg . " < /dev/null 2>&1", $output, $return_val);
        if ($return_val !== 0) {
            error_log("Create Image error: {$ffmpeg}");
            return $global['systemRootPath'] . "view/img/notfound.jpg";
        } else {
            return $destinationFile;
        }
    }

    static function getGifImage($pathFileName, $seconds = 5, $howLong = 3) {
        global $global;
        $destinationFile = "{$pathFileName}.gif";
        // do not encode again
        if (file_exists($destinationFile)) {
            return $destinationFile;
        }

        $duration = static::parseSecondsToDuration($seconds);

        //Generate a palette:
        eval('$ffmpeg ="ffmpeg -y -ss {$duration} -t {$howLong} -i {$pathFileName} -vf fps=10,scale=320:-1:flags=lanczos,palettegen {$pathFileName}palette.png";');
        exec($ffmpeg . " < /dev/null 2>&1", $output, $return_val);
        if ($return_val !== 0) {
            error_log("Create Pallete Gif Image error: {$ffmpeg}");
            return $global['systemRootPath'] . "view/img/notfound.gif";
        } else {
            eval('$ffmpeg ="ffmpeg -ss {$duration} -t {$howLong} -i {$pathFileName} -i {$pathFileName}palette.png -filter_complex \"fps=10,scale=320:-1:flags=lanczos[x];[x][1:v]paletteuse\" {$destinationFile}";');
            exec($ffmpeg . " < /dev/null 2>&1", $output, $return_val);
            if ($return_val !== 0) {
                error_log("Create Gif Image error: {$ffmpeg}");
                return $global['systemRootPath'] . "view/img/notfound.gif";
            } else {
                return $destinationFile;
            }
        }
    }

    function delete() {
        global $global;
        exec("rm {$global['systemRootPath']}videos/{$this->id}_tmpFile* < /dev/null 2>&1", $output, $return_val);
        if ($return_val !== 0) {
            error_log("Could not remove files from disk {$this->id}_tmpFile*");
        }
        return parent::delete();
    }

    static function checkList() {
        // is videos writeble
    }

    static function parseDurationToSeconds($str) {
        $durationParts = explode(":", $str);
        if (empty($durationParts[1]))
            return 0;
        $minutes = intval(($durationParts[0]) * 60) + intval($durationParts[1]);
        return intval($durationParts[2]) + ($minutes * 60);
    }

    static function parseSecondsToDuration($int) {
        $seconds = $int % 60;
        if ($seconds < 10) {
            $seconds = "0{$seconds}";
        }
        $minutes = floor($int / 60);
        if ($minutes < 10) {
            $minutes = "0{$minutes}";
        }
        $hours = floor($int / (60 * 60));
        if ($hours < 10) {
            $hours = "0{$hours}";
        }

        return "{$hours}:{$minutes}:{$seconds}";
    }

}

function stream_notification_callback($notification_code, $severity, $message, $message_code, $bytes_transferred, $bytes_max) {
    global $global;
    static $filesize = null;
    switch ($notification_code) {
        case STREAM_NOTIFY_RESOLVE:
        case STREAM_NOTIFY_AUTH_REQUIRED:
        case STREAM_NOTIFY_COMPLETED:
        case STREAM_NOTIFY_FAILURE:
        case STREAM_NOTIFY_AUTH_RESULT:
            //var_dump($notification_code, $severity, $message, $message_code, $bytes_transferred, $bytes_max);
            //$txt = "Found the mime-type: ". $message;
            /* Ignore */
            break;
        case STREAM_NOTIFY_REDIRECTED:
            //echo "Being redirected to: ", $message;
            $txt = "Being redirected to: " . $message;
            break;

        case STREAM_NOTIFY_CONNECT:
            //echo "Connected...";
            $txt = "Connected...";
            break;

        case STREAM_NOTIFY_FILE_SIZE_IS:
            //echo "Got the filesize: ", $bytes_max;
            $txt = "Got the filesize: " . $bytes_max;
            break;

        case STREAM_NOTIFY_MIME_TYPE_IS:
            //echo "Found the mime-type: ", $message;
            $txt = "Found the mime-type: " . $message;
            break;

        case STREAM_NOTIFY_PROGRESS:
            //echo "Made some progress, downloaded ", $bytes_transferred, " so far";
            $p = number_format(($bytes_transferred / $bytes_max) * 100, 2);
            $txt = "[download]  {$p}% of {$bytes_max}Bytes";
            break;
    }
    $myfile = file_put_contents($global['systemRootPath'] . 'videos/' . $global['queue_id'] . '_tmpFile_downloadProgress.txt', $txt . PHP_EOL, FILE_APPEND | LOCK_EX);
}
