<?php

global $sentImage;
$sentImage = array();
require_once $global['systemRootPath'] . 'objects/Format.php';
require_once $global['systemRootPath'] . 'objects/Login.php';
require_once $global['systemRootPath'] . 'objects/Streamer.php';
require_once $global['systemRootPath'] . 'objects/functions.php';

class Encoder extends ObjectYPT
{

    protected $id, $fileURI, $filename, $status, $status_obs, $return_vars, $priority, $created, $modified, $formats_id, $title, $videoDownloadedLink, $downloadedFileName, $streamers_id;

    public static function getSearchFieldsNames() {
        return array('filename');
    }

    public static function getTableName() {
        return 'encoder_queue';
    }

    public function save() {
        global $global;
        if (empty($this->id)) {
            $this->setStatus("queue");
        }
        $this->setTitle($global['mysqli']->real_escape_string($this->getTitle()));
        $this->setStatus_obs($global['mysqli']->real_escape_string($this->getStatus_obs()));
        return parent::save();
    }

    public static function getAll($onlyMine = false) {
        global $global;
        $sql = "SELECT * FROM  " . static::getTableName() . " WHERE 1=1 ";
        if ($onlyMine && !Login::isAdmin()) {
            $sql .= " AND streamers_id = " . Login::getStreamerId() . " ";
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

    public static function getTotal($onlyMine = false) {
        //will receive
        //current=1&rowCount=10&sort[sender]=asc&searchPhrase=
        global $global;
        $sql = "SELECT id FROM  " . static::getTableName() . " WHERE 1=1  ";
        if ($onlyMine && !Login::isAdmin()) {
            $sql .= " AND streamers_id = " . Login::getStreamerId() . " ";
        }
        $sql .= self::getSqlSearchFromPost();

        $global['lastQuery'] = $sql;
        $res = $global['mysqli']->query($sql);

        return $res->num_rows;
    }

    public function getId() {
        return $this->id;
    }

    public function getFileURI() {
        return $this->fileURI;
    }

    public function getFilename() {
        return $this->filename;
    }

    public function getStatus() {
        return $this->status;
    }

    public function getStatus_obs() {
        return $this->status_obs;
    }

    public function getReturn_vars() {
        return $this->return_vars;
    }

    public function getPriority() {
        return intval($this->priority);
    }

    public function getCreated() {
        return $this->created;
    }

    public function getModified() {
        return $this->modified;
    }

    public function getFormats_id() {
        return $this->formats_id;
    }

    public function setFileURI($fileURI) {
        $this->fileURI = $fileURI;
    }

    public function setFilename($filename) {
        $this->filename = $filename;
    }

    public function setStatus($status) {
        $this->status = $status;
    }

    public function setStatus_obs($status_obs) {
        $this->status_obs = $status_obs;
    }

    public function setReturn_vars($return_vars) {
        $this->return_vars = $return_vars;
    }

    public function setPriority($priority) {
        $this->priority = intval($priority);
    }

    public function setCreated($created) {
        $this->created = $created;
    }

    public function setModified($modified) {
        $this->modified = $modified;
    }

    public function getTitle() {
        return $this->title;
    }

    public function setTitle($title) {
        $this->title = $title;
    }

    public function getVideoDownloadedLink() {
        return $this->videoDownloadedLink;
    }

    public function setVideoDownloadedLink($videoDownloadedLink) {
        $this->videoDownloadedLink = $videoDownloadedLink;
    }

    public function getDownloadedFileName() {
        return $this->downloadedFileName;
    }

    public function setDownloadedFileName($downloadedFileName) {
        $this->downloadedFileName = $downloadedFileName;
    }

    public function getStreamers_id() {
        return $this->streamers_id;
    }

    public function setStreamers_id($streamers_id) {
        $this->streamers_id = $streamers_id;
    }

    public function setFormats_id($formats_id) {
        if (!preg_match('/^[0-9]+$/', $formats_id)) {
            $formats_id = Format::createIfNotExists($formats_id);
        }
        $this->formats_id = $formats_id;
    }

    public function setFormats_idFromOrder($order) {
        $o = new Format(0);
        $o->loadFromOrder($order);
        $this->setFormats_id($o->getId());
    }

    public static function getNext() {
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

    public static function downloadFile($queue_id) {
        global $global;
        $obj = new stdClass();
        $q = new Encoder($queue_id);
        $url = $q->getFileURI();
        error_log("url $url");
        $f = new Format($q->getFormats_id());
        $destinationPath = $global['systemRootPath'] . "videos/";
        $filename = "{$queue_id}_tmpFile." . $f->getExtension_from();
        if (!is_dir($destinationPath)) {
            mkdir($destinationPath);
        }

        $obj->error = true;
        $obj->filename = $filename;
        $obj->pathFileName = $destinationPath . $filename;

        $e = Encoder::getFromFileURI($url);
        if (!empty($e['downloadedFileName'])) {
            $obj->pathFileName = $e['downloadedFileName'];
            $q->setDownloadedFileName($obj->pathFileName);
            $q->save();
            $obj->error = false;
            return $obj;
        }

        if (!empty($q->getVideoDownloadedLink())) {
            //begin youtube-dl downloading and symlink it to the video temp file
            $response = static::getYoutubeDl($q->getVideoDownloadedLink(), $queue_id, $obj->pathFileName);
            $obj->error = false;
        } else {
            //symlink the downloaded file to the video temp file ($obj-pathFileName)
            if(strpos($url, "http") !== false) {
                //this file was uploaded "from file" and thus is in the videos directory
                $downloadedFile = substr($url, strrpos($url, '/') + 1);
                $downloadedFile = $destinationPath . $downloadedFile;
            } else {
                //this file was a "bulk encode" and thus is on a local directory
                $downloadedFile = $url;
            }
            
            $response = static::getVideoFile($url, $queue_id, $downloadedFile, $obj->pathFileName);
            $obj->error = false;
        }
        if ($obj->error == false) {
            $q->setDownloadedFileName($obj->pathFileName);
            $q->save();
        }
        if ($response) {
            $obj->msg = "Could not save file {$url} in {$destinationPath}{$filename}";
        }
        return $obj;
    }

    public static function getYoutubeDl($videoURL, $queue_id, $destinationFile) {
        global $global;
        $tmpfname = tempnam(sys_get_temp_dir(), 'youtubeDl');
        //$cmd = "youtube-dl -o {$tmpfname}.mp4 -f 'bestvideo[ext=mp4]+bestaudio[ext=m4a]/mp4' {$videoURL}";
        $cmd = self::getYouTubeDLCommand() . "  --force-ipv4 --no-check-certificate --no-playlist -k -o {$tmpfname}.mp4 -f 'mp4' \"{$videoURL}\"";
        //echo "\n**Trying Youtube DL **".$cmd;
        error_log("Getting from Youtube DL {$cmd}");
        exec($cmd . "  1> {$global['systemRootPath']}videos/{$queue_id}_tmpFile_downloadProgress.txt  2>&1", $output, $return_val);
        if ($return_val !== 0) {
            //echo "\n**ERROR Youtube DL **".$code . "\n" . print_r($output, true);
            error_log($cmd . "\n" . print_r($output, true));
            return false;
        } else {
            $file = $tmpfname . ".mp4";
            if (!file_exists($file)) {
                $dl = static::getYoutubeDlProgress($queue_id);
                $file = $dl->filename;
            }
            // instead of loading the whole file into memory to dump it into a new filename
            // the file is just symlinked
            symlink($file, $destinationFile);
            return url_set_file_context($file);
        }
    }

    public static function getYoutubeDlProgress($queue_id) {
        global $global;
        $obj = new stdClass();
        $obj->filename = "";
        $obj->progress = 0;
        $file = "{$global['systemRootPath']}videos/{$queue_id}_tmpFile_downloadProgress.txt";
        if (!file_exists($file)) {
            return $obj;
        }
        $text = url_get_contents($file);
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

    public static function getVideoFile($videoURL, $queue_id, $downloadedFile, $destinationFile) {
        // the file has already been downloaded
        // all that is needed to do is create a tempfile reference to the original
        symlink($downloadedFile, $destinationFile);
        global $global;
        $arrContextOptions = array(
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
            ),
        );
        $global['queue_id'] = $queue_id;
        $ctx = stream_context_create($arrContextOptions);
        //copied from stream_contenxt_set_params
        // the file is already 100% downloaded by now
        $txt = "[download]  100% of all Bytes";
        // save this progress file
        $myfile = file_put_contents($global['systemRootPath'] . 'videos/' . $global['queue_id'] . '_tmpFile_downloadProgress.txt', $txt . PHP_EOL, FILE_APPEND | LOCK_EX);
        //url_get_contents is what drives the red bar and causes the memory failure
        $return = url_set_file_context($videoURL, $ctx);
        if (!$return) {
            $fixedEncodedUrl = utf8_encode($videoURL);
            error_log("Try to get UTF8 URL {$fixedEncodedUrl}");
            $return = url_set_file_context($videoURL, $ctx);
            if (!$return) {
                $fixedEncodedUrl = utf8_decode($videoURL);
                error_log("Try to get UTF8 decode URL {$fixedEncodedUrl}");
                $return = url_set_file_context($videoURL, $ctx);
            }
        }
        return $return;
    }

    public static function isEncoding() {
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

    /*
    static function isTransferring() {
    global $global;
    $sql = "SELECT f.*, e.* FROM  " . static::getTableName() . " e "
    . " LEFT JOIN formats f ON f.id = formats_id WHERE status = 'transferring' ";

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
     *
     */

    public static function getAllQueue()
    {
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

    public static function getFromFilename($filename) {
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

    public static function getFromFileURI($fileURI) {
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

    public static function run() {
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
                    $encoder->setStatus("error");
                    $encoder->setStatus_obs("Could not download the file ");
                    $encoder->save();
                } else {
                    $encoder->setStatus("encoding");
                    $encoder->save();
                    self::sendImages($objFile->pathFileName, $return_vars->videos_id, $encoder);
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
                            $config = new Configuration();
                            if (!empty($config->getAutodelete())) {
                                $encoder->delete();
                            } else {
                                error_log("Autodelete Not active");
                            }
                            $encoder->notifyVideoIsDone();
                        } else {
                            $encoder->setStatus("error");
                            $encoder->setStatus_obs("Send message error = " . $response->msg);
                            $encoder->notifyVideoIsDone(1);
                        }
                        $encoder->save();
                        // TODO remove file
                        // run again
                    }
                }
                static::run();
            }
        } else {
            $obj->msg = "The file [{$row['id']}] {$row['filename']} is encoding";
        }
        return $obj;
    }

    private function notifyVideoIsDone($fail = 0) {
        global $global;
        $obj = new stdClass();
        $return_vars = json_decode($this->getReturn_vars());
        if (!empty($return_vars->videos_id)) {
            $videos_id = $return_vars->videos_id;
            $obj->error = true;

            $streamers_id = $this->getStreamers_id();
            $s = new Streamer($streamers_id);
            $user = $s->getUser();
            $pass = $s->getPass();

            $s = new Streamer($streamers_id);
            $youPHPTubeURL = $s->getSiteURL();

            $target = $youPHPTubeURL . "objects/youPHPTubeEncoderNotifyIsDone.json.php";
            $obj->target = $target;
            error_log("YouPHPTube-Encoder sending confirmation to {$target}");
            $postFields = array(
                'videos_id' => $videos_id,
                'user' => $user,
                'password' => $pass,
                'fail' => $fail
            );
            $obj->postFields = $postFields;

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $target);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            //curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: multipart/form-data'));
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_SAFE_UPLOAD, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $postFields);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
            $r = curl_exec($curl);
            error_log("YouPHPTube-Streamer confirmation answer {$r}");
            $obj->response_raw = $r;
            $obj->response = json_decode($r);
            if ($errno = curl_errno($curl)) {
                $error_message = curl_strerror($errno);
                //echo "cURL error ({$errno}):\n {$error_message}";
                $obj->msg = "cURL error ({$errno}):\n {$error_message} ";
            } else {
                $obj->error = false;
            }
            curl_close($curl);
        }

        error_log(json_encode($obj));
        return $obj;
    }

