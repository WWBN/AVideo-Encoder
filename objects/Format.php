<?php

if (!class_exists('Format')) {
    if (!class_exists('ObjectYPT')) {
        require_once $global['systemRootPath'] . 'objects/Object.php';
    }
    if (!class_exists('Upload')) {
        require_once $global['systemRootPath'] . 'objects/Upload.php';
    }

    class Format extends ObjectYPT
    {

        protected $id, $name, $code, $created, $modified, $extension, $extension_from, $order;

        static function getSearchFieldsNames()
        {
            return array('name');
        }

        static function getTableName()
        {
            global $global;
            return $global['tablesPrefix'] . 'formats';
        }

        function loadFromOrder($order)
        {
            $row = self::getFromOrder($order);
            if (empty($row))
                return false;
            foreach ($row as $key => $value) {
                $this->$key = $value;
            }
            return true;
        }

        static protected function getFromOrder($order)
        {
            error_log("AVideo-Encoder Format::getFromOrder($order)");
            global $global;
            $sql = "SELECT * FROM " . static::getTableName() . " WHERE  `order` = $order LIMIT 1";
            /**
             * @var array $global
             * @var object $global['mysqli']
             */
            $global['lastQuery'] = $sql;
            $res = $global['mysqli']->query($sql);
            if ($res) {
                $row = $res->fetch_assoc();
            } else {
                $row = false;
            }
            return $row;
        }

        function run($pathFileName, $encoder_queue_id)
        {
            error_log("AVideo-Encoder Format::run($pathFileName, $encoder_queue_id)");
            global $global;
            $obj = new stdClass();
            $obj->error = true;
            $path_parts = pathinfo($pathFileName);
            /**
             * @var array $global
             */
            if ($this->order == 88) {
                error_log("run:mp3ToSpectrumHLS");
                $obj = $this->mp3ToSpectrumHLS($pathFileName, $encoder_queue_id);
            } else if ($this->order == 89) {
                error_log("run:mp3ToSpectrumMP4");
                $obj = $this->mp3ToSpectrumMP4($pathFileName, $encoder_queue_id);
            } else if ($this->order == 90 && empty($global['disableWebM'])) {
                error_log("run:mp3ToSpectrumWEBM");
                $obj = $this->mp3ToSpectrumWEBM($pathFileName, $encoder_queue_id);
            } else if ($this->order == 70) {
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
            } else if (in_array($this->order, $global['multiResolutionOrder']) && !in_array($this->order, $global['sendAll'])) {
                error_log("run:runMultiResolution");
                error_log("run:runMultiResolution" . json_encode($this->order));
                error_log("run:runMultiResolution" . json_encode($global['sendAll']));
                $obj = $this->runMultiResolution($pathFileName, $encoder_queue_id, $this->order);
            } else {
                error_log("run (else): {$this->order}");
                $destinationFile = Encoder::getTmpFileName($encoder_queue_id, $path_parts['extension']);
                $obj = static::execOrder($this->order, $pathFileName, $destinationFile, $encoder_queue_id);
            }
            return $obj;
        }

        private function runMultiResolution($pathFileName, $encoder_queue_id, $order)
        {
            error_log("AVideo-Encoder Format::runMultiResolution($pathFileName, $encoder_queue_id, $order)");
            global $global;
            $path_parts = pathinfo($pathFileName);
            //$destinationFile = $path_parts['dirname'] . "/" . $path_parts['filename'] . "_converted";
            $obj = null;
            /**
             * @var array $global
             */
            if (in_array($order, $global['hasHDOrder'])) {
                $destination = Encoder::getTmpFileName($encoder_queue_id, 'mp4', "HD");
                $obj = static::execOrder(12, $pathFileName, $destination, $encoder_queue_id);
                if (empty($global['disableWebM']) && in_array($order, $global['bothVideosOrder'])) { // make the webm too
                    $destination = Encoder::getTmpFileName($encoder_queue_id, 'webm', "HD");
                    $obj = static::execOrder(22, $pathFileName, $destination, $encoder_queue_id);
                }
            }
            if (in_array($order, $global['hasSDOrder'])) {
                $destination = Encoder::getTmpFileName($encoder_queue_id, 'mp4', "SD");
                $obj = static::execOrder(11, $pathFileName, $destination, $encoder_queue_id);
                if (empty($global['disableWebM']) && in_array($order, $global['bothVideosOrder'])) { // make the webm too
                    $destination = Encoder::getTmpFileName($encoder_queue_id, 'webm', "SD");
                    $obj = static::execOrder(21, $pathFileName, $destination, $encoder_queue_id);
                }
            }
            if (in_array($order, $global['hasLowOrder'])) {
                $destination = Encoder::getTmpFileName($encoder_queue_id, 'mp4', "Low");
                $obj = static::execOrder(10, $pathFileName, $destination, $encoder_queue_id);
                if (empty($global['disableWebM']) && in_array($order, $global['bothVideosOrder'])) { // make the webm too
                    $destination = Encoder::getTmpFileName($encoder_queue_id, 'webm', "Low");
                    $obj = static::execOrder(20, $pathFileName, $destination, $encoder_queue_id);
                }
            }

            if (!empty($global['progressiveUpload'])) {
                $destinationFile = Encoder::getTmpFileName($encoder_queue_id, 'mp4');
                Upload::create($encoder_queue_id, $destinationFile);
            }

            return $obj;
        }

        private function sendImages($file, $encoder_queue_id)
        {
            $encoder = new Encoder($encoder_queue_id);
            $return_vars = json_decode($encoder->getReturn_vars());
            return Encoder::sendImages($file, $return_vars, $encoder);
        }

        private function mp3ToSpectrum($pathFileName, $encoder_queue_id)
        {
            error_log("AVideo-Encoder Format::mp3ToSpectrum($pathFileName, $encoder_queue_id)");
            global $global;
            $path_parts = pathinfo($pathFileName);
            //$destinationFile = $path_parts['dirname'] . "/" . $path_parts['filename'] . "_converted";

            error_log("mp3ToSpectrum: MP3 to MP4");
            $destinationFile = Encoder::getTmpFileName($encoder_queue_id, 'mp4', "converted");
            return self::exec(5, $pathFileName, $destinationFile, $encoder_queue_id);
        }

        private function mp3ToSpectrumHLS($pathFileName, $encoder_queue_id)
        {
            error_log("AVideo-Encoder Format::mp3ToSpectrumHLS($pathFileName, $encoder_queue_id)");
            global $global;
            $path_parts = pathinfo($pathFileName);
            //$destinationFile = $path_parts['dirname'] . "/" . $path_parts['filename'] . "_converted";
            $destinationFile = Encoder::getTmpFileName($encoder_queue_id, 'mp4', "converted");
            $obj = self::mp3ToSpectrum($pathFileName, $encoder_queue_id);
            if (!$obj->error) {
                $obj = static::execOrder(6, $obj->destinationFile, $destinationFile, $encoder_queue_id);
            }
            if ($obj->error) {
                error_log("mp3ToSpectrumHLS: ERROR " . json_encode($obj));
            }
            $this->sendImages($destinationFile, $encoder_queue_id);
            return $obj;
        }

        private function mp3ToSpectrumMP4($pathFileName, $encoder_queue_id)
        {
            error_log("AVideo-Encoder Format::mp3ToSpectrumHLS($pathFileName, $encoder_queue_id)");
            global $global;
            $path_parts = pathinfo($pathFileName);
            //$destinationFile = $path_parts['dirname'] . "/" . $path_parts['filename'] . "_converted";
            $destinationFile = Encoder::getTmpFileName($encoder_queue_id, 'mp4', "converted");
            $obj = self::mp3ToSpectrum($pathFileName, $encoder_queue_id);
            if (!$obj->error) {
                $obj = static::execOrder(7, $obj->destinationFile, $destinationFile, $encoder_queue_id);
            }
            if ($obj->error) {
                error_log("mp3ToSpectrumMP4: ERROR " . json_encode($obj));
            }
            $this->sendImages($destinationFile, $encoder_queue_id);
            return $obj;
        }

        private function mp3ToSpectrumWEBM($pathFileName, $encoder_queue_id)
        {
            error_log("AVideo-Encoder Format::mp3ToSpectrumWEBM($pathFileName, $encoder_queue_id)");
            global $global;
            $path_parts = pathinfo($pathFileName);
            //$destinationFile = $path_parts['dirname'] . "/" . $path_parts['filename'] . "_converted";
            $destinationFile = Encoder::getTmpFileName($encoder_queue_id, 'webm', "converted");
            $obj = self::mp3ToSpectrum($pathFileName, $encoder_queue_id);
            if (!$obj->error) {
                $obj = static::execOrder(8, $obj->destinationFile, $destinationFile, $encoder_queue_id);
            }
            if ($obj->error) {
                error_log("mp3ToSpectrumWEBM: ERROR " . json_encode($obj));
            }
            $this->sendImages($destinationFile, $encoder_queue_id);
            return $obj;
        }

        private function runVideoToSpectrum($pathFileName, $encoder_queue_id)
        {
            error_log("AVideo-Encoder Format::runVideoToSpectrum($pathFileName, $encoder_queue_id)");
            global $global;
            $path_parts = pathinfo($pathFileName);
            //$destinationFile = $path_parts['dirname'] . "/" . $path_parts['filename'] . "_converted";

            $destinationFile = Encoder::getTmpFileName($encoder_queue_id, 'mp3', "converted");
            // MP4 to MP3
            error_log("runVideoToSpectrum: MP4 to MP3");
            $obj = static::execOrder(60, $pathFileName, $destinationFile, $encoder_queue_id);
            if (!$obj->error) {
                //MP3 to Spectrum.MP4
                error_log("runVideoToSpectrum: MP3 to MP4");
                $destinationFile = Encoder::getTmpFileName($encoder_queue_id, 'mp4', "converted");
                $obj = static::execOrder(50, $obj->destinationFile, $destinationFile, $encoder_queue_id);
                if (empty($global['disableWebM']) && !$obj->error) {
                    // Spectrum.MP4 to WEBM
                    error_log("runVideoToSpectrum: MP4 to WEBM");
                    $destinationFile = Encoder::getTmpFileName($encoder_queue_id, 'webm', "converted");
                    $obj = static::execOrder(21, $obj->destinationFile, $destinationFile, $encoder_queue_id);
                }
            }

            if ($obj->error) {
                error_log("runVideoToSpectrum: ERROR " . json_encode($obj));
            }

            return $obj;
        }

        private function runVideoToAudio($pathFileName, $encoder_queue_id)
        {
            error_log("AVideo-Encoder Format::runVideoToAudio($pathFileName, $encoder_queue_id)");
            $path_parts = pathinfo($pathFileName);
            //$destinationFile = $path_parts['dirname'] . "/" . $path_parts['filename'] . "_converted";

            $destinationFile = Encoder::getTmpFileName($encoder_queue_id, 'mp3', "converted");

            // MP4 to MP3
            $obj = static::execOrder(60, $pathFileName, $destinationFile, $encoder_queue_id);
            if (!$obj->error) {
                $destinationFile = Encoder::getTmpFileName($encoder_queue_id, 'ogg', "converted");
                //MP4 to OGG
                $obj = static::execOrder(40, $pathFileName, $destinationFile, $encoder_queue_id);
            }
            return $obj;
        }

        private function runBothVideo($pathFileName, $encoder_queue_id)
        {
            error_log("AVideo-Encoder Format::runBothVideo($pathFileName, $encoder_queue_id)");
            $path_parts = pathinfo($pathFileName);
            //$destinationFile = $path_parts['dirname'] . "/" . $path_parts['filename'] . "_converted";
            $destinationFile = Encoder::getTmpFileName($encoder_queue_id, 'mp4', "converted");

            // Video to MP4
            $obj = static::execOrder(11, $pathFileName, $destinationFile, $encoder_queue_id);
            if (empty($global['disableWebM']) && !$obj->error) {
                $destinationFile = Encoder::getTmpFileName($encoder_queue_id, 'webm', "converted");
                //MP4 to WEBM
                $obj = static::execOrder(21, $pathFileName, $destinationFile, $encoder_queue_id);
            }
            return $obj;
        }

        private function runBothAudio($pathFileName, $encoder_queue_id)
        {
            error_log("AVideo-Encoder Format::runBothAudio($pathFileName, $encoder_queue_id)");
            $path_parts = pathinfo($pathFileName);
            //$destinationFile = $path_parts['dirname'] . "/" . $path_parts['filename'] . "_converted";
            $destinationFile = Encoder::getTmpFileName($encoder_queue_id, 'mp3', "converted");

            // Audio to MP3
            $obj = static::execOrder(30, $pathFileName, $destinationFile, $encoder_queue_id);
            if (!$obj->error) {
                $destinationFile = Encoder::getTmpFileName($encoder_queue_id, 'ogg', "converted");
                //MP3 to OGG
                $obj = static::execOrder(40, $pathFileName, $destinationFile, $encoder_queue_id);
            }
            return $obj;
        }

        static private function preProcessHLS($destinationFile)
        {
            $parts = pathinfo($destinationFile);
            $destinationFile = "{$parts["dirname"]}/{$parts["filename"]}/";
            // create a directory
            make_path($destinationFile);
            make_path($destinationFile . "low");
            make_path($destinationFile . "sd");
            make_path($destinationFile . "hd");
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

        static function getResolution($pathFileName)
        {
            global $_getResolution;

            if (!isset($_getResolution)) {
                $_getResolution = array();
            }

            if (!empty($_getResolution[$pathFileName])) {
                return $_getResolution[$pathFileName];
            }

            $command = get_ffprobe() . " -v quiet -print_format json -show_format -show_streams \"$pathFileName\"";
            error_log("getResolution: {$command}");
            $json = exec($command . " 2>&1", $output, $return_val);
            if ($return_val !== 0) {
                error_log("getResolution: Error on ffprobe " . json_encode($output));
                return 1080;
            }
            $json = implode(" ", $output);
            $jsonObj = json_decode($json);

            if (empty($jsonObj)) {
                error_log("getResolution: Error on json {$json}");
                return 1080;
            }

            $resolution = 1080;
            foreach ($jsonObj->streams as $stream) {
                if (!empty($stream->height)) {
                    $resolution = $stream->height;
                    break;
                }
            }
            error_log("getResolution: success $resolution");
            $_getResolution[$pathFileName] = $resolution;
            return $resolution;
        }

        static function getAudioTracks($pathFileName)
        {
            global $global;
            if (empty($global['enableMultipleLangs'])) {
                return array();
            }
            $command = get_ffprobe() . " -v quiet -print_format json -show_entries stream=index:stream_tags=language -select_streams a \"$pathFileName\"";
            error_log("getAudioTracks: {$command}");
            $json = exec($command . " 2>&1", $output, $return_val);
            if ($return_val !== 0) {
                error_log("getResolution: Error on ffprobe " . json_encode($output));
                return 1080;
            }
            $json = implode(" ", $output);
            $jsonObj = json_decode($json);

            if (empty($jsonObj)) {
                error_log("getResolution: Error on json {$json}");
                return 1080;
            }

            $audioTracks = array();
            foreach ($jsonObj->streams as $stream) {
                if (!empty($stream->tags) && !empty($stream->tags->language)) {
                    $audioTracks[] = $stream->tags->language;
                }
            }
            error_log("getAudioTracks: success " . json_encode($audioTracks) . " ({$json})");

            return $audioTracks;
        }

        static private function getDynamicCommandFromMP4($pathFileName, $encoder_queue_id)
        {
            return self::getDynamicCommandFromFormat($pathFileName, $encoder_queue_id, 31);
        }

        static private function getDynamicCommandFromWebm($pathFileName, $encoder_queue_id)
        {
            return self::getDynamicCommandFromFormat($pathFileName, $encoder_queue_id, 32);
        }

        /**
         * 2160p: 3840x2160
         * 1440p: 2560x1440
         * 1080p: 1920x1080
         * 720p: 1280x720
         * 480p: 854x480
         * 360p: 640x360
         * 240p: 426x240 (preview)
         */
        static private function getAvailableConfigurations()
        {
            $resolutions = array(240, 360, 480, 540, 720, 1080, 1440, 2160);
            $bandwidth = array(300000, 600000, 1000000, 1500000, 2000000, 4000000, 8000000, 12000000);
            $audioBitrate = array(128, 128, 128, 192, 192, 192, 192, 192);
            $videoFramerate = array(20, 30, 30, 0, 0, 0, 0, 0);

            return array(
                "resolutions" => $resolutions,
                "bandwidth" => $bandwidth,
                "audioBitrate" => $audioBitrate,
                "videoFramerate" => $videoFramerate
            );
        }

        static function getAvailableResolutions()
        {
            return self::getAvailableConfigurations()["resolutions"];
        }

        static function getAvailableResolutionsInfo()
        {
            global $config;
            $resolutions = array();
            $availableResolutions = Format::getAvailableResolutions();
            $selectedResolutions = $config->getSelectedResolutions();
            foreach ($availableResolutions as $key => $resolution) {
                $resolutionChecked = (array_search($resolution, $selectedResolutions, true) !== false) || !empty($resolutionDisabled) ? "checked" : "";

                $label = "<span class='label label-default'>{$resolution}p ";
                if ($resolution == 720) {
                    $label .= '<span class="label label-danger">HD</span>';
                } else if ($resolution == 1080) {
                    $label .= '<span class="label label-danger">FHD</span>';
                } else if ($resolution == 1440) {
                    $label .= '<span class="label label-danger">FHD+</span>';
                } else if ($resolution == 2160) {
                    $label .= '<span class="label label-danger">4K</span>';
                }
                $label .= " </span>";

                $resolutions[] = array(
                    'resolutionChecked' => $resolutionChecked,
                    'label' => $label,
                    'resolution' => $resolution,
                    'resolutionChecked' => $resolutionChecked,
                );
            }
            return $resolutions;
        }

        static function sanitizeResolutions($resolutions)
        {
            if (is_array($resolutions)) {
                // resolutions need to be int values
                $resolutions = array_map(
                    function ($value) {
                        return (int) $value;
                    },
                    $resolutions
                );

                // check if all the int values are real resolutions
                $availableResolutions = self::getAvailableResolutions();
                foreach ($resolutions as $index => $resolution) {
                    $key = array_search($resolution, $availableResolutions);
                    if ($key === false) {
                        $resolutions[$index] = 0;
                    }
                }

                // remove all invalid resolutions marked by 0
                $resolutions = array_unique($resolutions);
                sort($resolutions);

                // cleanup the invalid value (0)
                $key = array_search(0, $resolutions);
                if ($key !== false) {
                    unset($resolutions[$key]);
                    $resolutions = array_values($resolutions);
                }

                // if the array contains valid $resolutions, then return it     
                if (!empty($resolutions)) {
                    return $resolutions;
                }
            }
            return null;
        }

        static private function getSelectedResolutions()
        {
            $result = array(480, 720, 1080, 2160);
            $config = new Configuration();
            if (isset($config)) {
                $configResolutions = $config->getSelectedResolutions();
                if (isset($configResolutions)) {
                    $result = $configResolutions;
                }
            }
            return $result;
        }

        static private function loadEncoderConfiguration()
        {
            $availableConfiguration = self::getAvailableConfigurations();

            $resolutions = array();
            $bandwidth = array();
            $audioBitrate = array();
            $videoFramerate = array();

            $selectedResolutions = self::getSelectedResolutions();

            sort($selectedResolutions);

            foreach ($selectedResolutions as $index => $value) {
                $key = array_search($value, $availableConfiguration["resolutions"]);

                array_push($resolutions, $availableConfiguration["resolutions"][$key]);
                array_push($bandwidth, $availableConfiguration["bandwidth"][$key]);
                array_push($audioBitrate, $availableConfiguration["audioBitrate"][$key]);
                array_push($videoFramerate, $availableConfiguration["videoFramerate"][$key]);
            }

            return array(
                "resolutions" => $resolutions,
                "bandwidth" => $bandwidth,
                "audioBitrate" => $audioBitrate,
                "videoFramerate" => $videoFramerate
            );
        }

        static private function getDynamicCommandFromFormat($pathFileName, $encoder_queue_id, $format_id)
        {
            $height = self::getResolution($pathFileName);
            //$audioTracks = self::getAudioTracks($pathFileName);
            $advancedCustom = getAdvancedCustomizedObjectData();

            $encoderConfig = self::loadEncoderConfiguration();
            $resolutions = $encoderConfig['resolutions'];
            $bandwidth = $encoderConfig['bandwidth'];
            $audioBitrate = $encoderConfig['audioBitrate'];
            $videoFramerate = $encoderConfig['videoFramerate'];

            error_log("Encoder:Format:: getDynamicCommandFromFormat($pathFileName, $format_id) [resolutions=" . json_encode($resolutions) . "] [height={$height}]");
            $f = new Format($format_id);
            $code = $f->getCode(); // encoder command-line switches
            // create command            
            $command = get_ffmpeg() . ' -i "{$pathFileName}" ';

            $i = 0;
            $lastHeight = 0;
            $countResolutions = 0;
            while ($i < count($resolutions)) {
                $resolution = $resolutions[$i];
                if ($resolution <= $height) {
                    $countResolutions++;
                    $lastHeight = $resolution;
                    $destinationFile = Encoder::getTmpFileName($encoder_queue_id, $f->getExtension(), $resolution);
                    if (empty($destinationFile)) {
                        error_log("Encoder:Format:: getDynamicCommandFromFormat destination file is empty");
                        continue;
                    }
                    $autioBitrate = $audioBitrate[$i];
                    $framerate = (!empty($videoFramerate[$i])) ? " -r {$videoFramerate[$i]} " : "";

                    eval("\$command .= \" $code\";");
                } else if ($height != $resolution) {
                    error_log("Encoder:Format:: getDynamicCommandFromFormat resolution {$resolution} was ignored, your upload file is {$height} we wil not up transcode your video");
                    break;
                }
                $i++;
            }

            if (($advancedCustom->saveOriginalVideoResolution && $lastHeight < $height) || empty($countResolutions)) {
                $destinationFile = Encoder::getTmpFileName($encoder_queue_id, $f->getExtension(), $height);
                if (empty($destinationFile)) {
                    error_log("Encoder:Format:: getDynamicCommandFromFormat destination file is empty 2");
                    return '';
                }
                $code = ' -codec:v libx264 -movflags faststart -y {$destinationFile} ';
                eval("\$command .= \" $code\";");
            }

            $command = removeUserAgentIfNotURL($command);
            error_log("Encoder:Format:: getDynamicCommandFromFormat::return($command) ");
            return $command;
        }

        static private function preProcessDynamicHLS($pathFileName, $destinationFile)
        {
            $height = self::getResolution($pathFileName);
            //$audioTracks = self::getAudioTracks($pathFileName);
            // TODO: This method should be refactored to use loadEncoderConfiguration instead of getAvailableConfigurations...
            $encoderConfig = self::loadEncoderConfiguration();
            $resolutions = $encoderConfig['resolutions'];
            $bandwidth = $encoderConfig['bandwidth'];
            $audioBitrate = $encoderConfig['audioBitrate'];
            $videoFramerate = $encoderConfig['videoFramerate'];
            $parts = pathinfo($destinationFile);
            $destinationFile = "{$parts["dirname"]}/{$parts["filename"]}/";
            error_log("Encoder:Format:: preProcessDynamicHLS($pathFileName, $destinationFile) [resolutions=" . json_encode($resolutions) . "] [height={$height}] [$destinationFile=$destinationFile]");
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
            $str = "#EXTM3U" . PHP_EOL . "#EXT-X-VERSION:3" . PHP_EOL;

            $nextBandwidth = $bandwidth[0];
            foreach ($resolutions as $key => $value) {
                if (!empty($bandwidth[$key + 1])) {
                    $nextBandwidth = $bandwidth[$key + 1];
                }
                if ($height > $value) {
                    $file = $destinationFile . "res{$value}";
                    mkdir($file);
                    $str .= "#EXT-X-STREAM-INF:BANDWIDTH=" . ($bandwidth[$key]) . PHP_EOL . "res{$value}/index.m3u8" . PHP_EOL;
                    error_log("Encoder:Format:: preProcessDynamicHLS 1 mkdir [$file] ");
                } else if ($height != $value) {
                    error_log("Encoder:Format:: preProcessDynamicHLS resolution {$value} was ignored, your upload file is {$height}p we wil not up transcode your video");
                }
            }

            $file = $destinationFile . "res{$height}";
            mkdir($file);
            $str .= "#EXT-X-STREAM-INF:BANDWIDTH=" . ($nextBandwidth) . PHP_EOL . "res{$height}/index.m3u8" . PHP_EOL;
            error_log("Encoder:Format:: preProcessDynamicHLS 1 mkdir [$file] ");

            file_put_contents($destinationFile . "index.m3u8", $str);

            $f = new Format(30);
            $code = $f->getCode();

            $command = get_ffmpeg() . ' -i {$pathFileName} -max_muxing_queue_size 9999 ';

            $rate = 300000;
            $minrate = ($rate * 0.5);
            $maxrate = ($rate * 1.5);
            $bufsize = ($rate * 2);
            $autioBitrate = 128;

            foreach ($resolutions as $key => $value) {
                if ($height > $value) {
                    $rate = $bandwidth[$key] / 1000;
                    $minrate = ($rate * 0.5);
                    $maxrate = ($rate * 1.5);
                    $bufsize = ($rate * 2);
                    $autioBitrate = $audioBitrate[$key];
                    if ($value <= 360) {
                        $framerate = " -r 20 ";
                    } else {
                        $framerate = "";
                    }

                    $resolution = $value;
                    if (!empty($videoFramerate[$key])) {
                        $framerate = " -r {$videoFramerate[$key]} ";
                    }
                    eval("\$command .= \" $code\";");
                    error_log("Encoder:Format:: 2 preProcessDynamicHLS {$command}");
                } else if ($height != $value) {
                    error_log("Encoder:Format:: preProcessDynamicHLS 2 resolution {$value} was ignored, your upload file is {$height} we wil not up transcode your video");
                }
            }

            $resolution = $height;
            //$code = ' -c:v h264 -c:a aac -f hls -hls_time 6 -hls_list_size 0 -hls_key_info_file {$destinationFile}keyinfo {$destinationFile}res{$resolution}/index.m3u8';
            eval("\$command .= \" $code\";");

            $command = removeUserAgentIfNotURL($command);
            return array($destinationFile, $command);
        }

        static private function posProcessHLS($destinationFile, $encoder_queue_id)
        {
            // zip the directory
            $encoder = new Encoder($encoder_queue_id);
            $encoder->setStatus(Encoder::$STATUS_PACKING);
            $encoder->save();
            error_log("posProcessHLS: ZIP start {$destinationFile}");
            $zipPath = zipDirectory($destinationFile);
            //rrmdir($destinationFile);
            //unlink($destinationFile . "keyinfo");
            error_log("posProcessHLS: ZIP created {$zipPath} " . humanFileSize(filesize($zipPath)));
            return file_exists($zipPath);
        }

        static private function fixFile($pathFileName, $encoder_queue_id)
        {
            // zip the directory
            $encoder = new Encoder($encoder_queue_id);
            $encoder->setStatus(Encoder::$STATUS_FIXING);
            $encoder->save();
            error_log("fixFile: start {$pathFileName}" . humanFileSize(filesize($pathFileName)));
            // try to fix the file in case you want to try again
            $newPathFileName = $pathFileName . '.error';
            rename($pathFileName, $newPathFileName);
            $command = get_ffmpeg() . " -copyts -fflags +genpts -i {$newPathFileName} -map 0:v -c:v copy {$pathFileName} ";
            //$command = replaceFFMPEG($command);
            $command = removeUserAgentIfNotURL($command);
            $encoder->exec($command, $output, $return_val);

            if ($return_val !== 0) {
                error_log("fixFile: Error " . json_encode($output));
                return false;
            } else {
                error_log("fixFile: done {$pathFileName} " . humanFileSize(filesize($pathFileName)));
            }
            return file_exists($pathFileName);
        }

        static private function exec($format_id, $pathFileName, $destinationFile, $encoder_queue_id, $try = 0)
        {
            global $global;
            $obj = new stdClass();
            $obj->error = true;
            $obj->destinationFile = $destinationFile;
            $obj->pathFileName = $pathFileName;
            $f = new Format($format_id);
            $fc = $f->getCode();

            $encoder = new Encoder($encoder_queue_id);
            error_log("AVideo-Encoder Format::exec [$format_id, $pathFileName, $destinationFile, $encoder_queue_id] code=({$fc})");
            if ($format_id == 29 || $format_id == 30) { // it is HLS
                if (empty($fc) || $format_id == 30) {
                    $dynamic = self::preProcessDynamicHLS($pathFileName, $destinationFile);
                    $destinationFile = $dynamic[0];
                    $fc = $dynamic[1];
                } else { // use default 3 resolutions
                    $destinationFile = self::preProcessHLS($destinationFile);
                }
            } else if ($format_id == 31) { // it is MP4
                $fc = self::getDynamicCommandFromMP4($pathFileName, $encoder_queue_id);
            } else if ($format_id == 32) { // it is WebM
                $fc = self::getDynamicCommandFromWebm($pathFileName, $encoder_queue_id);
            }
            $code = '';
            eval('$code ="' . addcslashes($fc, '"') . '";');
            $code = replaceFFMPEG($code);
            $code = removeUserAgentIfNotURL($code);
            if (empty($code)) {
                $obj->msg = "Code not found ($format_id, $pathFileName, $destinationFile, $encoder_queue_id)";
            } else {
                $obj->code = $code;
                error_log("AVideo-Encoder Format::exec  Start Encoder [{$code}] ");
                $progressFile = "{$global['systemRootPath']}videos/{$encoder_queue_id}_tmpFile_progress.txt";
                $encoder->exec($code . " 1> \"{$progressFile}\"  2>&1", $output, $return_val);
                if (self::progressFileHasVideosWithErrors($progressFile)) {
                    error_log("AVideo-Encoder Format::exec ERROR ($return_val) progressFile={$progressFile}" . PHP_EOL . json_encode($output));
                    $obj->msg = print_r($output, true);
                    $encoder = new Encoder($encoder_queue_id);
                    if (empty($encoder->getId())) {/* dequeued */
                        error_log("id=(" . $encoder_queue_id . ") dequeued");
                    } else {

                        if (empty($try) && self::fixFile($pathFileName, $encoder_queue_id)) {
                            self::exec($format_id, $pathFileName, $destinationFile, $encoder_queue_id, $try + 1);
                        } else {
                            $encoder->setStatus(Encoder::$STATUS_ERROR);
                            $encoder->setStatus_obs(json_encode($output));
                            $encoder->save();
                        }
                    }
                } else {
                    $obj->error = false;
                }
            }

            if ($format_id == 29 || $format_id == 30) { // it is HLS
                $obj->error = !self::posProcessHLS($destinationFile, $encoder_queue_id);
                if ($obj->error) {
                    $obj->msg = "Error on pack directory";
                }
            }
            return $obj;
        }

        static function progressFileHasVideosWithErrors($progressFilename)
        {
            global $global;

            if (empty($progressFilename)) {
                error_log("progressFileHasVideosWithErrors: file not exists {$progressFilename}");
                return true;
            }

            $content = file_get_contents($progressFilename);

            if (empty($content)) {
                error_log("progressFileHasVideosWithErrors: content is empty");
                return true;
            }

            $videos_dir = addcslashes("{$global['systemRootPath']}videos", '/');
            $pattern = "/output.*to '({$videos_dir}.*)'/i";

            preg_match_all($pattern, $content, $matches);

            if (empty($matches[1])) {
                error_log("progressFileHasVideosWithErrors: we could not detect files on the progress log, we will ignore errors" . PHP_EOL . $content);
                return false;
            }
            //error_log("progressFileHasVideosWithErrors: {$pattern} matches= " . json_encode($matches));
            foreach ($matches[1] as $value) {
                if (empty($value)) {
                    continue;
                }
                //error_log("progressFileHasVideosWithErrors: value= " . json_encode($value));
                if (self::videoFileHasErrors($value)) {
                    error_log("progressFileHasVideosWithErrors: error found {$value}");
                    return true;
                }
            }
            //error_log("progressFileHasVideosWithErrors: no errors found {$progressFilename}");
            return false;
        }

        static function videoFileHasErrors($filename, $allowed_extensions = true)
        {
            global $global;
            if (!file_exists($filename)) {
                error_log("videoFileHasErrors: file not exists {$filename}");
                return true;
            }

            if (!empty($global['byPassVideoFileHasErrors'])) {
                return false;
            }

            $errorLogFile = $filename . '.error.log';

            /**
             * -allowed_extensions ALL  is required
             * key' is not a common multimedia extension, blocked for security reasons.
              If you wish to override this adjust allowed_extensions, you can set it to 'ALL' to allow all
             */
            $complement = '';
            if ($allowed_extensions) {
                $complement = '-allowed_extensions ALL';
            }

            if (isWindows()) {
                $command = get_ffmpeg() . " {$complement} -v error -i \"{$filename}\" -f null - >\"{$errorLogFile}\" 2>&1 ";
            } else {
                $command = get_ffmpeg() . " {$complement} -v error -i \"{$filename}\" -f null - 2>\"{$errorLogFile}\" ";
            }
            $command = removeUserAgentIfNotURL($command);
            exec($command);

            if (!file_exists($errorLogFile)) {
                error_log("videoFileHasErrors: error.log file not exists {$errorLogFile}");
                return true;
            }

            $content = file_get_contents($errorLogFile);
            unlink($errorLogFile);

            if (!empty($content)) {
                if ($allowed_extensions) {
                    return self::videoFileHasErrors($filename, false);
                }
                error_log("videoFileHasErrors: errors found on video file {$filename} " . PHP_EOL . $content);
                return true;
            } else {
                return false;
            }
        }

        static private function execOrder($format_order, $pathFileName, $destinationFile, $encoder_queue_id)
        {
            if (empty($destinationFile)) {
                $obj = new stdClass();
                $obj->error = true;
                $obj->destinationFile = $destinationFile;
                $obj->pathFileName = $pathFileName;
                $obj->msg = "destinationFile is empty";
                error_log("execOrder($format_order, $pathFileName, $destinationFile, $encoder_queue_id) destinationFile");
                return $obj;
            }
            if (file_exists($destinationFile)) {
                $src_duration = Encoder::getDurationFromFile($pathFileName);
                $dst_duration = Encoder::getDurationFromFile($destinationFile);
                if ($src_duration == $dst_duration) {
                    $obj = new stdClass();
                    $obj->error = false;
                    $obj->destinationFile = $destinationFile;
                    $obj->pathFileName = $pathFileName;
                    $obj->msg = "Already done";
                    error_log($destinationFile . " already done, skip");
                    return $obj;
                } else {
                    unlink($destinationFile);
                }
            }

            $o = new Format(0);
            $o->loadFromOrder($format_order);
            // make sure the file extension is correct
            if ($format_order == 50) {
                $parts = pathinfo($destinationFile);
                if (strtolower($parts["extension"]) === 'mp3') {
                    $destinationFile = "{$parts["dirname"]}/{$parts["filename"]}.mp4";
                }
            }
            if ($format_order == 60) {
                $parts = pathinfo($destinationFile);
                if (strtolower($parts["extension"]) === 'mp4') {
                    $destinationFile = "{$parts["dirname"]}/{$parts["filename"]}.mp3";
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

        static function getFromName($name)
        {
            global $global;
            $name = strtolower(trim($name));
            $sql = "SELECT * FROM  " . static::getTableName() . " WHERE LOWER(name) = '{$name}' LIMIT 1";

            /**
             * @var array $global
             * @var object $global['mysqli']
             */
            $res = $global['mysqli']->query($sql);
            if ($res) {
                return $res->fetch_assoc();
            } else {
                die($sql . '\nError : (' . $global['mysqli']->errno . ') ' . $global['mysqli']->error);
            }
            return false;
        }

        static function createIfNotExists($name)
        {
            if (empty($name)) {
                return false;
            }
            error_log("createIfNotExists($name) checking");
            $row = static::getFromName($name);
            if (empty($row)) {
                error_log("createIfNotExists($name) not found, create a new one");
                $f = new Format("");
                $f->setName($name);
                $f->setExtension($name);
                $f->setCode("");
                $row['id'] = $f->save();
            }
            return $row['id'];
        }

        function getId()
        {
            return $this->id;
        }

        function getName()
        {
            return $this->name;
        }

        function getCode()
        {
            return $this->code;
        }

        function getCreated()
        {
            return $this->created;
        }

        function getModified()
        {
            return $this->modified;
        }

        function getExtension()
        {
            return $this->extension;
        }

        function setId($id)
        {
            $this->id = $id;
        }

        function setName($name)
        {
            global $global;
            /**
             * @var array $global
             */
            $this->name = $global['mysqli']->real_escape_string($name);
        }

        function setCode($code)
        {
            global $global;
            /**
             * @var array $global
             */
            $this->code = $global['mysqli']->real_escape_string($code);
        }

        function setCreated($created)
        {
            $this->created = $created;
        }

        function setModified($modified)
        {
            $this->modified = $modified;
        }

        function setExtension($extension)
        {
            global $global;
            /**
             * @var array $global
             */
            $this->extension = $global['mysqli']->real_escape_string($extension);
        }

        function getExtension_from()
        {
            return $this->extension_from;
        }

        function setExtension_from($extension_from)
        {
            $this->extension_from = $extension_from;
        }

        function getOrder()
        {
            return $this->order;
        }

        function setOrder($order)
        {
            $this->order = $order;
        }
    }
}
