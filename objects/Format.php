<?php

class Format extends Object {

    protected $id, $name, $code, $created, $modified, $extension, $extension_from;

    static function getSearchFieldsNames() {
        return array('name');
    }

    static function getTableName() {
        return 'formats';
    }

    // ffmpeg -i {$pathFileName} -vf scale=352:240 -vcodec h264 -acodec aac -strict -2 -y {$destinationFile}
    function run($pathFileName, $encoder_queue_id) {
        global $global;
        $obj = new stdClass();
        $obj->error = true;
        $path_parts = pathinfo($pathFileName);
        $destinationFile = $path_parts['dirname'] . "/" . $path_parts['filename'] . "_converted." . $path_parts['extension'];
        if ($this->id == 7) {
            $obj = $this->runVideoToSpectrum($pathFileName, $encoder_queue_id);
        } else if ($this->id == 8) {
            $obj = $this->runVideoToAudio($pathFileName, $encoder_queue_id);
        } elseif ($this->id == 9) {
            $obj = $this->runBothVideo($pathFileName, $encoder_queue_id);
        } else if ($this->id == 10) {
            $obj = $this->runBothAudio($pathFileName, $encoder_queue_id);
        } else {
            $obj = static::exec($this->id, $pathFileName, $destinationFile, $encoder_queue_id);
        }
        return $obj;
    }

    private function runVideoToSpectrum($pathFileName, $encoder_queue_id) {
        global $global;
        $path_parts = pathinfo($pathFileName);
        $destinationFile = $path_parts['dirname'] . "/" . $path_parts['filename'] . "_converted";

        // MP4 to MP3
        $obj = static::exec(6, $pathFileName, $destinationFile . ".mp3", $encoder_queue_id);
        if (!$obj->error) {
            //MP3 to Spectrum.MP4
            $obj = static::exec(5, $obj->destinationFile, $destinationFile . ".mp4", $encoder_queue_id);
            if (!$obj->error) {
                // Spectrum.MP4 to WEBM
                $obj = static::exec(2, $obj->destinationFile, $destinationFile . ".webm", $encoder_queue_id);
            }
        }
        return $obj;
    }

    private function runVideoToAudio($pathFileName, $encoder_queue_id) {
        $path_parts = pathinfo($pathFileName);
        $destinationFile = $path_parts['dirname'] . "/" . $path_parts['filename'] . "_converted";

        // MP4 to MP3
        $obj = static::exec(6, $pathFileName, $destinationFile . ".mp3", $encoder_queue_id);
        if (!$obj->error) {
            //MP4 to OGG
            $obj = static::exec(4, $pathFileName, $destinationFile . ".ogg", $encoder_queue_id);
        }
        return $obj;
    }
    
    private function runBothVideo($pathFileName, $encoder_queue_id) {
        $path_parts = pathinfo($pathFileName);
        $destinationFile = $path_parts['dirname'] . "/" . $path_parts['filename'] . "_converted";

        // Video to MP4
        $obj = static::exec(1, $pathFileName, $destinationFile . ".mp4", $encoder_queue_id);
        if (!$obj->error) {
            //MP4 to WEBM
            $obj = static::exec(2, $pathFileName, $destinationFile . ".webm", $encoder_queue_id);
        }
        return $obj;
    }
    
    private function runBothAudio($pathFileName, $encoder_queue_id) {
        $path_parts = pathinfo($pathFileName);
        $destinationFile = $path_parts['dirname'] . "/" . $path_parts['filename'] . "_converted";

        // Audio to MP3
        $obj = static::exec(3, $pathFileName, $destinationFile . ".mp3", $encoder_queue_id);
        if (!$obj->error) {
            //MP3 to OGG
            $obj = static::exec(4, $pathFileName, $destinationFile . ".ogg", $encoder_queue_id);
        }
        return $obj;
    }

    static private function exec($format_id, $pathFileName, $destinationFile, $encoder_queue_id) {
        global $global;
        $obj = new stdClass();
        $obj->error = true;
        $obj->destinationFile = $destinationFile;
        $obj->pathFileName = $pathFileName;
        $f = new Format($format_id);
        eval('$code ="' . $f->getCode() . '";');
        if (empty($code)) {
            $obj->msg = "Code not found ($format_id, $pathFileName, $destinationFile, $encoder_queue_id)";
        } else {
            $obj->code = $code;
            error_log("YouPHPTube-Encoder Start Encoder [{$code}] ");
            exec($code . " 1> {$global['systemRootPath']}videos/{$encoder_queue_id}_tmpFile_progress.txt  2>&1", $output, $return_val);
            if ($return_val !== 0) {
                error_log($code . "\n" . print_r($output, true)." ($format_id, $pathFileName, $destinationFile, $encoder_queue_id) ");
                $obj->msg = print_r($output, true);
            } else {
                $obj->error = false;
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

}
