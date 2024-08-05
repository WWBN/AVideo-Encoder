<?php

class ServerMonitor
{
    public static function getMemoryLinux($obj)
    {
        $obj->command = "free";
        exec($obj->command . "  2>&1", $output, $return_val);
        if ($return_val !== 0) {
            $obj->error = "Get Memory ERROR** " . print_r($output, true);
        } else {
            $obj->output = $output;

            if (preg_match("/Mem: *([0-9]+) *([0-9]+) *([0-9]+) */i", $output[1], $match)) {
                $obj->memTotalBytes = $match[1]*1024;
                $obj->memUsedBytes = $match[2]*1024;
                $obj->memFreeBytes = $match[3]*1024;
            } else {
                $obj->error = "Get Memory ERROR** " . print_r($output, true);
            }
        }
        return $obj;
    }

    public static function getMemoryNetBSD($obj)
    {
        $obj->command = "/sbin/sysctl hw.pagesize; /usr/bin/vmstat -t";
        exec($obj->command . "  2>&1", $output, $return_val);
        if ($return_val !== 0) {
            $obj->error = "Get Memory ERROR** (".$obj->command." failed)";
        } else {
            $obj->output = $output;

            $parts = explode(" = ", $output[0]);
            if ($parts[0] != "hw.pagesize") {
                $obj->error = "Get Memory ERROR** (unknown page size)";
            } elseif (($match = preg_split("/ +/", trim($output[3]))) === false) {
                $obj->error = "Get Memory ERROR** (unepxected vmstat output)";
            } elseif (!is_numeric($match[4]) || !is_numeric($match[5]) || !is_numeric($match[11])) {
                $obj->error = "Get Memory ERROR** (non numeric memory size?)";
            } else {
                $page_size = $parts[1];
                $obj->memTotalBytes = $match[4] * $page_size;
                $obj->memUsedBytes = $match[5] * $page_size;
                $obj->memFreeBytes = $match[11] * $page_size;
            }
        }

        if (!empty($obj->error)) {
            $obj->error .= " " . print_r($output, true);
        }

        return $obj;
    }

    public static function getMemory()
    {
        $obj = new stdClass();
        $os = php_uname("s");
        $getMemoryOsFunction = "getMemory" . $os;

        if (!method_exists("ServerMonitor", $getMemoryOsFunction)) {
            $obj->error = "Get Memory error: ".$os." not supported";
        } else {
            $obj = ServerMonitor::$getMemoryOsFunction($obj);
        }

        if (empty($obj->error)) {
            $obj->success = 1;
            $onePc = $obj->memTotalBytes / 100;
            $obj->memTotal = self::humanFileSize($obj->memTotalBytes);
            $obj->memUsed = self::humanFileSize($obj->memUsedBytes);
            $obj->memFree = self::humanFileSize($obj->memFreeBytes);
            $obj->percent = intval($obj->memUsedBytes / $onePc);
            $obj->title = "Total: {$obj->memTotal} | Free: {$obj->memFree} | Used: {$obj->memUsed}";
        }
        return $obj;
    }

    public static function humanFileSize($size, $unit = "")
    {
        if ((!$unit && $size >= 1 << 30) || $unit == "GB") {
            return number_format($size / (1 << 30), 2) . "GB";
        }
        if ((!$unit && $size >= 1 << 20) || $unit == "MB") {
            return number_format($size / (1 << 20), 2) . "MB";
        }
        if ((!$unit && $size >= 1 << 10) || $unit == "KB") {
            return number_format($size / (1 << 10), 2) . "KB";
        }
        return number_format($size) . " bytes";
    }
}
