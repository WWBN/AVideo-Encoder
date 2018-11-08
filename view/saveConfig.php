<?php

header('Content-Type: application/json');
require_once dirname(__FILE__) . '/../videos/configuration.php';
require_once '../objects/Format.php';
require_once '../objects/Configuration.php';
require_once '../objects/Login.php';

$obj = new stdClass();
$obj->error = true;

if(!Login::isAdmin()){
    $obj->msg = "You are not admin";
    die(json_encode($obj));
}

if (empty($global['disableConfigurations'])) {
    if (!empty($_POST['formats'])) {
        foreach ($_POST['formats'] as $value) {
            if (empty($value)) {                
                continue;
            }
            $id = $value[0];
            if (empty($id)) {
                continue;
            }
            $f = new Format($id);
            $f->setCode($value[1]);
            if ($f->save()) {
                $obj->error = false;
            }
        }
        $config = new Configuration();
        $config->setAllowedStreamersURL($_POST['allowedStreamers']);
        $config->setDefaultPriority($_POST['defaultPriority']);
        $config->setAutodelete($_POST['autodelete']);
        $config->save();
    } else {
        $obj->msg = "formats not found";
    }
} else {
    $obj->msg = "Configuration is disabled";
}
echo json_encode($obj);
