<?php

class Streamer extends Object {

    protected $id, $siteURL, $user, $pass, $priority, $created, $modified;

    protected static function getSearchFieldsNames() {
        return array('siteURL');
    }

    protected static function getTableName() {
        return 'streamers';
    }
        
    private static function get($user, $pass, $siteURL){
        global $global;
        $sql = "SELECT * FROM  " . static::getTableName() . " WHERE user = '{$user}' AND pass = '{$pass}' AND lower(siteURL) = lower('{$siteURL}') LIMIT 1";

        $res = $global['mysqli']->query($sql);
        if ($res) {
            return $res->fetch_assoc();
        } else {
            die($sql . '\nError : (' . $global['mysqli']->errno . ') ' . $global['mysqli']->error);
        }
        return false;
    }
    
    static function createIfNotExists($user, $pass, $siteURL){
        if($row = static::get($user, $pass, $siteURL)){
            if(!empty($row['id'])){
                return $row['id'];
            }
        }
        $s = new Streamer('');
        $s->setUser($user);
        $s->setPass($pass);
        $s->setSiteURL($siteURL);
        $s->setPriority(3);
        return $s->save();
    }
    
    function getId() {
        return $this->id;
    }

    function getSiteURL() {
        return $this->siteURL;
    }

    function getUser() {
        return $this->user;
    }

    function getPass() {
        return $this->pass;
    }

    function getCreated() {
        return $this->created;
    }

    function getModified() {
        return $this->modified;
    }

    function setId($id) {
        $this->id = $id;
    }

    function setSiteURL($siteURL) {
        $this->siteURL = $siteURL;
    }

    function setUser($user) {
        $this->user = $user;
    }

    function setPass($pass) {
        $this->pass = $pass;
    }

    function setCreated($created) {
        $this->created = $created;
    }

    function setModified($modified) {
        $this->modified = $modified;
    }

    function getPriority() {
        return $this->priority;
    }

    function setPriority($priority) {
        $this->priority = $priority;
    }




}
