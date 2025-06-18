<?php

require_once __DIR__ . '/HLSProcessor.php';
require_once __DIR__ . '/MP4Processor.php';
require_once __DIR__ . '/MP3Processor.php';

if (!class_exists('Format')) {
    if (!class_exists('ObjectYPT')) {
        require_once $global['systemRootPath'] . 'objects/Object.php';
    }
    if (!class_exists('Upload')) {
        require_once $global['systemRootPath'] . 'objects/Upload.php';
    }

    class Format extends ObjectYPT
    {

        protected $id;
        protected $name;
        protected $code;
        protected $created;
        protected $modified;
        protected $extension;
        protected $extension_from;
        protected $order;
        const ENCODING_SETTINGS = array(
            '240' => array(
                'minrate'      => 300,  // Reduced from 500
                'maxrate'      => 500,  // Reduced from 700
                'bufsize'      => 1000, // Reduced from 1400
                'audioBitrate' => 48,   // Reduced from 64
            ),
            '360' => array(
                'minrate'      => 500,  // Reduced from 800
                'maxrate'      => 800,  // Reduced from 1000
                'bufsize'      => 1600, // Reduced from 2000
                'audioBitrate' => 64,   // Reduced from 96
            ),
            '480' => array(
                'minrate'      => 800,  // Reduced from 1200
                'maxrate'      => 1000, // Reduced from 1500
                'bufsize'      => 2000, // Reduced from 3000
                'audioBitrate' => 96,   // Reduced from 128
            ),
            '540' => array( // Added 540p resolution
                'minrate'      => 1000, // Suggested based on reduction pattern
                'maxrate'      => 1500, // Suggested based on reduction pattern
                'bufsize'      => 3000, // Suggested based on reduction pattern
                'audioBitrate' => 96,   // Suggested to match 480p settings
            ),
            '720' => array(
                'minrate'      => 1500, // Reduced from 2000
                'maxrate'      => 2000, // Reduced from 2500
                'bufsize'      => 4000, // Reduced from 5000
                'audioBitrate' => 128,  // Retained at 128
            ),
            '1080' => array(
                'minrate'      => 3000, // Reduced from 4000
                'maxrate'      => 4000, // Reduced from 5000
                'bufsize'      => 8000, // Reduced from 10000
                'audioBitrate' => 128,  // Reduced from 192
            ),
            '1440' => array( // Added 1440p resolution
                'minrate'      => 6000,  // Suggested based on higher resolution pattern
                'maxrate'      => 8000,  // Suggested based on higher resolution pattern
                'bufsize'      => 16000, // Suggested based on higher resolution pattern
                'audioBitrate' => 160,   // Suggested for better audio quality at higher resolutions
            ),
            '2160' => array(
                'minrate'      => 8000,  // Reduced from 16000
                'maxrate'      => 12000, // Reduced from 20000
                'bufsize'      => 24000, // Reduced from 40000
                'audioBitrate' => 160,   // Reduced from 192
            ),
        );

        public static function getSearchFieldsNames()
        {
            return array('name');
        }

        public static function getTableName()
        {
            global $global;
            return $global['tablesPrefix'] . 'formats';
        }

        public function loadFromOrder($order)
        {
            $row = self::getFromOrder($order);
            if (empty($row)) {
                return false;
            }
            foreach ($row as $key => $value) {
                $this->$key = $value;
            }
            return true;
        }

        protected static function getFromOrder($order)
        {
            _error_log("AVideo-Encoder Format::getFromOrder($order)");
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

        public function run($pathFileName, $encoder_queue_id)
        {
            _error_log("AVideo-Encoder Format::run($pathFileName, $encoder_queue_id) " . json_encode(debug_backtrace()));
            global $global;
            $obj = new stdClass();
            $obj->error = true;
            $obj->addInQueueAgain = false;
            $path_parts = pathinfo($pathFileName);
            if (!file_exists($pathFileName)) {
                _error_log("AVideo-Encoder Format::run($pathFileName, $encoder_queue_id) ERROR File not found");
                $obj->msg = "file not found $pathFileName";
                $obj->addInQueueAgain = true;
                $obj->code = 404;
                return $obj;
            }
            /**
             * @var array $global
             */
            if ($this->order == 88) {
                _error_log("run:mp3ToSpectrumHLS");
                $obj = $this->mp3ToSpectrumHLS($pathFileName, $encoder_queue_id);
            } elseif ($this->order == 89) {
                _error_log("run:mp3ToSpectrumMP4");
                $obj = $this->mp3ToSpectrumMP4($pathFileName, $encoder_queue_id);
            } elseif ($this->order == 90 && empty($global['disableWebM'])) {
                _error_log("run:mp3ToSpectrumWEBM");
                $obj = $this->mp3ToSpectrumWEBM($pathFileName, $encoder_queue_id);
            } elseif ($this->order == 70) {
                _error_log("run:runVideoToSpectrum");
                $obj = $this->runVideoToSpectrum($pathFileName, $encoder_queue_id);
            } elseif ($this->order == 71) {
                _error_log("run:runVideoToAudio");
                $obj = $this->runVideoToAudio($pathFileName, $encoder_queue_id);
            } elseif ($this->order == 72) {
                _error_log("run:runBothVideo");
                $obj = $this->runBothVideo($pathFileName, $encoder_queue_id);
            } elseif ($this->order == 73) {
                _error_log("run:runBothAudio");
                $obj = $this->runBothAudio($pathFileName, $encoder_queue_id, $this->id);
            } elseif (in_array($this->order, $global['multiResolutionOrder']) && !in_array($this->order, $global['sendAll'])) {
                _error_log("run:runMultiResolution");
                _error_log("run:runMultiResolution" . json_encode($this->order));
                _error_log("run:runMultiResolution" . json_encode($global['sendAll']));
                $obj = $this->runMultiResolution($pathFileName, $encoder_queue_id, $this->order);
            } else {
                _error_log("run (else): {$this->order}");
                $destinationFile = Encoder::getTmpFileName($encoder_queue_id, $path_parts['extension']);
                $obj = static::execOrder($this->order, $pathFileName, $destinationFile, $encoder_queue_id);
            }
            return $obj;
        }

        private function runMultiResolution($pathFileName, $encoder_queue_id, $order)
        {
            _error_log("AVideo-Encoder Format::runMultiResolution($pathFileName, $encoder_queue_id, $order)");
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
            _error_log("AVideo-Encoder Format::mp3ToSpectrum($pathFileName, $encoder_queue_id)");
            global $global;
            $path_parts = pathinfo($pathFileName);
            //$destinationFile = $path_parts['dirname'] . "/" . $path_parts['filename'] . "_converted";

            _error_log("mp3ToSpectrum: MP3 to MP4");
            $destinationFile = Encoder::getTmpFileName($encoder_queue_id, 'mp4', "converted");
            return self::exec(5, $pathFileName, $destinationFile, $encoder_queue_id);
        }

        private function mp3ToSpectrumHLS($pathFileName, $encoder_queue_id)
        {
            _error_log("AVideo-Encoder Format::mp3ToSpectrumHLS($pathFileName, $encoder_queue_id)");
            global $global;
            $path_parts = pathinfo($pathFileName);
            //$destinationFile = $path_parts['dirname'] . "/" . $path_parts['filename'] . "_converted";
            $destinationFile = Encoder::getTmpFileName($encoder_queue_id, 'mp4', "converted");
            $obj = self::mp3ToSpectrum($pathFileName, $encoder_queue_id);
            if (!$obj->error) {
                //_error_log("AVideo-Encoder Format::execOrder(6, $obj->destinationFile, $destinationFile, $encoder_queue_id) MP4 to HLS ".json_encode($obj));
                //$obj = static::execOrder(6, $obj->destinationFile, $destinationFile, $encoder_queue_id);
                $code = new Format(30);
                $obj = $code->run($destinationFile, $encoder_queue_id);
            } else {
                _error_log("mp3ToSpectrumHLS: self::mp3ToSpectrum($pathFileName, $encoder_queue_id) ERROR ");
            }
            if ($obj->error) {
                _error_log("mp3ToSpectrumHLS: ERROR " . json_encode($obj));
            }
            $this->sendImages($destinationFile, $encoder_queue_id);
            return $obj;
        }

        private function mp3ToSpectrumMP4($pathFileName, $encoder_queue_id)
        {
            _error_log("AVideo-Encoder Format::mp3ToSpectrumHLS($pathFileName, $encoder_queue_id)");
            global $global;
            $path_parts = pathinfo($pathFileName);
            //$destinationFile = $path_parts['dirname'] . "/" . $path_parts['filename'] . "_converted";
            $destinationFile = Encoder::getTmpFileName($encoder_queue_id, 'mp4', "converted");
            $obj = self::mp3ToSpectrum($pathFileName, $encoder_queue_id);
            if (!$obj->error) {
                $obj = static::execOrder(7, $obj->destinationFile, $destinationFile, $encoder_queue_id);
            }
            if ($obj->error) {
                _error_log("mp3ToSpectrumMP4: ERROR " . json_encode($obj));
            }
            $this->sendImages($destinationFile, $encoder_queue_id);
            return $obj;
        }

        private function mp3ToSpectrumWEBM($pathFileName, $encoder_queue_id)
        {
            _error_log("AVideo-Encoder Format::mp3ToSpectrumWEBM($pathFileName, $encoder_queue_id)");
            global $global;
            $path_parts = pathinfo($pathFileName);
            //$destinationFile = $path_parts['dirname'] . "/" . $path_parts['filename'] . "_converted";
            $destinationFile = Encoder::getTmpFileName($encoder_queue_id, 'webm', "converted");
            $obj = self::mp3ToSpectrum($pathFileName, $encoder_queue_id);
            if (!$obj->error) {
                $obj = static::execOrder(8, $obj->destinationFile, $destinationFile, $encoder_queue_id);
            }
            if ($obj->error) {
                _error_log("mp3ToSpectrumWEBM: ERROR " . json_encode($obj));
            }
            $this->sendImages($destinationFile, $encoder_queue_id);
            return $obj;
        }

        private function runVideoToSpectrum($pathFileName, $encoder_queue_id)
        {
            _error_log("AVideo-Encoder Format::runVideoToSpectrum($pathFileName, $encoder_queue_id)");
            global $global;
            $path_parts = pathinfo($pathFileName);
            //$destinationFile = $path_parts['dirname'] . "/" . $path_parts['filename'] . "_converted";

            $destinationFile = Encoder::getTmpFileName($encoder_queue_id, 'mp3', "converted");
            // MP4 to MP3
            _error_log("runVideoToSpectrum: MP4 to MP3");
            $obj = static::execOrder(60, $pathFileName, $destinationFile, $encoder_queue_id);
            if (!$obj->error) {
                //MP3 to Spectrum.MP4
                _error_log("runVideoToSpectrum: MP3 to MP4");
                $destinationFile = Encoder::getTmpFileName($encoder_queue_id, 'mp4', "converted");
                $obj = static::execOrder(50, $obj->destinationFile, $destinationFile, $encoder_queue_id);
                if (empty($global['disableWebM']) && !$obj->error) {
                    // Spectrum.MP4 to WEBM
                    _error_log("runVideoToSpectrum: MP4 to WEBM");
                    $destinationFile = Encoder::getTmpFileName($encoder_queue_id, 'webm', "converted");
                    $obj = static::execOrder(21, $obj->destinationFile, $destinationFile, $encoder_queue_id);
                }
            }

            if ($obj->error) {
                _error_log("runVideoToSpectrum: ERROR " . json_encode($obj));
            }

            return $obj;
        }

        private function runVideoToAudio($pathFileName, $encoder_queue_id)
        {
            _error_log("AVideo-Encoder Format::runVideoToAudio($pathFileName, $encoder_queue_id)");
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
            _error_log("AVideo-Encoder Format::runBothVideo($pathFileName, $encoder_queue_id)");
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
            _error_log("AVideo-Encoder Format::runBothAudio($pathFileName, $encoder_queue_id)");
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

        private static function preProcessHLS($destinationFile)
        {
            $parts = pathinfo($destinationFile);
            $destinationDir = "{$parts["dirname"]}/{$parts["filename"]}/";

            // Create the necessary directories
            make_path($destinationDir);
            make_path($destinationDir . "low");
            make_path($destinationDir . "sd");
            make_path($destinationDir . "hd");

            // Check for existing .key file
            $keyFiles = glob($destinationDir . '*.key');
            if (!empty($keyFiles)) {
                // If a .key file already exists, return the directory
                _error_log("Encoder:Format:preProcessHLS($destinationFile) .key file already exists [$destinationDir] " . json_encode(array($keyFiles, debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS))));
                return $destinationDir;
            }

            // Create a new encryption key
            $key = openssl_random_pseudo_bytes(16);
            $keyFileName = "enc_" . uniqid() . ".key";
            file_put_contents($destinationDir . $keyFileName, $key);

            // Create info file keyinfo
            $str = "../{$keyFileName}\n{$destinationDir}{$keyFileName}";
            file_put_contents($destinationDir . "keyinfo", $str);

            // Create master playlist
            $str = "#EXTM3U
        #EXT-X-VERSION:3
        #EXT-X-STREAM-INF:BANDWIDTH=800000
        low/index.m3u8
        #EXT-X-STREAM-INF:BANDWIDTH=1400000
        sd/index.m3u8
        #EXT-X-STREAM-INF:BANDWIDTH=2800000
        hd/index.m3u8
        ";
            file_put_contents($destinationDir . "index.m3u8", $str);

            return $destinationDir;
        }


        public static function getResolution($pathFileName)
        {
            global $_getResolution;

            if (!isset($_getResolution)) {
                $_getResolution = [];
            }

            if (!empty($_getResolution[$pathFileName])) {
                return $_getResolution[$pathFileName];
            }

            $command = get_ffprobe() . " -v quiet -print_format json -show_format -show_streams \"$pathFileName\"";
            _error_log("getResolution: {$command}");
            $json = exec($command . " 2>&1", $output, $return_val);
            if ($return_val !== 0) {
                _error_log("getResolution: Error on ffprobe " . json_encode($output));
                return 1080;
            }
            $json = implode(" ", $output);
            $jsonObj = json_decode($json);

            if (empty($jsonObj)) {
                _error_log("getResolution: Error on json {$json}");
                return 1080;
            }

            $resolution = 1080;
            foreach ($jsonObj->streams as $stream) {
                if (!empty($stream->height)) {
                    $resolution = $stream->height;
                    break;
                }
            }
            _error_log("getResolution: success $resolution");
            $_getResolution[$pathFileName] = $resolution;
            return $resolution;
        }

        public static function getAudioTracks($pathFileName)
        {
            global $global;
            if (empty($global['enableMultipleLangs'])) {
                return array();
            }
            $command = get_ffprobe() . " -v quiet -print_format json -show_entries stream=index:stream_tags=language -select_streams a \"$pathFileName\"";
            _error_log("getAudioTracks: {$command}");
            $json = exec($command . " 2>&1", $output, $return_val);
            if ($return_val !== 0) {
                _error_log("getResolution: Error on ffprobe " . json_encode($output));
                return 1080;
            }
            $json = implode(" ", $output);
            $jsonObj = json_decode($json);

            if (empty($jsonObj)) {
                _error_log("getResolution: Error on json {$json}");
                return 1080;
            }

            $audioTracks = [];
            foreach ($jsonObj->streams as $stream) {
                if (!empty($stream->tags) && !empty($stream->tags->language)) {
                    $audioTracks[] = $stream->tags->language;
                }
            }
            _error_log("getAudioTracks: success " . json_encode($audioTracks) . " ({$json})");

            return $audioTracks;
        }

        private static function getDynamicCommandFromMP4($pathFileName, $encoder_queue_id)
        {
            return self::getDynamicCommandFromFormat($pathFileName, $encoder_queue_id, 31);
        }

        private static function getDynamicCommandFromWebm($pathFileName, $encoder_queue_id)
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
        private static function getAvailableConfigurations()
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

        public static function getAvailableResolutions()
        {
            return self::getAvailableConfigurations()["resolutions"];
        }

        public static function getAvailableResolutionsInfo()
        {
            global $config;
            $resolutions = [];
            $availableResolutions = Format::getAvailableResolutions();
            $selectedResolutions = $config->getSelectedResolutions();
            foreach ($availableResolutions as $key => $resolution) {
                $resolutionChecked = (array_search($resolution, $selectedResolutions, true) !== false) || !empty($resolutionDisabled) ? "checked" : "";

                $label = "<span class='label label-default'>{$resolution}p ";
                if ($resolution == 720) {
                    $label .= '<span class="label label-danger">HD</span>';
                } elseif ($resolution == 1080) {
                    $label .= '<span class="label label-danger">FHD</span>';
                } elseif ($resolution == 1440) {
                    $label .= '<span class="label label-danger">FHD+</span>';
                } elseif ($resolution == 2160) {
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

        public static function sanitizeResolutions($resolutions)
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

        private static function getSelectedResolutions()
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

        static function loadEncoderConfiguration()
        {
            $availableConfiguration = self::getAvailableConfigurations();

            $resolutions = [];
            $bandwidth = [];
            $audioBitrate = [];
            $videoFramerate = [];

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

        private static function getDynamicCommandFromFormat($pathFileName, $encoder_queue_id, $format_id)
        {
            $height = self::getResolution($pathFileName);
            //$audioTracks = self::getAudioTracks($pathFileName);
            $advancedCustom = getAdvancedCustomizedObjectData();

            $encoderConfig = self::loadEncoderConfiguration();
            $resolutions = $encoderConfig['resolutions'];
            $bandwidth = $encoderConfig['bandwidth'];
            $videoFramerate = $encoderConfig['videoFramerate'];
            $audioBitrate = 128; // Assign audioBitrate

            _error_log("Encoder:Format:: getDynamicCommandFromFormat($pathFileName, $format_id) [resolutions=" . json_encode($resolutions) . "] [height={$height}]");
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
                        _error_log("Encoder:Format:: getDynamicCommandFromFormat destination file is empty");
                        continue;
                    }
                    if (!empty(Format::ENCODING_SETTINGS[$resolution])) {
                        $settings = Format::ENCODING_SETTINGS[$resolution];
                        _error_log("Encoder:Format:: getDynamicCommandFromFormat line=" . __LINE__ . ' settings=' . json_encode($settings));

                        $bitrate = $settings['maxrate'];        // Assign maxrate as the bitrate
                        $minrate = $settings['minrate'];        // Assign minrate
                        $maxrate = $settings['maxrate'];        // Assign maxrate
                        $bufsize = $settings['bufsize'];        // Assign bufsize
                        $audioBitrate = $settings['audioBitrate']; // Assign audioBitrate
                    } else {
                        _error_log("Encoder:Format:: getDynamicCommandFromFormat line=" . __LINE__);
                        $bitrate = 1500;          // Default bitrate
                        $minrate = 1000;          // Default minrate
                        $maxrate = 1500;          // Default maxrate
                        $bufsize = 3000;          // Default bufsize
                        $audioBitrate = 128;      // Default audioBitrate
                    }
                    $framerate = (!empty($videoFramerate[$i])) ? " -r {$videoFramerate[$i]} " : "";

                    _error_log("Encoder:Format:: getDynamicCommandFromFormat line=" . __LINE__ . ' settings=' . json_encode($settings));
                    eval("\$command .= \" $code\";");
                } elseif ($height != $resolution) {
                    _error_log("Encoder:Format:: getDynamicCommandFromFormat resolution {$resolution} was ignored, your upload file is {$height} we wil not up transcode your video");
                    break;
                }
                $i++;
            }

            if (($advancedCustom->saveOriginalVideoResolution && $lastHeight < $height) || empty($countResolutions)) {
                $destinationFile = Encoder::getTmpFileName($encoder_queue_id, $f->getExtension(), $height);
                if (empty($destinationFile)) {
                    _error_log("Encoder:Format:: getDynamicCommandFromFormat destination file is empty 2");
                    return '';
                }
                _error_log("Encoder:Format:: getDynamicCommandFromFormat line=" . __LINE__);
                $code = ' -codec:v libx264 -movflags faststart -y {$destinationFile} ';
                eval("\$command .= \" $code\";");
            }

            $command = removeUserAgentIfNotURL($command);
            _error_log("Encoder:Format:: getDynamicCommandFromFormat::return($command) ");
            return $command;
        }

        private static function preProcessDynamicHLS($pathFileName, $destinationFile)
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
            _error_log("Encoder:Format:: preProcessDynamicHLS($pathFileName, $destinationFile) [resolutions=" . json_encode($resolutions) . "] [height={$height}] [$destinationFile=$destinationFile]");
            // create a directory
            mkdir($destinationFile);
            // create an encryption key
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
                    _error_log("Encoder:Format:: preProcessDynamicHLS 1 mkdir [$file] ");
                } elseif ($height != $value) {
                    _error_log("Encoder:Format:: preProcessDynamicHLS resolution {$value} was ignored, your upload file is {$height}p we wil not up transcode your video");
                }
            }

            $file = $destinationFile . "res{$height}";
            mkdir($file);
            $str .= "#EXT-X-STREAM-INF:BANDWIDTH=" . ($nextBandwidth) . PHP_EOL . "res{$height}/index.m3u8" . PHP_EOL;
            _error_log("Encoder:Format:: preProcessDynamicHLS 1 mkdir [$file] ");

            file_put_contents($destinationFile . "index.m3u8", $str);

            $f = new Format(30);
            $code = $f->getCode();

            $command = get_ffmpeg() . ' -i {$pathFileName} -max_muxing_queue_size 9999 ';

            $rate = 300000;
            $minrate = ($rate * 0.5);
            $maxrate = ($rate * 1.5);
            $bufsize = ($rate * 2);
            $audioBitrate = 128;

            foreach ($resolutions as $key => $value) {
                if ($height > $value) {
                    $rate = $bandwidth[$key] / 1000;
                    if ($value <= 360) {
                        $framerate = " -r 20 ";
                    } else {
                        $framerate = "";
                    }

                    $resolution = $value;

                    if (!empty(Format::ENCODING_SETTINGS[$resolution])) {
                        $settings = Format::ENCODING_SETTINGS[$resolution];

                        $bitrate = $settings['maxrate'];        // Assign maxrate as the bitrate
                        $minrate = $settings['minrate'];        // Assign minrate
                        $maxrate = $settings['maxrate'];        // Assign maxrate
                        $bufsize = $settings['bufsize'];        // Assign bufsize
                        $audioBitrate = $settings['audioBitrate']; // Assign audioBitrate
                    } else {
                        $bitrate = 1500;          // Default bitrate
                        $minrate = ($rate * 0.5);          // Default minrate
                        $maxrate = ($rate * 1.5);          // Default maxrate
                        $bufsize = ($rate * 2);          // Default bufsize
                        $audioBitrate = 128; // Assign audioBitrate
                    }

                    if (!empty($videoFramerate[$key])) {
                        $framerate = " -r {$videoFramerate[$key]} ";
                    }
                    eval("\$command .= \" $code\";");
                    _error_log("Encoder:Format:: 2 preProcessDynamicHLS {$command}");
                } elseif ($height != $value) {
                    _error_log("Encoder:Format:: preProcessDynamicHLS 2 resolution {$value} was ignored, your upload file is {$height} we wil not up transcode your video");
                }
            }

            $resolution = $height;

            if (!empty(Format::ENCODING_SETTINGS[$resolution])) {
                $settings = Format::ENCODING_SETTINGS[$resolution];

                $bitrate = $settings['maxrate'];        // Assign maxrate as the bitrate
                $minrate = $settings['minrate'];        // Assign minrate
                $maxrate = $settings['maxrate'];        // Assign maxrate
                $bufsize = $settings['bufsize'];        // Assign bufsize
                $audioBitrate = $settings['audioBitrate']; // Assign audioBitrate
            } else {
                $bitrate = 1500;          // Default bitrate
                $minrate = 1000;          // Default minrate
                $maxrate = 1500;          // Default maxrate
                $bufsize = 3000;          // Default bufsize
                $audioBitrate = 128;      // Default audioBitrate
            }

            //$code = ' -c:v h264 -c:a aac -f hls -hls_time 6 -hls_list_size 0 -hls_key_info_file {$destinationFile}keyinfo {$destinationFile}res{$resolution}/index.m3u8';
            eval("\$command .= \" $code\";");

            $command = removeUserAgentIfNotURL($command);
            return array($destinationFile, $command);
        }

        private static function posProcessHLS($destinationFile, $encoder_queue_id)
        {
            // zip the directory
            $encoder = new Encoder($encoder_queue_id);
            $encoder->setStatus(Encoder::STATUS_PACKING);
            $encoder->save();
            _error_log("posProcessHLS: ZIP start {$destinationFile}");
            $zipPath = zipDirectory($destinationFile);
            //rrmdir($destinationFile);
            //unlink($destinationFile . "keyinfo");
            _error_log("posProcessHLS: ZIP created {$zipPath} " . humanFileSize(filesize($zipPath)));
            return file_exists($zipPath);
        }

        private static function fixFile($pathFileName, $encoder_queue_id)
        {
            // zip the directory
            $encoder = new Encoder($encoder_queue_id);
            $encoder->setStatus(Encoder::STATUS_FIXING);
            $encoder->save();
            _error_log("fixFile: start {$pathFileName}" . humanFileSize(filesize($pathFileName)));
            // try to fix the file in case you want to try again
            $newPathFileName = $pathFileName . '.error';
            rename($pathFileName, $newPathFileName);
            $command = get_ffmpeg() . " -copyts -fflags +genpts -i {$newPathFileName} -map 0:v -c:v copy {$pathFileName} ";
            //$command = replaceFFMPEG($command);
            $command = removeUserAgentIfNotURL($command);
            $encoder->exec($command, $output, $return_val);

            if ($return_val !== 0) {
                _error_log("fixFile: Error " . json_encode($output));
                return false;
            } else {
                _error_log("fixFile: done {$pathFileName} " . humanFileSize(filesize($pathFileName)));
            }
            return file_exists($pathFileName);
        }

        private static function exec($format_id, $pathFileName, $destinationFile, $encoder_queue_id, $try = 0)
        {
            global $global;
            $obj = new stdClass();
            $obj->error = true;
            $obj->destinationFile = $destinationFile;
            $obj->pathFileName = $pathFileName;
            $f = new Format($format_id);
            $fc = $f->getCode();

            $encoder = new Encoder($encoder_queue_id);
            _error_log("AVideo-Encoder Format::exec [$format_id, $pathFileName, $destinationFile, $encoder_queue_id] code=({$fc})");
            if ($format_id == 29 || $format_id == 30) { // it is HLS
                if (empty($fc) || $format_id == 30) {
                    if (empty($global['disableHLSAudioMultitrack'])) {
                        _error_log("AVideo-Encoder Format::exec use HLSProcessor");
                        $dynamic = HLSProcessor::createHLSWithAudioTracks($pathFileName, $destinationFile);
                        _error_log("AVideo-Encoder Format::exec use HLSProcessor Complete");
                    } else {
                        _error_log("AVideo-Encoder Format::exec disableHLSAudioMultitrack");
                        $dynamic = self::preProcessDynamicHLS($pathFileName, $destinationFile);
                    }
                    $destinationFile = $dynamic[0];
                    $fc = $dynamic[1];

                    _error_log("AVideo-Encoder Format::exec destinationFile=$destinationFile fc=$fc ");
                } else { // use default 3 resolutions
                    $destinationFile = self::preProcessHLS($destinationFile);
                }
            } elseif ($format_id == 31) { // it is MP4
                _error_log("AVideo-Encoder Format::exec line=" . __LINE__);

                $advancedCustom = getAdvancedCustomizedObjectData();
                _error_log("AVideo-Encoder Format::exec line=" . __LINE__);
                if (!empty($advancedCustom->singleResolution->value)) {
                    _error_log("AVideo-Encoder Format::exec MP4Processor::createMP4MaxResolutionFromQueueId($pathFileName, $encoder_queue_id, {$advancedCustom->singleResolution->value})");
                    return MP4Processor::createMP4MaxResolutionFromQueueId($pathFileName, $encoder_queue_id, $advancedCustom->singleResolution->value);
                } else {
                    _error_log("AVideo-Encoder Format::exec line=" . __LINE__);
                    $fc = self::getDynamicCommandFromMP4($pathFileName, $encoder_queue_id);
                }
            } elseif ($format_id == 32) { // it is WebM
                $fc = self::getDynamicCommandFromWebm($pathFileName, $encoder_queue_id);
            }
            $code = '';
            eval('$code ="' . addcslashes($fc, '"') . '";');

            $code = replaceFFMPEG($code);
            $code = removeUserAgentIfNotURL($code);
            if (empty($code)) {
                _error_log("AVideo-Encoder Format::exec code is empty ");
                $obj->msg = "Code not found ($format_id, $pathFileName, $destinationFile, $encoder_queue_id)";
            } else {
                $obj->code = $code;
                $progressFile = "{$global['systemRootPath']}videos/{$encoder_queue_id}_tmpFile_progress.txt";
                _error_log("AVideo-Encoder Format::exec  Start Encoder [{$code}] {$progressFile} ");
                $encoder->exec($code . " 1> \"{$progressFile}\"  2>&1", $output, $return_val);
                if (self::progressFileHasVideosWithErrors($progressFile)) {
                    _error_log("AVideo-Encoder Format::exec ERROR ($return_val) progressFile={$progressFile}" . PHP_EOL . json_encode($output));
                    $obj->msg = print_r($output, true);
                    $encoder = new Encoder($encoder_queue_id);
                    if (empty($encoder->getId())) {/* dequeued */
                        _error_log("id=(" . $encoder_queue_id . ") dequeued");
                    } else {
                        //if (empty($try) && self::fixFile($pathFileName, $encoder_queue_id)) {
                        if (empty($try)) {
                            self::exec($format_id, $pathFileName, $destinationFile, $encoder_queue_id, $try + 1);
                        } else {
                            $msg = json_encode($output);
                            _error_log("AVideo-Encoder Format::exec " . $msg . ' ' . json_encode(debug_backtrace()));
                            $encoder->setStatus(Encoder::STATUS_ERROR);
                            $encoder->setStatus_obs($msg);
                            $encoder->save();
                        }
                    }
                } else {
                    _error_log("AVideo-Encoder Format::exec Success progressFile={$progressFile}");
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

        public static function progressFileHasVideosWithErrors($progressFilename)
        {
            global $global;

            if (empty($progressFilename)) {
                _error_log("progressFileHasVideosWithErrors: file not exists {$progressFilename}");
                return true;
            }

            $content = file_get_contents($progressFilename);

            if (empty($content)) {
                _error_log("progressFileHasVideosWithErrors: content is empty");
                return true;
            }

            $videos_dir = addcslashes("{$global['systemRootPath']}videos", '/');
            $pattern = "/output.*to '({$videos_dir}.*)'/i";

            preg_match_all($pattern, $content, $matches);

            if (empty($matches[1])) {
                _error_log("progressFileHasVideosWithErrors: we could not detect files on the progress log, we will ignore errors" . PHP_EOL . $content);
                return false;
            }
            //_error_log("progressFileHasVideosWithErrors: {$pattern} matches= " . json_encode($matches));
            foreach ($matches[1] as $value) {
                if (empty($value)) {
                    continue;
                }
                //_error_log("progressFileHasVideosWithErrors: value= " . json_encode($value));
                if (self::videoFileHasErrors($value)) {
                    _error_log("progressFileHasVideosWithErrors: error found {$value}");
                    return true;
                }
            }
            //_error_log("progressFileHasVideosWithErrors: no errors found {$progressFilename}");
            return false;
        }

        public static function videoFileHasErrors($filename, $allowed_extensions = true)
        {
            global $global;
            if (!file_exists($filename)) {
                _error_log("videoFileHasErrors: file not exists {$filename}");
                return true;
            }

            if (!empty($global['byPassVideoFileHasErrors'])) {
                return false;
            }

            $errorLogFile = tempnam(sys_get_temp_dir(), 'video_error_log');

            // Check if the file extension is compatible with -allowed_extensions ALL
            $compatibleExtensions = ['m3u8', 'mpd']; // Add any other compatible extensions here
            $fileExtension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $isCompatible = in_array($fileExtension, $compatibleExtensions);

            $complement = '';
            if ($allowed_extensions && $isCompatible) {
                $complement = '-allowed_extensions ALL';
            }

            $skipFramesOption = '';
            $durationOption = '';
            //$skipFramesOption = '-vf "select=not(mod(n\,1000))"';
            $durationOption = '-t 10';  // check the first 10 seconds

            if (isWindows()) {
                $command = get_ffmpeg() . " {$complement} {$skipFramesOption} {$durationOption} -v error -i \"{$filename}\" -f null - > \"{$errorLogFile}\" 2>&1 ";
            } else {
                $command = get_ffmpeg() . " {$complement} {$skipFramesOption} {$durationOption} -v error -i \"{$filename}\" -f null - 2> \"{$errorLogFile}\" ";
            }
            $command = removeUserAgentIfNotURL($command);
            exec($command, $output, $return_var);
            if ($return_var !== 0) {
                _error_log("videoFileHasErrors: could not exec [{$command}] " . json_encode($output) . ' ' . json_encode(debug_backtrace()));
                return false;
            }

            if (!file_exists($errorLogFile)) {
                _error_log("videoFileHasErrors: error.log file not exists {$errorLogFile}");
                return true;
            }

            $content = file_get_contents($errorLogFile);
            unlink($errorLogFile);

            if (!empty($content)) {
                if ($allowed_extensions && $isCompatible) {
                    return self::videoFileHasErrors($filename, false);
                }
                _error_log("videoFileHasErrors: errors found on video file {$filename} " . PHP_EOL . $content);
                return true;
            } else {
                return false;
            }
        }


        private static function execOrder($format_order, $pathFileName, $destinationFile, $encoder_queue_id)
        {
            if (empty($destinationFile)) {
                $obj = new stdClass();
                $obj->error = true;
                $obj->destinationFile = $destinationFile;
                $obj->pathFileName = $pathFileName;
                $obj->msg = "destinationFile is empty";
                _error_log("execOrder($format_order, $pathFileName, $destinationFile, $encoder_queue_id) destinationFile " . json_encode(debug_backtrace()));
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
                    _error_log($destinationFile . " already done, skip");
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
            $format_d = $o->getId();
            _error_log("execOrder: self::exec($format_d, $pathFileName, $destinationFile, $encoder_queue_id)");
            $obj = self::exec($format_d, $pathFileName, $destinationFile, $encoder_queue_id);
            if ($format_order == 50) {
                if (!$obj->error) {
                    // Spectrum.MP4 to WEBM
                    _error_log("runVideoToSpectrum: MP4 to WEBM");
                    $obj = static::execOrder(21, $obj->destinationFile, $destinationFile . ".webm", $encoder_queue_id);
                }
            }
            return $obj;
        }

        public static function getFromName($name)
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

        public static function createIfNotExists($name)
        {
            if (empty($name)) {
                return false;
            }
            _error_log("createIfNotExists($name) checking");
            $row = static::getFromName($name);
            if (empty($row)) {
                _error_log("createIfNotExists($name) not found, create a new one");
                $f = new Format("");
                $f->setName($name);
                $f->setExtension($name);
                $f->setCode("");
                $row['id'] = $f->save();
            }
            return $row['id'];
        }

        public function getId()
        {
            return $this->id;
        }

        public function getName()
        {
            return $this->name;
        }

        public function getCode()
        {
            return $this->code;
        }

        public function getCreated()
        {
            return $this->created;
        }

        public function getModified()
        {
            return $this->modified;
        }

        public function getExtension()
        {
            return $this->extension;
        }

        public function setId($id)
        {
            $this->id = $id;
        }

        public function setName($name)
        {
            global $global;
            /**
             * @var array $global
             */
            $this->name = $global['mysqli']->real_escape_string($name);
        }

        public function setCode($code)
        {
            global $global;
            /**
             * @var array $global
             */
            $this->code = $global['mysqli']->real_escape_string($code);
        }

        public function setCreated($created)
        {
            $this->created = $created;
        }

        public function setModified($modified)
        {
            $this->modified = $modified;
        }

        public function setExtension($extension)
        {
            global $global;
            /**
             * @var array $global
             */
            $this->extension = $global['mysqli']->real_escape_string($extension);
        }

        public function getExtension_from()
        {
            return $this->extension_from;
        }

        public function setExtension_from($extension_from)
        {
            $this->extension_from = $extension_from;
        }

        public function getOrder()
        {
            return $this->order;
        }

        public function setOrder($order)
        {
            $this->order = $order;
        }
    }
}
