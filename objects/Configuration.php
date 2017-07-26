<?php
class Configuration extends Object {
    
    protected $allowedStreamersURL, $defaultPriority; 
    
    protected static function getSearchFieldsNames() {
        return array('allowedStreamersURL');
    }

    protected static function getTableName() {
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


}
    