    private function multiResolutionSend($resolution, $format, $videos_id) {
        global $global;
        $file = $global['systemRootPath'] . "videos/{$this->id}_tmpFile_converted_{$resolution}.{$format}";
        $r = static::sendFile($file, $videos_id, $format, $this, $resolution);
        return $r;
    }

    public function verify() {
        $streamer = new Streamer($this->getStreamers_id());
        return $streamer->verify();
    }

    public function send() {
        global $global;
        $formatId = $this->getFormats_id();
        $f = new Format($formatId);
        $order_id = $f->getOrder();
        $return_vars = json_decode($this->getReturn_vars());
        $videos_id = (!empty($return_vars->videos_id) ? $return_vars->videos_id : 0);
        $return = new stdClass();
        $return->sends = array();
        $return->formats_id = $this->getFormats_id();
        $return->error = false;

        $this->setStatus("transferring");
        $this->save();

        if (in_array($order_id, $global['multiResolutionOrder'])) {
            if (in_array($order_id, $global['hasHDOrder'])) {
                $return->sends[] = $this->multiResolutionSend("HD", "mp4", $videos_id);
                if (in_array($order_id, $global['bothVideosOrder'])) { // make the webm too
                    $return->sends[] = $this->multiResolutionSend("HD", "webm", $videos_id);
                }
            }
            if (in_array($order_id, $global['hasSDOrder'])) {
                $return->sends[] = $this->multiResolutionSend("SD", "mp4", $videos_id);
                if (in_array($order_id, $global['bothVideosOrder'])) { // make the webm too
                    $return->sends[] = $this->multiResolutionSend("SD", "webm", $videos_id);
                }
            }
            if (in_array($order_id, $global['hasLowOrder'])) {
                $return->sends[] = $this->multiResolutionSend("Low", "mp4", $videos_id);
                if (in_array($order_id, $global['bothVideosOrder'])) { // make the webm too
                    $return->sends[] = $this->multiResolutionSend("Low", "webm", $videos_id);
                }
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
        $this->setStatus("done");
        // check if autodelete is enabled
        $config = new Configuration();
        if (!empty($config->getAutodelete())) {
            $this->delete();
        } else {
            error_log("Autodelete Not active");
        }
        $this->save();
        return $return;
    }

    public static function sendFile($file, $videos_id, $format, $encoder = null, $resolution = "") {
        global $global;
        global $sentImage;

        $obj = new stdClass();
        $obj->error = true;
        $obj->format = $format;
        $obj->file = $file;
        $obj->resolution = $resolution;
        $obj->videoDownloadedLink = $encoder->getVideoDownloadedLink();
        $duration = static::getDurationFromFile($file);
        if (empty($_POST['title'])) {
            $title = $encoder->getTitle();
        } else {
            $title = $_POST['title'];
        }
        if (empty($_POST['description'])) {
            if (!empty($obj->videoDownloadedLink)) {
                $description = $encoder->getDescriptionFromLink($obj->videoDownloadedLink);
            } else {
                $description = "";
            }
        } else {
            $description = $_POST['description'];
        }
        if (empty($_POST['categories_id'])) {
            $categories_id = 0;
        } else {
            $categories_id = $_POST['categories_id'];
        }

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
            'categories_id' => $categories_id,
            'format' => $format,
            'resolution' => $resolution,
            'videoDownloadedLink' => $obj->videoDownloadedLink,
            'description' => $description,
            'user' => $user,
            'password' => $pass,
        );
        $obj->postFields = $postFields;

        if (!empty($file)) {
            $postFields['video'] = new CURLFile($file);
            if ($format == "mp4" && !in_array($videos_id, $sentImage)) {
                // do not send image twice
                $sentImage[] = $videos_id;
                //$postFields['image'] = new CURLFile(static::getImage($file, intval(static::parseDurationToSeconds($duration) / 2)));
                //$postFields['gifimage'] = new CURLFile(static::getGifImage($file, intval(static::parseDurationToSeconds($duration) / 2), 3));
            }
            $obj->videoFileSize = humanFileSize(filesize($file));
        }
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $target);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: multipart/form-data'));
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_SAFE_UPLOAD, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        $r = curl_exec($curl);
        error_log("YouPHPTube-Streamer answer {$r}");
        $obj->postFields = count($postFields);
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
        error_log(json_encode($obj));
        //var_dump($obj);exit;
        return $obj;
    }

