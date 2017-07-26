<?php
require_once dirname(__FILE__) . '/Configuration.php';
class Streamer extends Object {

    protected $id, $siteURL, $user, $pass, $priority, $isAdmin, $created, $modified;

    protected static function getSearchFieldsNames() {
        return array('siteURL');
    }

    protected static function getTableName() {
        return 'streamers';
    }
        
    private static function get($user, $pass, $siteURL){
        global $global;
        $sql = "SELECT * FROM  " . static::getTableName() . " WHERE user = '{$user}' AND pass = '{$pass}' AND lower(siteURL) = lower('{$siteURL}') LIMIT 1";
        //echo $sql;exit;
        $res = $global['mysqli']->query($sql);
        if ($res) {
            return $res->fetch_assoc();
        } else {
            die($sql . '\nError : (' . $global['mysqli']->errno . ') ' . $global['mysqli']->error);
        }
        return false;
    }
        
    private static function getFirst(){
        global $global;
        $sql = "SELECT * FROM  " . static::getTableName() . " LIMIT 1";

        $res = $global['mysqli']->query($sql);
        if ($res) {
            return $res->fetch_assoc();
        } else {
            die($sql . '\nError : (' . $global['mysqli']->errno . ') ' . $global['mysqli']->error);
        }
        return false;
    }
    
    static function getFirstURL(){
        $row = static::getFirst();
        return $row['siteURL'];
    }
    
    static function createIfNotExists($user, $pass, $siteURL){
        if (substr($siteURL, -1) !== '/') {
            $siteURL .= "/";
        }
        if($row = static::get($user, $pass, $siteURL)){
            if(!empty($row['id'])){
                return $row['id'];
            }
        }
        
        if(static::isURLAllowed($siteURL)){
            $s = new Streamer('');
            $s->setUser($user);
            $s->setPass($pass);
            $s->setSiteURL($siteURL);
            $s->setPriority($config->getDefaultPriority());
            return $s->save();
        }else{
            return false;
        }
    }
    
    static function isURLAllowed($siteURL){
        $config = new Configuration();
        $allowed = explode(PHP_EOL, $config->getAllowedStreamersURL());
        $return = false;
        if(empty($allowed)){
            $return = true;
        }else{
            foreach ($allowed as $value){
                if(empty($value)){
                    continue;
                }
                $value = trim($value);
                if (substr($value, -1) !== '/') {
                    $value .= "/";
                }
                //var_dump($siteURL,$value);
                if($siteURL==$value){
                    $return = true;
                    break;
                }
            }
        }
        return $return;
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
        if (!empty($siteURL) && substr($siteURL, -1) !== '/') {
            $siteURL .= "/";
        }
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

    function getIsAdmin() {
        return $this->isAdmin;
    }

    function setIsAdmin($isAdmin) {
        $this->isAdmin = $isAdmin;
    }




}
