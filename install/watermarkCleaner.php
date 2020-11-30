<?php

$deleteFilesOlderThanHours = 12; 

require_once '../videos/configuration.php';

if (!isCommandLineInterface()) {
    return die('Command Line only');
}

$watermarkDir = $global['systemRootPath'] . 'videos/watermarked/';

if (is_dir($watermarkDir)) {
    searchObjLog($watermarkDir);
}

function searchObjLog($dir) {
    echo "Searching {$dir}".PHP_EOL;
    $jsonFile = "{$dir}.obj.log";
    if (file_exists($jsonFile)) {
         echo "Found {$jsonFile}".PHP_EOL;
        $json = json_decode(file_get_contents($jsonFile));
        if (is_object($json)) {
            $remainingTime = $json->lastUpdate - strtotime("-{$deleteFilesOlderThanHours} hours");
            if ($remainingTime<0) {
                $cmd = "rm -rf {$dir}";
                echo "Remove {$cmd}".PHP_EOL;
                exec($cmd);
            }else{
                echo "Wait {$remainingTime} seconds".PHP_EOL;
            }
        }
    } else {
        if ($dh = opendir($dir)) {
            while (($file = readdir($dh)) !== false) {
                if($file=='.' || $file == '..'){
                    continue;
                }
                $newDir = "{$dir}{$file}/";
                if(is_dir($newDir)){
                    searchObjLog($newDir);
                }
            }
            closedir($dh);
        }
    }
}
