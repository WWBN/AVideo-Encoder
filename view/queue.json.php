<?php
require_once dirname(__FILE__) . '/../videos/configuration.php';
require_once '../objects/Encoder.php';
require_once '../objects/Login.php';
header('Content-Type: application/json');
$rows = Encoder::getAll();
$totalSkiped = 0;
foreach ($rows as $key=>$value) {
    if(!Login::isAdmin()){
        if(Login::getStreamerId() != $rows[$key]['streamers_id']){
            unset($rows[$key]);
            $totalSkiped++;
            continue;
        }
    }    
    $f = new Format($rows[$key]['formats_id']);
    $rows[$key]['format']= $f->getName();
}
$total = Encoder::getTotal();

echo '{  "current": '.$_POST['current'].',"rowCount": '.$_POST['rowCount'].', "total": '.($total-$totalSkiped).', "rows":'. json_encode($rows).'}';