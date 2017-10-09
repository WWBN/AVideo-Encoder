<?php
header('Access-Control-Allow-Origin: *');
$url = base64_decode($_GET['base64Url']);
$destinationFile = md5($url);
$destination = sys_get_temp_dir().DIRECTORY_SEPARATOR.$destinationFile;
$cache_life = '120'; //caching time, in seconds

if($_GET['format'] === 'png'){
    header('Content-Type: image/x-png');
    $destination .= ".".$_GET['format'];
    $exec = "ffmpeg -i {$url} -f image2 -vframes 1 -y {$destination}";
}else if($_GET['format'] === 'jpg'){
    header('Content-Type: image/jpg');
    $destination .= ".".$_GET['format'];
    $exec = "ffmpeg -i {$url} -f image2 -vframes 1 -y {$destination}";
}else if($_GET['format'] === 'gif'){
    header('Content-Type: image/gif');
    $destination .= ".".$_GET['format'];    
    //Generate a palette:
    $ffmpeg ="ffmpeg -y -t 3 -i {$url} -vf fps=10,scale=320:-1:flags=lanczos,palettegen {$destination}palette.png";
    error_log("Exec get Image palette: {$ffmpeg}");
    exec($ffmpeg);
    $exec ="ffmpeg -y -t 3 -i {$url} -i {$destination}palette.png -filter_complex \"fps=10,scale=320:-1:flags=lanczos[x];[x][1:v]paletteuse\" {$destination}";
}else{
    error_log("ERROR Destination get Image {$_GET['format']} not suported");
    die();
}

$filemtime = @filemtime($destination);  // returns FALSE if file does not exist
if(!$filemtime || (time() - $filemtime >= $cache_life) || !empty($_GET['renew'])){
    error_log("Exec get Image: {$exec}");
    shell_exec($exec);
}
error_log("Destination get Image {$_GET['format']}: {$destination}");
echo file_get_contents($destination);
die();