<?php

require_once dirname(__FILE__) . '/../videos/configuration.php';
header('Access-Control-Allow-Origin: *');
$max_execution_time = 2 * 3600;
ini_set('max_execution_time', $max_execution_time);
$pxBetweenTiles = 0;

// Started only by getSpiritsFromVideo.php through the PHP CLI; the tile sizes reach a shell command.
if (php_sapi_name() !== 'cli') {
    die();
}
$url = $argv[1];
$step = floatval($argv[2]);
$tileWidth = intval($argv[3]);
$tileHeight = intval($argv[4]);
$imageFileName = $argv[5];
$numberOfTiles = intval($argv[6]);
$baseName = preg_replace('/[^a-f0-9]/i', '', $argv[7]);
$force = intval(@$argv[8]);

if ($step <= 0) {
    $step = 0.01;
}

$dirname = $global['systemRootPath'] . "videos/thumbs_{$baseName}/";
if (is_dir($dirname) && empty($force)) {
    $dirSize = dirSize($dirname);
    if (time() - filemtime($dirname) > $max_execution_time) {
        // file older than 2 hours
        error_log("CreateSpirits:  request is older then 2 hours, we will try again");
    } else {
        error_log("CreateSpirits:  we are working on it, dirsize={$dirSize}, please wait {$dirname}");
        exit;
    }
}

$url = str_replace('https://gdrive.local/', 'http://192.168.0.2/', $url);
/**
  if (!isURL200($url)){
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

// frames left by an older run would be mixed with the new ones
foreach (glob($dirname . "*.png") as $oldImage) {
    @unlink($oldImage);
}
@unlink("{$imageFileName}.failed");

$width = $tileWidth + $pxBetweenTiles;
$height = $tileHeight + $pxBetweenTiles;
$mapWidth = ($tileWidth + $pxBetweenTiles) * 10;
$mapHeight = $tileHeight * (ceil($numberOfTiles / 10));

$urlEscaped = escapeshellarg($url);
$outPatternEscaped = escapeshellarg("{$dirname}out%03d.png");
$cmd = get_ffmpeg() . " -i {$urlEscaped} -map 0:v:0 -vf fps=1/{$step},"
        . getFFmpegScaleToForceOriginalAspectRatio($tileWidth, $tileHeight) .
        " -frames:v 100 -y "
        . " {$outPatternEscaped}  2>&1 ";

$cmd = removeUserAgentIfNotURL($cmd);
error_log("CreateSpirits: $cmd");
//var_dump($duration, $videoLength);echo $cmd;exit;
exec($cmd, $output, $return_var);
//error_log("CreateSpirits: ". json_encode($output));
//error_log("CreateSpirits: ". json_encode($return_var));
$dirSize = dirSize($dirname);
if ($dirSize<100000){
    error_log("CreateSpirits: ERROR on dirsize={$dirSize} {$cmd} " . json_encode(array_slice($output, -5)));
    // getSpiritsFromVideo.php answers nothing for a while instead of the "creating images" placeholder
    file_put_contents("{$imageFileName}.failed", time());
    rrmdir($dirname);
    return false;
}

$images = glob($dirname . "*.png");
$srcImagePaths = [];

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
// written to a temporary name first, so getSpiritsFromVideo.php never sends a half written image
$tmpImageFileName = "{$imageFileName}.tmp";
if (!imagejpeg($mapImage, $tmpImageFileName) || !rename($tmpImageFileName, $imageFileName)) {
    error_log("CreateSpirits: ERROR could not save {$imageFileName}");
    @unlink($tmpImageFileName);
}

// the directory also tells getSpiritsFromVideo.php that the sprite is being created
error_log("CreateSpirits:  removing directory {$dirname}");
rrmdir($dirname);
