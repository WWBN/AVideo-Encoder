<?php

require_once dirname(__FILE__) . '/../videos/configuration.php';
require_once '../objects/Encoder.php';
header('Access-Control-Allow-Origin: *');

//https://stackoverflow.com/questions/30429383/combine-16-images-into-1-big-image-with-php

function tempdir() {
    global $global;
    $tempfile= tempnam($global['systemRootPath'],'');
    // you might want to reconsider this line when using this snippet.
    // it "could" clash with an existing directory and this line will
    // try to delete the existing one. Handle with caution.
    if (file_exists($tempfile)) { unlink($tempfile); }
    mkdir($tempfile);
    if (is_dir($tempfile)) { return $tempfile; }
}

if(empty($_GET['totalClips'])){
    $_GET['totalClips'] = 100;
}

$url = base64_decode($_GET['base64Url']);

//$url = "http://127.0.0.1/YouPHPTube/videos/_YPTuniqid_5a01ef79b04ec6.24051213_HD.mp4";

$tileWidth = $_GET['tileWidth'];
$numberOfTiles = $_GET['totalClips'];

$tileHeight = intval($tileWidth/16*9);
$pxBetweenTiles = 0;

$duration = Encoder::getDurationFromFile($url);
$videoLength = parseDurationToSeconds($duration);


$width = $tileWidth+$pxBetweenTiles;
$height = $tileHeight+$pxBetweenTiles;
$step = $videoLength / $numberOfTiles;
$mapWidth = ($tileWidth + $pxBetweenTiles) * 10;
$mapHeight = $tileHeight * (ceil($numberOfTiles/10));

$id = uniqid();
$dirname = $global['systemRootPath']."videos/tail{$id}/";

mkdir($dirname);

$cmd = "ffmpeg -i {$url} -vf fps=1/{$step}  -s {$tileWidth}x{$tileHeight} {$dirname}out%03d.png";

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
    imagecopy($mapImage, $tileImg, $width*($index%10), $height*(floor($index/10)), 0, 0, $tileWidth, $tileHeight);
    imagedestroy($tileImg);
}

/* * OUTPUT THUMBNAIL IMAGE */
header("Content-type: image/jpeg");
imagejpeg($mapImage); //change argument to $mapImage to output the original size image


$it = new RecursiveDirectoryIterator($dirname, RecursiveDirectoryIterator::SKIP_DOTS);
$files = new RecursiveIteratorIterator($it,
             RecursiveIteratorIterator::CHILD_FIRST);
foreach($files as $file) {
    if ($file->isDir()){
        rmdir($file->getRealPath());
    } else {
        unlink($file->getRealPath());
    }
}
rmdir($dirname);
/* * 
 */