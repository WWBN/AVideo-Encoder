<?php
class Configuration extends ObjectYPT {
    
    protected $allowedStreamersURL, $defaultPriority, $version, $autodelete; 
    
    static function getSearchFieldsNames() {
        return array('allowedStreamersURL');
    }

    static function getTableName() {
        return 'configurations';
    }

    function __construct() {
        $this->load(1);
    }
    
    function getAllowedStreamersURL() {
        return $this->allowedStreamersURL;
    }

    function getDefaultPriority() {
        return $this->defaultPriority;
    }

    function setAllowedStreamersURL($allowedStreamersURL) {
        $this->allowedStreamersURL = $allowedStreamersURL;
    }

    function setDefaultPriority($defaultPriority) {
        $this->defaultPriority = $defaultPriority;
    }

    function getVersion() {
        return $this->version;
    }

    function setVersion($version) {
        $this->version = $version;
    }
    
    function getAutodelete() {
        return $this->autodelete;
    }

    function setAutodelete($autodelete) {
        if(empty($autodelete) || strtolower($autodelete) === 'false'){
            $autodelete = 0;
        }else{
            $autodelete = 1;
        }
        $this->autodelete = $autodelete;
    }
        
    function currentVersionLowerThen($version) {
        return version_compare($version, $this->getVersion()) > 0;
    }

    function currentVersionGreaterThen($version) {
        return version_compare($version, $this->getVersion()) < 0;
    }

    function currentVersionEqual($version) {
        return version_compare($version, $this->getVersion()) == 0;
    }


}
    