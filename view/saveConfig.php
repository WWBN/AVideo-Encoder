<?php
header('Content-Type: application/json');
require_once dirname(__FILE__) . '/../videos/configuration.php';
require_once '../objects/Format.php';

$obj = new stdClass();
$obj->error = true;
if(empty($global['disableConfigurations'])){
    if (!empty($_POST['formats'])) {
        foreach ($_POST['formats'] as $value) {
            $id = $value[0];
            if(empty($id)){
                continue;
            }
            $f = new Format($id);
            $f->setCode($value[1]);
            if($f->save()){
                $obj->error = false;
            }

        }
    } else {
        $obj->msg = "formats not found";
    }
}else{
    $obj->msg = "Configuration is disabled";
}
echo json_encode($obj);
