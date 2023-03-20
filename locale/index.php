<?php
header('Content-Type: text/plain');

function listAll($dir)
{
    $vars = [];
    if (preg_match('/vendor.*$/', $dir)) {
        return $vars;
    }
    //echo $dir.'<br>';
    if ($handle = opendir($dir)) {
        while (false !== ($entry = readdir($handle))) {
            if ($entry !== '.' && $entry !== '..') {
                $filename = ($dir) . DIRECTORY_SEPARATOR . $entry;
                if (is_dir($filename)) {
                    $vars_dir = listAll($filename);
                    $vars = array_merge($vars, $vars_dir);
                } elseif (preg_match("/\.php$/", $entry)) {
                    //echo $entry.PHP_EOL;
                    $data = file_get_contents($filename);
                    $regex = '/__\(["\']{1}(.*)["\']{1}\)/U';
                    preg_match_all(
                        $regex,
                        $data,
                        $matches
                    );
                    foreach ($matches[0] as $key => $value) {
                        $vars[$matches[1][$key]] = $matches[1][$key];
                    }
                }
            }
        }
        closedir($handle);
    }
    return $vars;
}

$vars1 = listAll('../');
//var_dump($vars1);exit;
$vars2 = listAll('../view');
//var_dump($vars2);exit;
$vars3 = listAll('../objects');

$vars = array_merge($vars1, $vars2, $vars3);

sort($vars);
$str = '<?php
global $t;' . PHP_EOL;
foreach ($vars as $key => $value) {
    $value = addcslashes($value, "'");
    $str .= "\$t['{$value}'] = '{$value}';" . PHP_EOL;
}
echo $str;
/*
$directory = './';
$files = scandir($directory);

foreach ($files as $file) {
    if (!in_array($file, array('.', '..'))) {
        if($file!='index.php'){
            echo $file . "\n";
            file_put_contents($file, $str);
        }
    }
}
*/