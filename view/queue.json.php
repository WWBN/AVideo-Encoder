<?php

require_once dirname(__FILE__) . '/../videos/configuration.php';
require_once '../objects/Encoder.php';
require_once '../objects/Streamer.php';
require_once '../objects/Login.php';
header('Content-Type: application/json');
$rows = Encoder::getAll(true);
$resolutions = array('Low', 'SD', 'HD');
foreach ($rows as $key => $value) {
    $f = new Format($rows[$key]['formats_id']);
    $rows[$key]['format'] = $f->getName();
    $s = new Streamer($rows[$key]['streamers_id']);
    $rows[$key]['streamer'] = $s->getSiteURL();
    foreach ($resolutions as $value2) {
        $file = "{$global['systemRootPath']}videos/{$rows[$key]['id']}_tmpFile_converted_{$value2}.mp4";
        if (file_exists($file)) {
            $rows[$key]['mp4_filesize_' . $value2] = filesize($file);
            $rows[$key]['mp4_filesize_human_' . $value2] = humanFileSize($rows[$key]['mp4_filesize_' . $value2]);
        }

        $file = "{$global['systemRootPath']}videos/{$rows[$key]['id']}_tmpFile_converted_{$value2}.webm";
        if (file_exists($file)) {
            $rows[$key]['webm_filesize_' . $value2] = filesize($file);
            $rows[$key]['webm_filesize_human_' . $value2] = humanFileSize($rows[$key]['webm_filesize_' . $value2]);
        }
    }
}
$rows = array_values($rows);
$total = Encoder::getTotal(true);

if (empty($_POST['rowCount']) && !empty($total)) {
    $_POST['rowCount'] = $total;
}

echo '{  "current": ' . $_POST['current'] . ',"rowCount": ' . $_POST['rowCount'] . ', "total": ' . ($total) . ', "rows":' . json_encode($rows) . '}';