    public static function sendImages($file, $videos_id, $encoder) {
        global $global;

        $obj = new stdClass();
        $obj->error = true;
        $obj->file = $file;
        error_log("sendImages: Sending image to [$videos_id]");
        $duration = static::getDurationFromFile($file);
        $streamers_id = $encoder->getStreamers_id();
        $s = new Streamer($streamers_id);
        $youPHPTubeURL = $s->getSiteURL();
        $user = $s->getUser();
        $pass = $s->getPass();

        $target = $youPHPTubeURL . "objects/youPHPTubeEncoderReceiveImage.json.php";
        $obj->target = $target;
        error_log("sendImages: YouPHPTube-Encoder sending file to {$target}");
        error_log("sendImages: YouPHPTube-Encoder reading file from {$file}");
        $postFields = array(
            'duration' => $duration,
            'videos_id' => $videos_id,
            'user' => $user,
            'password' => $pass,
        );

        $obj->postFields = $postFields;

        if (!empty($file)) {
            $durationInSeconds = static::getDurationInSecondsFromFile($file);
            $postFields['image'] = new CURLFile(static::getImage($file, $durationInSeconds / 2));
            $postFields['gifimage'] = new CURLFile(static::getGifImage($file, $durationInSeconds / 2, 3));
        } else {
            $obj->msg = "sendImages: File is empty {$file} ";
            error_log(json_encode($obj));
            return $obj;
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $target);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: multipart/form-data'));
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_SAFE_UPLOAD, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        $r = curl_exec($curl);
        error_log("sendImages: YouPHPTube-Streamer answer {$r}");
        $obj->postFields = count($postFields);
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
        error_log(json_encode($obj));
        //var_dump($obj);exit;
        return $obj;
    }

