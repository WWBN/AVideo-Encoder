<?php

require_once dirname(__FILE__) . '/../videos/configuration.php';
header('Access-Control-Allow-Origin: *');
$max_execution_time = 2 * 3600;
ini_set('max_execution_time', $max_execution_time);
$pxBetweenTiles = 0;

$url = $argv[1];
$step = $argv[2];
$tileWidth = $argv[3];
$tileHeight = $argv[4];
$imageFileName = $argv[5];
$numberOfTiles = $argv[6];
$baseName = $argv[7];

$dirname = $global['systemRootPath'] . "videos/thumbs_{$baseName}/";
if (is_dir($dirname)) {
    if (time() - filemtime($dirname) > $max_execution_time) {
        // file older than 2 hours
        error_log("CreateSpirits:  request is older then 2 hours, we will try again");
    } else {
        error_log("CreateSpirits:  we are working on it, please wait");
        exit;
    }
}

error_log("CreateSpirits:  creating directory {$dirname}");
mkdir($dirname);

$width = $tileWidth + $pxBetweenTiles;
$height = $tileHeight + $pxBetweenTiles;
$mapWidth = ($tileWidth + $pxBetweenTiles) * 10;
$mapHeight = $tileHeight * (ceil($numberOfTiles / 10));

$cmd = "ffmpeg -i \"{$url}\" -vf fps=1/{$step} -s {$tileWidth}x{$tileHeight} {$dirname}out%03d.png";
error_log("CreateSpirits: $cmd");
//var_dump($duration, $videoLength);echo $cmd;exit;
exec($cmd);

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
        rmdir($file->getRealPath());
    } else {
        unlink($file->getRealPath());
    }
}

error_log("CreateSpirits:  removing directory {$dirname}");
rmdir($dirname);
/* * 
 */