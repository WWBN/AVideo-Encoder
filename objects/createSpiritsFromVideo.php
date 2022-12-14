<?php

require_once dirname(__FILE__) . '/../videos/configuration.php';
header('Access-Control-Allow-Origin: *');
$max_execution_time = 2 * 3600;
ini_set('max_execution_time', $max_execution_time);
$pxBetweenTiles = 0;

$url = $argv[1];
$step = floatval($argv[2]);
$tileWidth = $argv[3];
$tileHeight = $argv[4];
$imageFileName = $argv[5];
$numberOfTiles = $argv[6];
$baseName = $argv[7];

if ($step <= 0) {
    $step = 0.01;
}

$dirname = $global['systemRootPath'] . "videos/thumbs_{$baseName}/";
if (is_dir($dirname)) {
    $dirSize = dirSize($dirname);
    if (time() - filemtime($dirname) > $max_execution_time) {
        // file older than 2 hours
        error_log("CreateSpirits:  request is older then 2 hours, we will try again");
    } else {
        error_log("CreateSpirits:  we are working on it, dirsize={$dirSize}, please wait {$dirname}");
        exit;
    }
}

$url = str_replace('https://gdrive.local/', 'http://192.168.1.4/', $url);
/**
  if(!isURL200($url)){
  $headers = get_headers($url);
  error_log("URL $url is not 200 code ". json_encode($headers));
  return false;
  }
 * 
 */
error_log("CreateSpirits:  creating directory {$dirname}");
$created = make_path($dirname);

if (empty($created) || !is_dir($dirname)) {
    try {
        mkdir($dirname, 0777, true);
        error_log("CreateSpirits: NO mkdir error ");
    } catch (ErrorException $ex) {
        error_log("CreateSpirits: ERROR " . $ex->getMessage());
    }
}

if (!is_dir($dirname)) {
    error_log("CreateSpirits: Could not create dir " . json_encode(error_get_last()));
    return false;
}

$width = $tileWidth + $pxBetweenTiles;
$height = $tileHeight + $pxBetweenTiles;
$mapWidth = ($tileWidth + $pxBetweenTiles) * 10;
$mapHeight = $tileHeight * (ceil($numberOfTiles / 10));

$cmd = get_ffmpeg() . " -i \"{$url}\" -map 0:v:0 -vf fps=1/{$step},"
        . getFFmpegScaleToForceOriginalAspectRatio($tileWidth, $tileHeight) . " "
        . " \"{$dirname}out%03d.png\"  2>&1 ";

$cmd = removeUserAgentIfNotURL($cmd);
error_log("CreateSpirits: $cmd");
//var_dump($duration, $videoLength);echo $cmd;exit;
exec($cmd, $output, $return_var);
//error_log("CreateSpirits: ". json_encode($output));
//error_log("CreateSpirits: ". json_encode($return_var));
$dirSize = dirSize($dirname);
if($dirSize<100000){
    error_log("CreateSpirits: ERROR on dirsize={$dirSize} {$cmd}");
    return false;
}

$images = glob($dirname . "*.png");
$srcImagePaths = Array();

foreach ($images as $image) {
    $srcImagePaths[] = $image;
}
//var_dump($dirname, $images, $srcImagePaths);exit;
//https://stackoverflow.com/questions/30429383/combine-16-images-into-1-big-image-with-php

$mapImage = imagecreatetruecolor($mapWidth, $mapHeight);
$bgColor = imagecolorallocate($mapImage, 50, 40, 0);
imagefill($mapImage, 0, 0, $bgColor);

/* * COPY SOURCE IMAGES TO MAP */
foreach ($srcImagePaths as $index => $srcImagePath) {
    $tileImg = imagecreatefrompng($srcImagePath);
    imagecopy($mapImage, $tileImg, $width * ($index % 10), $height * (floor($index / 10)), 0, 0, $tileWidth, $tileHeight);
    imagedestroy($tileImg);
}

/* * SAVE THUMBNAIL IMAGE */
imagejpeg($mapImage, $imageFileName); //change argument to $mapImage to output the original size image


$it = new RecursiveDirectoryIterator($dirname, RecursiveDirectoryIterator::SKIP_DOTS);
$files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
foreach ($files as $file) {
    if ($file->isDir()) {
        //rmdir($file->getRealPath());
    } else {
        //unlink($file->getRealPath());
    }
}

error_log("CreateSpirits:  removing directory {$dirname}");
//rmdir($dirname);
/* * 
 */