    public static function getVideoConversionStatus($encoder_queue_id) {
        global $global;
        $progressFilename = "{$global['systemRootPath']}videos/{$encoder_queue_id}_tmpFile_progress.txt";
        $content = url_get_contents($progressFilename);
        if (!empty($content)) {
            return self::parseProgress($content);
        }

        return false;
    }

    private static function parseProgress($content) {
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

    public static function getDurationInSecondsFromFile($file) {
        eval('$cmd="ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 \"{$file}\"";');
        exec($cmd . ' 2>&1', $output, $return_val);
        $durationInSeconds = $output[0];
        error_log("output {$output} duration {$durationInSeconds}");

        return $durationInSeconds;
    }

    public static function getDurationFromFile($file) {
        global $config;
        // get movie duration HOURS:MM:SS.MICROSECONDS
        if (!file_exists($file)) {
            $file_headers = @get_headers($file);
            if (!$file_headers || $file_headers[0] == 'HTTP/1.1 404 Not Found') {
                error_log('{"status":"error", "msg":"getDurationFromFile ERROR, File (' . $file . ') Not Found"}');
                return "EE:EE:EE";
            }
        }
        //$cmd = 'ffprobe -i ' . $file . ' -sexagesimal -show_entries  format=duration -v quiet -of csv="p=0"';
        eval('$cmd="ffprobe -i \"{$file}\" -sexagesimal -show_entries  format=duration -v quiet -of csv=\'p=0\'";');
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

    public static function getImage($pathFileName, $seconds = 5) {
        global $global;
        $destinationFile = "{$pathFileName}.jpg";
        // do not encode again
        if (file_exists($destinationFile)) {
            return $destinationFile;
        }

        // placing ss before the input is faster https://stackoverflow.com/a/27573049
        eval('$ffmpeg ="ffmpeg -ss {$seconds} -i {$pathFileName} -vframes 1 -y {$destinationFile}";');
        exec($ffmpeg . " < /dev/null 2>&1", $output, $return_val);
        if ($return_val !== 0) {
            error_log("Create Image error: {$ffmpeg}");
            return $global['systemRootPath'] . "view/img/notfound.jpg";
        } else {
            return $destinationFile;
        }
    }

    public static function getGifImage($pathFileName, $seconds = 5, $howLong = 3) {
        error_log("getGifImage");
        global $global;
        $destinationFile = "{$pathFileName}.gif";
        // do not encode again
        if (file_exists($destinationFile)) {
            return $destinationFile;
        }

        eval('$ffmpeg ="ffmpeg -y -ss {$seconds} -t {$howLong} -i {$pathFileName} -vf fps=10,scale=320:-1:flags=lanczos,palettegen {$pathFileName}palette.png";');
        exec($ffmpeg . " < /dev/null 2>&1", $output, $return_val);
        if ($return_val !== 0) {
            error_log("Create Pallete Gif Image error: {$ffmpeg}");
            return $global['systemRootPath'] . "view/img/notfound.gif";
        } else {
            // I've discovered that if the ss parameter comes before the input flag, a tremendous time penalty is avoided.
            // Also I've developed this ffmpeg line to allow unusual aspect videos to be letter boxed
            // so that they don't get rendered incorrectly on the youphptube site. https://superuser.com/a/891478

            eval('$ffmpeg ="ffmpeg -ss {$seconds} -t {$howLong} -i {$pathFileName} -i {$pathFileName}palette.png -filter_complex \"fps=10,scale=(iw*sar)*min(320/(iw*sar)\,180/ih):ih*min(320/(iw*sar)\,180/ih):flags=lanczos[x];[x][1:v]paletteuse, pad=320:180:(320-iw*min(320/iw\,180/ih))/2:(180-ih*min(320/iw\,180/ih))/2\" {$destinationFile}";');
            exec($ffmpeg . " < /dev/null 2>&1", $output, $return_val);
            if ($return_val !== 0) {
                error_log("Create Gif Image error: {$ffmpeg}");
                return $global['systemRootPath'] . "view/img/notfound.gif";
            } else {
                return $destinationFile;
            }
        }
    }

    public function delete() {
        global $global;
        if (empty($this->id)) {
            return false;
        }
        $files = glob("{$global['systemRootPath']}videos/{$this->id}_tmpFile*"); // get all file names
        foreach ($files as $file) { // iterate files
            if (is_file($file)) {
                unlink($file);
            }
            // delete file
        }
        $this->deleteOriginal();
        return parent::delete();
    }

    private function deleteOriginal() {
        global $global;
        if (empty($this->id)) {
            return false;
        }
        $files = glob("{$global['systemRootPath']}videos/original_" . $this->getFilename()); // get all file names
        foreach ($files as $file) { // iterate files
            if (is_file($file)) {
                unlink($file);
            }
            // delete file
        }
        return true;
    }

    public static function checkList() {
        // is videos writeble
    }

    public static function parseDurationToSeconds($str) {
        $durationParts = explode(":", $str);
        if (empty($durationParts[1])) {
            return 0;
        }

        $minutes = intval(($durationParts[0]) * 60) + intval($durationParts[1]);
        return intval($durationParts[2]) + ($minutes * 60);
    }

    public static function formatDuration($str) {
        $seconds = 0;
        if (preg_match('/^[0-9]+$/', $str)) { // seconds only
            $seconds = $str;
        } else if (preg_match('/^[0-9]+:[0-9]+$/', $str)) { // seconds and minutes
            $durationParts = explode(":", $str);
            $seconds = intval(($durationParts[0]) * 60) + intval($durationParts[1]);
        } else if (preg_match('/^[0-9]+:[0-9]+:[0-9]+$/', $str)) { // seconds and minutes
            $durationParts = explode(":", $str);
            $seconds = intval(($durationParts[0]) * 60 * 60) + (($durationParts[1]) * 60) + intval($durationParts[2]);
        }
        return self::parseSecondsToDuration($seconds);
    }

    public static function parseSecondsToDuration($int) {
        $seconds = $int % 60;
        if ($seconds < 10) {
            $seconds = "0{$seconds}";
        }
        $minutes = floor(($int / 60) % 60);
        if ($minutes < 10) {
            $minutes = "0{$minutes}";
        }
        $hours = floor($int / (60 * 60));
        if ($hours < 10) {
            $hours = "0{$hours}";
        }

        return "{$hours}:{$minutes}:{$seconds}";
    }

    /**
     *
     * @param type $link channel link
     * @return Array {"url": "DeHSfLqwqxg", "_type": "url", "ie_key": "Youtube", "id": "DeHSfLqwqxg", "title": "COMMERCIALS IN REAL LIFE"}
     */
    public static function getReverseVideosJsonListFromLink($link)
    {
        $cmd = self::getYouTubeDLCommand() . " --force-ipv4 --skip-download  --playlist-reverse --flat-playlist -j  \"{$link}\"";
        exec($cmd . "  2>&1", $output, $return_val);
        if ($return_val !== 0) {
            error_log("Get ReverseVideosJsonListFromLink List Error: $cmd \n" . print_r($output, true));
            return false;
        } else {
            $list = array();
            foreach ($output as $value) {
                $list[] = json_decode($value);
            }
            return $list;
        }
    }

    public static function getTitleFromLink($link) {
        $cmd = self::getYouTubeDLCommand() . " --no-playlist --force-ipv4 --skip-download -e \"{$link}\"";
        exec($cmd . "  2>&1", $output, $return_val);
        if ($return_val !== 0) {
            error_log("Get Title Error: $cmd \n" . print_r($output, true));
            return false;
        } else {
            return end($output);
        }
    }

    public static function getDurationFromLink($link) {
        $cmd = self::getYouTubeDLCommand() . " --no-playlist --force-ipv4 --get-duration --skip-download \"{$link}\"";
        exec($cmd . "  2>&1", $output, $return_val);
        if ($return_val !== 0) {
            return false;
        } else {
            $line = end($output);
            if (preg_match('/^[0-9:]+$/', $line)) {
                return self::formatDuration($line);
            } else {
                error_log("Could not get duration " . print_r($output, true));
                return "EE:EE:EE";
            }
        }
    }

    public static function getThumbsFromLink($link) {
        $tmpfname = tempnam(sys_get_temp_dir(), 'thumbs');
        $cmd = self::getYouTubeDLCommand() . " --no-playlist --force-ipv4 --write-thumbnail --skip-download  -o \"{$tmpfname}.jpg\" \"{$link}\"";
        exec($cmd . "  2>&1", $output, $return_val);
        if ($return_val !== 0) {
            return false;
        } else {
            return url_get_contents($tmpfname . ".jpg");
        }
    }

    public static function getDescriptionFromLink($link) {
        if (empty($link)) {
            return '';
        }
        $tmpfname = tempnam(sys_get_temp_dir(), 'thumbs');
        $cmd = self::getYouTubeDLCommand() . " --no-playlist --force-ipv4 --write-description --skip-download  -o \"{$tmpfname}\" \"{$link}\"";
        exec($cmd . "  2>&1", $output, $return_val);
        if ($return_val !== 0) {
            return false;
        } else {
            return url_get_contents($tmpfname . ".description");
        }
    }

    public static function getYouTubeDLCommand() {
        if (file_exists("/usr/local/bin/youtube-dl")) {
            return "/usr/local/bin/youtube-dl";
        } else {
            return "youtube-dl";
        }
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
