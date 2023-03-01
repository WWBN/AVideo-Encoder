<?php

require_once $global['systemRootPath'] . 'objects/Encoder.php';
require_once $global['systemRootPath'] . 'objects/Login.php';
require_once $global['systemRootPath'] . 'objects/Streamer.php';
require_once $global['systemRootPath'] . 'objects/functions.php';

class Upload extends ObjectYPT
{

    protected $id, $encoders_id, $resolution, $format, $videos_id, $status;

    static function getTableName()
    {
        global $global;
        return $global['tablesPrefix'] . 'upload_queue';
    }

    static function getSearchFieldsNames()
    {
        return array();
    }

    static function loadFromEncoder($encoders_id, $resolution, $format)
    {
        global $global;
        $sql = "SELECT * FROM " . static::getTableName() . " WHERE  `encoders_id` = $encoders_id AND `resolution` = '$resolution' AND `format`= '$format' LIMIT 1";
        $global['lastQuery'] = $sql;
        $res = $global['mysqli']->query($sql);
        if ($res === false)
            return false;

        $row = $res->fetch_assoc();
        if (empty($row['id']))
            return false;

        $u = new Upload($row['id']);
        foreach ($row as $key => $value)
            $u->$key = $value;

        return $u;
    }

    static function create($encoders_id, $file)
    {
        preg_match("/tmpFile_converted_([^.]+)\.(.*)$/", $file, $matches);
        if (empty($matches[1]) || empty($matches[2])) {
            error_log("Upload::createIfNotExists filename " . $file . " not match");
            return false;
        }

        $resolution = $matches[1];
        $format = $matches[2];

        $e = new Encoder($encoders_id);
        $return_vars = json_decode($e->getReturn_vars());
        if (empty($return_vars->videos_id)) {
            error_log("Upload::createIfNotExists no videos_id");
            return false;
        }

        $u = new Upload("");
        $u->setEncoders_id($encoders_id);
        $u->setResolution($resolution);
        $u->setFormat($format);
        $u->setVideos_id($return_vars->videos_id);
        $u->setStatus(Encoder::$STATUS_QUEUE);
        $u->save();

        $sent = Encoder::sendFile($file, $return_vars, $format, $e, $resolution);

        return $u;
    }

    static function deleteFile($encoders_id)
    {
        global $global;

        $sql = "SELECT * FROM " . static::getTableName() . " WHERE  `encoders_id` = $encoders_id";
        $global['lastQuery'] = $sql;
        $res = $global['mysqli']->query($sql);
        if ($res === false)
            return false;

        while ($row = $res->fetch_assoc()) {
            $u = new Upload($row['id']);
            $u->delete();
        }

        return true;
    }

    function getId()
    {
        return $this->id;
    }

    function getEncoder_id()
    {
        return $this->encoders_id;
    }

    function getResolution()
    {
        return $this->resolution;
    }

    function getFormat()
    {
        return $this->format;
    }

    function getVideos_id()
    {
        return $this->videos_id;
    }

    function getStatus()
    {
        return $this->status;
    }

    function setEncoders_id($encoders_id)
    {
        $this->encoders_id = $encoders_id;
    }

    function setResolution($resolution)
    {
        $this->resolution = $resolution;
    }

    function setFormat($format)
    {
        $this->format = $format;
    }

    function setVideos_id($videos_id)
    {
        $this->videos_id = $videos_id;
    }

    function setStatus($status)
    {
        $this->status = $status;
    }
}
