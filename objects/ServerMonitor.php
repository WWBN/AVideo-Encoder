<?php

class ServerMonitor {

    static function getMemory() {
        $obj = new stdClass();
        $cmd = "free";
        exec($cmd . "  2>&1", $output, $return_val);
        if ($return_val !== 0) {
            $obj->error = "Get Memmory ERROR** " . print_r($output, true);
            $obj->command = $cmd;
        } else {
            $obj->title = "";
            $obj->success = 1;
            $obj->output = $output;
            $obj->command = $cmd;
            $obj->memTotalBytes = 0;
            $obj->memUsedBytes = 0;
            $obj->memFreeBytes = 0;
            if (preg_match("/Mem: *([0-9]+) *([0-9]+) *([0-9]+) */i", $output[1], $match)) {
                $obj->memTotalBytes = $match[1]*1024;
                $obj->memUsedBytes = $match[2]*1024;
                $obj->memFreeBytes = $match[3]*1024;
                $onePc = $obj->memTotalBytes / 100;
                $obj->memTotal = self::humanFileSize($obj->memTotalBytes);
                $obj->memUsed = self::humanFileSize($obj->memUsedBytes);
                $obj->memFree = self::humanFileSize($obj->memFreeBytes);
                $obj->percent = intval($obj->memUsedBytes / $onePc);
                $obj->title = "Total: {$obj->memTotal} | Free: {$obj->memFree} | Used: {$obj->memUsed}";
            }
        }
        return $obj;
    }

    static function humanFileSize($size, $unit = "") {
        if ((!$unit && $size >= 1 << 30) || $unit == "GB")
            return number_format($size / (1 << 30), 2) . "GB";
        if ((!$unit && $size >= 1 << 20) || $unit == "MB")
            return number_format($size / (1 << 20), 2) . "MB";
        if ((!$unit && $size >= 1 << 10) || $unit == "KB")
            return number_format($size / (1 << 10), 2) . "KB";
        return number_format($size) . " bytes";
    }

}
