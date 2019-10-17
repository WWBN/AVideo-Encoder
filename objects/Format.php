<?php

if (!class_exists('Format')) {

    class Format extends ObjectYPT {

        protected $id, $name, $code, $created, $modified, $extension, $extension_from, $order;

        static function getSearchFieldsNames() {
            return array('name');
        }

        static function getTableName() {
            return 'formats';
        }

        function loadFromOrder($order) {
            $row = self::getFromOrder($order);
            if (empty($row))
                return false;
            foreach ($row as $key => $value) {
                $this->$key = $value;
            }
            return true;
        }

        static protected function getFromOrder($order) {
            global $global;
            $sql = "SELECT * FROM " . static::getTableName() . " WHERE  `order` = $order LIMIT 1";
            $global['lastQuery'] = $sql;
            $res = $global['mysqli']->query($sql);
            if ($res) {
                $row = $res->fetch_assoc();
            } else {
                $row = false;
            }
            return $row;
        }

        // ffmpeg -i {$pathFileName} -vf scale=352:240 -vcodec h264 -acodec aac -strict -2 -y {$destinationFile}
        function run($pathFileName, $encoder_queue_id) {
            global $global;
            $obj = new stdClass();
            $obj->error = true;
            $path_parts = pathinfo($pathFileName);
            if ($this->order == 70) {
                error_log("run:runVideoToSpectrum");
                $obj = $this->runVideoToSpectrum($pathFileName, $encoder_queue_id);
            } else if ($this->order == 71) {
                error_log("run:runVideoToAudio");
                $obj = $this->runVideoToAudio($pathFileName, $encoder_queue_id);
            } elseif ($this->order == 72) {
                error_log("run:runBothVideo");
                $obj = $this->runBothVideo($pathFileName, $encoder_queue_id);
            } else if ($this->order == 73) {
                error_log("run:runBothAudio");
                $obj = $this->runBothAudio($pathFileName, $encoder_queue_id, $this->id);
            } else if (in_array($this->order, $global['multiResolutionOrder'])) {
                error_log("run:runMultiResolution");
                $obj = $this->runMultiResolution($pathFileName, $encoder_queue_id, $this->order);
            } else {
                error_log("run: {$this->order}");
                $destinationFile = $path_parts['dirname'] . "/" . $path_parts['filename'] . "_converted." . $path_parts['extension'];
                $obj = static::execOrder($this->order, $pathFileName, $destinationFile, $encoder_queue_id);
            }
            return $obj;
        }

        private function runMultiResolution($pathFileName, $encoder_queue_id, $order) {
            global $global;
            $path_parts = pathinfo($pathFileName);
            $destinationFile = $path_parts['dirname'] . "/" . $path_parts['filename'] . "_converted";

            if (in_array($order, $global['hasHDOrder'])) {
                $obj = static::execOrder(12, $pathFileName, $destinationFile . "_HD.mp4", $encoder_queue_id);
                if (in_array($order, $global['bothVideosOrder'])) { // make the webm too
                    $obj = static::execOrder(22, $pathFileName, $destinationFile . "_HD.webm", $encoder_queue_id);
                }
            }
            if (in_array($order, $global['hasSDOrder'])) {
                $obj = static::execOrder(11, $pathFileName, $destinationFile . "_SD.mp4", $encoder_queue_id);
                if (in_array($order, $global['bothVideosOrder'])) { // make the webm too
                    $obj = static::execOrder(21, $pathFileName, $destinationFile . "_SD.webm", $encoder_queue_id);
                }
            }
            if (in_array($order, $global['hasLowOrder'])) {
                $obj = static::execOrder(10, $pathFileName, $destinationFile . "_Low.mp4", $encoder_queue_id);
                if (in_array($order, $global['bothVideosOrder'])) { // make the webm too
                    $obj = static::execOrder(20, $pathFileName, $destinationFile . "_Low.webm", $encoder_queue_id);
                }
            }

            return $obj;
        }

        private function runVideoToSpectrum($pathFileName, $encoder_queue_id) {
            global $global;
            $path_parts = pathinfo($pathFileName);
            $destinationFile = $path_parts['dirname'] . "/" . $path_parts['filename'] . "_converted";

            // MP4 to MP3
            error_log("runVideoToSpectrum: MP4 to MP3");
            $obj = static::execOrder(60, $pathFileName, $destinationFile . ".mp3", $encoder_queue_id);
            if (!$obj->error) {
                //MP3 to Spectrum.MP4
                error_log("runVideoToSpectrum: MP3 to MP4");
                $obj = static::execOrder(50, $obj->destinationFile, $destinationFile . ".mp4", $encoder_queue_id);
                if (!$obj->error) {
                    // Spectrum.MP4 to WEBM
                    error_log("runVideoToSpectrum: MP4 to WEBM");
                    $obj = static::execOrder(21, $obj->destinationFile, $destinationFile . ".webm", $encoder_queue_id);
                }
            }
            
            if($obj->error){
                error_log("runVideoToSpectrum: ERROR ". json_encode($obj));
            }
            
            return $obj;
        }

        private function runVideoToAudio($pathFileName, $encoder_queue_id) {
            $path_parts = pathinfo($pathFileName);
            $destinationFile = $path_parts['dirname'] . "/" . $path_parts['filename'] . "_converted";

            // MP4 to MP3
            $obj = static::execOrder(60, $pathFileName, $destinationFile . ".mp3", $encoder_queue_id);
            if (!$obj->error) {
                //MP4 to OGG
                $obj = static::execOrder(40, $pathFileName, $destinationFile . ".ogg", $encoder_queue_id);
            }
            return $obj;
        }

        private function runBothVideo($pathFileName, $encoder_queue_id) {
            $path_parts = pathinfo($pathFileName);
            $destinationFile = $path_parts['dirname'] . "/" . $path_parts['filename'] . "_converted";

            // Video to MP4
            $obj = static::execOrder(11, $pathFileName, $destinationFile . ".mp4", $encoder_queue_id);
            if (!$obj->error) {
                //MP4 to WEBM
                $obj = static::execOrder(21, $pathFileName, $destinationFile . ".webm", $encoder_queue_id);
            }
            return $obj;
        }

        private function runBothAudio($pathFileName, $encoder_queue_id) {
            $path_parts = pathinfo($pathFileName);
            $destinationFile = $path_parts['dirname'] . "/" . $path_parts['filename'] . "_converted";

            // Audio to MP3
            $obj = static::execOrder(30, $pathFileName, $destinationFile . ".mp3", $encoder_queue_id);
            if (!$obj->error) {
                //MP3 to OGG
                $obj = static::execOrder(40, $pathFileName, $destinationFile . ".ogg", $encoder_queue_id);
            }
            return $obj;
        }

        static private function preProcessHLS($destinationFile) {
            $parts = pathinfo($destinationFile);
            $destinationFile = "{$parts["dirname"]}/{$parts["filename"]}/";
            // create a directory
            mkdir($destinationFile);
            mkdir($destinationFile . "low");
            mkdir($destinationFile . "sd");
            mkdir($destinationFile . "hd");
            // create a encryption key
            $key = openssl_random_pseudo_bytes(16);
            $keyFileName = "enc_" . uniqid() . ".key";
            file_put_contents($destinationFile . $keyFileName, $key);

            // create info file keyinfo
            $str = "../{$keyFileName}\n{$destinationFile}{$keyFileName}";
            file_put_contents($destinationFile . "keyinfo", $str);

            //master playlist
            $str = "#EXTM3U
#EXT-X-VERSION:3
#EXT-X-STREAM-INF:BANDWIDTH=800000
low/index.m3u8
#EXT-X-STREAM-INF:BANDWIDTH=1400000
sd/index.m3u8
#EXT-X-STREAM-INF:BANDWIDTH=2800000
hd/index.m3u8
";
            file_put_contents($destinationFile . "index.m3u8", $str);
            return $destinationFile;
        }

        static function getResolution($pathFileName) {
            $command = "ffprobe -v quiet -print_format json -show_format -show_streams '$pathFileName'";
            error_log("getResolution: {$command}");
            $json = exec($command . " 2>&1", $output, $return_val);
            if ($return_val !== 0) {
                error_log("getResolution: Error on ffprobe");
                return 1080;
            }
            $json = implode(" ", $output);
            $jsonObj = json_decode($json);
            
            if (empty($jsonObj)) {
                error_log("getResolution: Error on json {$json}");
                return 1080;
            }
            
            $resolution = 1080;
            foreach($jsonObj->streams as $stream){
                if(!empty($stream->height)){
                    $resolution = $stream->height;
                    break;
                }
            }
            error_log("getResolution: success $resolution ({$json})");
            
            return $resolution;
        }

        /**
          2160p: 3840x2160
          1440p: 2560x1440
          1080p: 1920x1080
          720p: 1280x720
          480p: 854x480
          360p: 640x360
          240p: 426x240
         * @param type $destinationFile
         * @param type $resolutions
         * @return type
         */
        static private function preProcessDynamicHLS($pathFileName, $destinationFile) {
            $height = self::getResolution($pathFileName);
            $resolutions = array(360, 480, 720, 1080, 1440, 2160);
            $bandwidth = array(600000, 1000000, 2000000, 4000000, 8000000, 12000000);
            //$videoBitrate = array(472, 872, 1372, 2508, 3000, 4000);
            $audioBitrate = array(128, 128, 192, 192, 192, 192);
            $parts = pathinfo($destinationFile);
            $destinationFile = "{$parts["dirname"]}/{$parts["filename"]}/";
            // create a directory
            mkdir($destinationFile);
            // create a encryption key
            $key = openssl_random_pseudo_bytes(16);
            $keyFileName = "enc_" . uniqid() . ".key";
            file_put_contents($destinationFile . $keyFileName, $key);

            // create info file keyinfo
            $str = "../{$keyFileName}\n{$destinationFile}{$keyFileName}";
            file_put_contents($destinationFile . "keyinfo", $str);

            //master playlist
            $str = "#EXTM3U
#EXT-X-VERSION:3
";

            mkdir($destinationFile . "res240");
            $str .= "#EXT-X-STREAM-INF:BANDWIDTH=300000
res240/index.m3u8
";
            foreach ($resolutions as $key => $value) {
                if ($height >= $value) {
                    mkdir($destinationFile . "res{$value}");
                    $str .= "#EXT-X-STREAM-INF:BANDWIDTH=".($bandwidth[$key])."
res{$value}/index.m3u8
";
                }
            }

            file_put_contents($destinationFile . "index.m3u8", $str);
            
            // create command
            
            $command = 'ffmpeg -i {$pathFileName} ';
            $command .= ' -c:a aac -b:a 128k -c:v libx264 -vf scale=-2:240 -g 48 -keyint_min 48  -sc_threshold 0 -bf 3 -b_strategy 2 -b:v '.(300).'k -maxrate '.(450).'k -bufsize '.(600).'k -b:a 128k -f hls -hls_time 15 -hls_list_size 0 -hls_key_info_file {$destinationFile}keyinfo {$destinationFile}res240/index.m3u8';
            
            foreach ($resolutions as $key => $value) {
                if ($height >= $value) {
                    $rate = $bandwidth[$key]/1000;
                    $command .= ' -c:a aac -b:a '.($audioBitrate[$key]).'k -c:v libx264 -vf scale=-2:'.$value.' -g 48 -keyint_min 48  -sc_threshold 0 -bf 3 -b_strategy 2 -b:v '.($rate).'k -maxrate '.($rate*1.5).'k -bufsize '.($rate*2).'k -b:a '.($audioBitrate[$key]).'k -f hls -hls_time 15 -hls_list_size 0 -hls_key_info_file {$destinationFile}keyinfo {$destinationFile}res'.$value.'/index.m3u8';
                }
            }
            
            return array($destinationFile, $command);
        }

        static private function posProcessHLS($destinationFile, $encoder_queue_id) {
            // zip the directory
            $encoder = new Encoder($encoder_queue_id);
            $encoder->setStatus("packing");
            $encoder->save();
            unlink($destinationFile . "keyinfo");
            error_log("posProcessHLS: ZIP start {$destinationFile}");
            $zipPath = zipDirectory($destinationFile);
            error_log("posProcessHLS: ZIP created {$zipPath} ".  humanFileSize(filesize($zipPath)));
            return file_exists($zipPath);
        }

        static private function exec($format_id, $pathFileName, $destinationFile, $encoder_queue_id) {
            global $global;
            $obj = new stdClass();
            $obj->error = true;
            $obj->destinationFile = $destinationFile;
            $obj->pathFileName = $pathFileName;
            $f = new Format($format_id);
            $fc = $f->getCode();
            if ($format_id == 29) {// it is HLS
                if (empty($fc)) {
                    $dynamic = self::preProcessDynamicHLS($pathFileName, $destinationFile);
                    $destinationFile = $dynamic[0];
                    $fc = $dynamic[1];
                } else { // use default 3 resolutions
                    $destinationFile = self::preProcessHLS($destinationFile);
                }
            }



            eval('$code ="' . $fc . '";');
            if (empty($code)) {
                $obj->msg = "Code not found ($format_id, $pathFileName, $destinationFile, $encoder_queue_id)";
            } else {
                $obj->code = $code;
                error_log("YouPHPTube-Encoder Start Encoder [{$code}] ");
                exec($code . " 1> {$global['systemRootPath']}videos/{$encoder_queue_id}_tmpFile_progress.txt  2>&1", $output, $return_val);
                if ($return_val !== 0) {
                    error_log($code . " --- " . json_encode($output) . " --- ($format_id, $pathFileName, $destinationFile, $encoder_queue_id) ");
                    $obj->msg = print_r($output, true);
                    $encoder = new Encoder($encoder_queue_id);
                    $encoder->setStatus("error");
                    $encoder->setStatus_obs(json_encode($output));
                    $encoder->save();
                } else {
                    $obj->error = false;
                }
            }

            if ($format_id == 29) {// it is HLS
                $obj->error = !self::posProcessHLS($destinationFile, $encoder_queue_id);
                if ($obj->error) {
                    $obj->msg = "Error on pack directory";
                }
            }
            return $obj;
        }

        static private function execOrder($format_order, $pathFileName, $destinationFile, $encoder_queue_id) {
            $o = new Format(0);
            $o->loadFromOrder($format_order);
            // make sure the file extension is correct
            if ($format_order == 50) {
                $parts = pathinfo($destinationFile);
                if (strtolower($parts["extension"]) === 'mp3') {
                    $destinationFile = "{$parts["dirname"]}/{$parts["filename"]}.mp4";
                }
            }
            $obj = self::exec($o->getId(), $pathFileName, $destinationFile, $encoder_queue_id);
            if ($format_order == 50) {
                if (!$obj->error) {
                    // Spectrum.MP4 to WEBM
                    error_log("runVideoToSpectrum: MP4 to WEBM");
                    $obj = static::execOrder(21, $obj->destinationFile, $destinationFile . ".webm", $encoder_queue_id);
                }
            }
            return $obj;                
        }

        static function getFromName($name) {
            global $global;
            $name = strtolower(trim($name));
            $sql = "SELECT * FROM  " . static::getTableName() . " WHERE LOWER(name) = '{$name}' LIMIT 1";

            $res = $global['mysqli']->query($sql);
            if ($res) {
                return $res->fetch_assoc();
            } else {
                die($sql . '\nError : (' . $global['mysqli']->errno . ') ' . $global['mysqli']->error);
            }
            return false;
        }

        static function createIfNotExists($name) {
            $row = static::getFromName($name);
            if (empty($row)) {
                $f = new Format("");
                $f->setName($name);
                $f->setExtension($name);
                $f->setCode("");
                $row['id'] = $f->save();
            }
            return $row['id'];
        }

        function getId() {
            return $this->id;
        }

        function getName() {
            return $this->name;
        }

        function getCode() {
            return $this->code;
        }

        function getCreated() {
            return $this->created;
        }

        function getModified() {
            return $this->modified;
        }

        function getExtension() {
            return $this->extension;
        }

        function setId($id) {
            $this->id = $id;
        }

        function setName($name) {
            $this->name = $name;
        }

        function setCode($code) {
            global $global;
            $this->code = $global['mysqli']->real_escape_string($code);
        }

        function setCreated($created) {
            $this->created = $created;
        }

        function setModified($modified) {
            $this->modified = $modified;
        }

        function setExtension($extension) {
            $this->extension = $extension;
        }

        function getExtension_from() {
            return $this->extension_from;
        }

        function setExtension_from($extension_from) {
            $this->extension_from = $extension_from;
        }

        function getOrder() {
            return $this->order;
        }

        function setOrder($order) {
            $this->order = $order;
        }

    }

}