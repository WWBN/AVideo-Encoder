<?php

if (!class_exists('Configuration')) {

    if (!class_exists('ObjectYPT')) {
        require_once 'Object.php';
    }

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
            if (empty($autodelete) || strtolower($autodelete) === 'false') {
                $autodelete = 0;
            } else {
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

        static function rewriteConfigFile() {
            global $global, $mysqlHost, $mysqlUser, $mysqlPass, $mysqlDatabase;
            $content = "<?php
\$global['configurationVersion'] = 2;
\$global['webSiteRootURL'] = '{$global['webSiteRootURL']}';
\$global['systemRootPath'] = '{$global['systemRootPath']}';
\$global['webSiteRootPath'] = '" . (@$global['webSiteRootPath']) . "';

\$global['disableConfigurations'] = " . intval($global['disableConfigurations']) . ";
\$global['disableBulkEncode'] = " . intval($global['disableBulkEncode']) . ";
\$global['disableWebM'] = " . intval($global['disableWebM']) . ";

\$mysqlHost = '{$mysqlHost}';
\$mysqlUser = '{$mysqlUser}';
\$mysqlPass = '{$mysqlPass}';
\$mysqlDatabase = '{$mysqlDatabase}';

\$global['allowed'] = array('" . implode("', '", $global['allowed']) . "');
/**
 * Do NOT change from here
 */
if(empty(\$global['webSiteRootPath'])){
    preg_match('/https?:\/\/[^\/]+(.*)/i', \$global['webSiteRootURL'], \$matches);
    if(!empty(\$matches[1])){
        \$global['webSiteRootPath'] = \$matches[1];
    }
}
if(empty(\$global['webSiteRootPath'])){
    die('Please configure your webSiteRootPath');
}

require_once \$global['systemRootPath'] . 'objects/include_config.php';
";

            $fp = fopen($global['systemRootPath'] . "videos/configuration.php", "wb");
            fwrite($fp, $content);
            fclose($fp);
        }

    }

}