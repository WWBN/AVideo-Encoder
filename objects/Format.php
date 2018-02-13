<?php

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
        $sql = "SELECT * FROM ".static::getTableName()." WHERE  `order` = $order LIMIT 1";
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
            error_log("runVideoToSpectrum");
            $obj = $this->runVideoToSpectrum($pathFileName, $encoder_queue_id);
        } else if ($this->order == 71) {
            error_log("runVideoToAudio");
            $obj = $this->runVideoToAudio($pathFileName, $encoder_queue_id);
        } elseif ($this->order == 72) {
            error_log("runBothVideo");
            $obj = $this->runBothVideo($pathFileName, $encoder_queue_id);
        } else if ($this->order == 73) {
            error_log("runBothAudio");
            $obj = $this->runBothAudio($pathFileName, $encoder_queue_id, $this->id);
        }else if (in_array($this->order, $global['multiResolutionOrder'])) {
            error_log("runMultiResolution");
            $obj = $this->runMultiResolution($pathFileName, $encoder_queue_id,$this->order);
        } else {
            error_log("execOrder {$this->order}");
            $destinationFile = $path_parts['dirname'] . "/" . $path_parts['filename'] . "_converted." . $path_parts['extension'];
            $obj = static::execOrder($this->order, $pathFileName, $destinationFile, $encoder_queue_id);
        }
        return $obj;
    }

    private function runMultiResolution($pathFileName, $encoder_queue_id, $order) {
        global $global;
        $path_parts = pathinfo($pathFileName);
        $destinationFile = $path_parts['dirname'] . "/" . $path_parts['filename'] . "_converted";
        
        if(in_array($order, $global['hasHDOrder'])){
            $obj = static::execOrder(12, $pathFileName, $destinationFile . "_HD.mp4", $encoder_queue_id);
            if(in_array($order, $global['bothVideosOrder'])){ // make the webm too
                $obj = static::execOrder(22, $pathFileName, $destinationFile . "_HD.webm", $encoder_queue_id);
            }
        }
        if(in_array($order, $global['hasSDOrder'])){
            $obj = static::execOrder(11, $pathFileName, $destinationFile . "_SD.mp4", $encoder_queue_id);
            if(in_array($order, $global['bothVideosOrder'])){ // make the webm too
                $obj = static::execOrder(21, $pathFileName, $destinationFile . "_SD.webm", $encoder_queue_id);
            }
        }
        if(in_array($order, $global['hasLowOrder'])){
            $obj = static::execOrder(10, $pathFileName, $destinationFile . "_Low.mp4", $encoder_queue_id);
            if(in_array($order, $global['bothVideosOrder'])){ // make the webm too
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
        $obj = static::execOrder(60, $pathFileName, $destinationFile . ".mp3", $encoder_queue_id);
        if (!$obj->error) {
            //MP3 to Spectrum.MP4
            $obj = static::execOrder(50, $obj->destinationFile, $destinationFile . ".mp4", $encoder_queue_id);
            if (!$obj->error) {
                // Spectrum.MP4 to WEBM
                $obj = static::execOrder(21, $obj->destinationFile, $destinationFile . ".webm", $encoder_queue_id);
            }
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
    
    static private function execOrder($format_order, $pathFileName, $destinationFile, $encoder_queue_id) {
        $o = new Format(0);
        $o->loadFromOrder($format_order);
        return self::exec($o->getId(), $pathFileName, $destinationFile, $encoder_queue_id);
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
