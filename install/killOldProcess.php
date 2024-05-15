<?php

if (php_sapi_name() !== 'cli') {
    die('Must be command line');
}

$cmd = 'ps -u www-data -o pid,etime,cmd';

$output = shell_exec($cmd);
foreach(preg_split("/((\r?\n)|(\r\n?))/", $output) as $line) {
    if (strpos($line, '-f image2 -s') !== false) {
        echo $line . PHP_EOL;
        preg_match('/([0-9]+) +([0-9:]+)/', $line, $matches);
        $etime = intval(str_replace(':', '', $matches[2]));
        if ($etime > 100) { // 1 minute
            $pid = intval($matches[1]);
            if (!empty($pid)) {
                echo "Killing {$pid}" . PHP_EOL;
                error_log("Killing {$pid}");
                $cmd = 'kill -9 '.$pid;
                echo shell_exec($cmd);
                continue;
            }
        }
    }
    if (strpos($line, '-y -ss 3 -t 3') !== false) {
        echo $line . PHP_EOL;
        preg_match('/([0-9]+) +([0-9:]+)/', $line, $matches);
        $etime = intval(str_replace(':', '', $matches[2]));
        if ($etime>100) { // 1 minute
            $pid = intval($matches[1]);
            if (!empty($pid)) {
                echo "Killing {$pid}" . PHP_EOL;
                error_log("Killing {$pid}");
                $cmd = 'kill -9 ' . $pid;
                echo shell_exec($cmd);
                continue;
            }
        }
    }
}
