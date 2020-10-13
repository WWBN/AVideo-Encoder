-- MySQL Workbench Forward Engineering

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';

-- -----------------------------------------------------
-- Schema aVideo-Encoder
-- -----------------------------------------------------
-- -----------------------------------------------------
-- Table `formats`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `formats` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(45) NOT NULL,
  `code` TEXT NOT NULL,
  `created` DATETIME NULL,
  `modified` DATETIME NULL,
  `extension` VARCHAR(5) NULL,
  `extension_from` VARCHAR(5) NULL,
  `order` SMALLINT UNSIGNED NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `streamers`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `streamers` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `siteURL` VARCHAR(255) NOT NULL,
  `user` VARCHAR(45) NOT NULL,
  `pass` VARCHAR(45) NOT NULL,
  `priority` INT NOT NULL DEFAULT 3,
  `isAdmin` TINYINT(1) NOT NULL DEFAULT 0,
  `created` DATETIME NULL,
  `modified` DATETIME NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `encoder_queue`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `encoder_queue` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `fileURI` VARCHAR(255) NOT NULL,
  `filename` VARCHAR(400) NOT NULL,
  `status` VARCHAR(45) NULL,
  `status_obs` VARCHAR(255) NULL,
  `return_vars` VARCHAR(255) NULL,
  `priority` INT(1) NULL,
  `title` VARCHAR(255) NULL,
  `videoDownloadedLink` VARCHAR(255) NULL,
  `downloadedFileName` VARCHAR(255) NULL,
  `streamers_id` INT NOT NULL,
  `formats_id` INT NOT NULL,
  `created` DATETIME NULL,
  `modified` DATETIME NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_encoder_queue_formats_idx` (`formats_id` ASC),
  INDEX `fk_encoder_queue_streamers1_idx` (`streamers_id` ASC),
  CONSTRAINT `fk_encoder_queue_formats`
    FOREIGN KEY (`formats_id`)
    REFERENCES `formats` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_encoder_queue_streamers1`
    FOREIGN KEY (`streamers_id`)
    REFERENCES `streamers` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `configurations`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `configurations` (
  `id` INT NOT NULL,
  `allowedStreamersURL` TEXT NULL,
  `defaultPriority` INT(1) NULL,
  `created` DATETIME NULL,
  `modified` DATETIME NULL,
  `version` VARCHAR(10) NULL,
  `autodelete` TINYINT(1) NULL DEFAULT 1,
  PRIMARY KEY (`id`))
ENGINE = InnoDB;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;


INSERT INTO `formats` VALUES 
(1,'MP4 Low','ffmpeg -i {$pathFileName} -vf scale=-2:360 -movflags +faststart -preset veryfast -vcodec h264 -acodec aac -strict -2 -max_muxing_queue_size 1024 -y {$destinationFile}',now(),now(),'mp4','mp4',10),
(2,'WEBM Low','ffmpeg -i {$pathFileName} -vf scale=-2:360 -movflags +faststart -preset veryfast -f webm -c:v libvpx -b:v 1M -acodec libvorbis -y {$destinationFile}',now(),now(),'webm','mp4',20),
(3,'MP3','ffmpeg -i {$pathFileName} -acodec libmp3lame -y {$destinationFile}',now(),now(),'mp3','mp3',30),
(4,'OGG','ffmpeg -i {$pathFileName} -acodec libvorbis -y {$destinationFile}',now(),now(),'ogg','mp3',40),
(5,'MP3 to Spectrum.MP4','ffmpeg -i {$pathFileName} -filter_complex \'[0:a]showwaves=s=1280x720:mode=line,format=yuv420p[v]\' -map \'[v]\' -map 0:a -c:v libx264 -c:a copy {$destinationFile}',now(),now(),'mp4','mp3',50),
(6,'Video.MP4 to Audio.MP3','ffmpeg -i {$pathFileName} -y {$destinationFile}',now(),now(),'mp3','mp4',60),
(7,'MP4 SD','ffmpeg -i {$pathFileName} -vf scale=-2:540 -movflags +faststart -preset veryfast -vcodec h264 -acodec aac -strict -2 -max_muxing_queue_size 1024 -y {$destinationFile}',now(),now(),'mp4','mp4',11),
(8,'MP4 HD','ffmpeg -i {$pathFileName} -vf scale=-2:720 -movflags +faststart -preset veryfast -vcodec h264 -acodec aac -strict -2 -max_muxing_queue_size 1024 -y {$destinationFile}',now(),now(),'mp4','mp4',12),
(9,'WEBM SD','ffmpeg -i {$pathFileName} -vf scale=-2:540 -movflags +faststart -preset veryfast -f webm -c:v libvpx -b:v 1M -acodec libvorbis -y {$destinationFile}',now(),now(),'webm','mp4',21),
(10,'WEBM HD','ffmpeg -i {$pathFileName} -vf scale=-2:720 -movflags +faststart -preset veryfast -f webm -c:v libvpx -b:v 1M -acodec libvorbis -y {$destinationFile}',now(),now(),'webm','mp4',22),
(11,'Video to Spectrum','60-50-10',now(),now(),'mp4','mp4',70),
(12,'Video to Audio','60-40',now(),now(),'mp3','mp4',71),
(13,'Both Video','10-20',now(),now(),'mp4','mp4',72),
(14,'Both Audio','30-40',now(),now(),'mp3','mp3',73),
(15,'MP4 Low','10',now(),now(),'mp4','mp4',74),
(16,'MP4 SD','11',now(),now(),'mp4','mp4',75),
(17,'MP4 HD','12',now(),now(),'mp4','mp4',76),
(18,'MP4 Low SD','10-11',now(),now(),'mp4','mp4',77),
(19,'MP4 SD HD','11-12',now(),now(),'mp4','mp4',78),
(20,'MP4 Low HD','10 12',now(),now(),'mp4','mp4',79),
(21,'MP4 Low SD HD','10-11-12',now(),now(),'mp4','mp4',80),
(22,'Both Low','10-20',now(),now(),'mp4','mp4',81),
(23,'Both SD','11-21',now(),now(),'mp4','mp4',82),
(24,'Both HD','12-22',now(),now(),'mp4','mp4',83),
(25,'Both Low SD','10-11-20-21',now(),now(),'mp4','mp4',84),
(26,'Both SD HD','11-12-21-22',now(),now(),'mp4','mp4',85),
(27,'Both Low HD','10-12-20-22',now(),now(),'mp4','mp4',86),
(28,'Both Low SD HD','10-11-12-20-21-22',now(),now(),'mp4','mp4',87),
(29,'Multi Bitrate HLS VOD encrypted','ffmpeg -re -i {$pathFileName} -c:a aac -strict -2 -b:a 128k -c:v libx264 -vf scale=-2:360 -g 48 -keyint_min 48  -sc_threshold 0 -bf 3 -b_strategy 2 -b:v 800k -maxrate 856k -bufsize 1200k -f hls -hls_time 6 -hls_list_size 0 -hls_key_info_file {$destinationFile}keyinfo {$destinationFile}low/index.m3u8 -c:a aac -strict -2 -b:a 128k -c:v libx264 -vf scale=-2:540 -g 48 -keyint_min 48 -sc_threshold 0 -bf 3 -b_strategy 2 -b:v 1400k -maxrate 1498k -bufsize 2100k -f hls -hls_time 6 -hls_list_size 0 -hls_key_info_file {$destinationFile}keyinfo {$destinationFile}sd/index.m3u8 -c:a aac -strict -2 -b:a 128k -c:v libx264 -vf scale=-2:720 -g 48 -keyint_min 48 -sc_threshold 0 -bf 3 -b_strategy 2 -b:v 2800k -maxrate 2996k -bufsize 4200k -f hls -hls_time 6 -hls_list_size 0 -hls_key_info_file {$destinationFile}keyinfo {$destinationFile}hd/index.m3u8',now(),now(),'mp4','m3u8',9);
-- ffmpeg -re -i {$pathFileName} -c:a aac -b:a 128k -c:v libx264 -vf scale=-2:540 -g 48 -keyint_min 48 -sc_threshold 0 -bf 3 -b_strategy 2 -b:v 1400k -maxrate 1498k -bufsize 2100k -f hls -hls_time 15 -hls_list_size 0 -hls_key_info_file {$destinationFile}keyinfo {$destinationFile}low/index.m3u8 -c:a aac -b:a 128k -c:v libx264 -vf scale=-2:720 -g 48 -keyint_min 48 -sc_threshold 0 -bf 3 -b_strategy 2 -b:v 2800k -maxrate 2996k -bufsize 4200k -f hls -hls_time 15 -hls_list_size 0 -hls_key_info_file {$destinationFile}keyinfo {$destinationFile}sd/index.m3u8 -c:a aac -b:a 192k -c:v libx264 -vf scale=-2:1080 -g 48 -keyint_min 48 -sc_threshold 0 -bf 3 -b_strategy 2 -b:v 5000k -maxrate 5350k -bufsize 7500k -f hls -hls_time 15 -hls_list_size 0 -hls_key_info_file {$destinationFile}keyinfo {$destinationFile}hd/index.m3u8

INSERT INTO `formats` VALUES (30,'Dynamic HLS','-c:v h264 -vf scale=-2:{$resolution} -r 24 -g 48 -keyint_min 48 -sc_threshold 0 -bf 3 -b_strategy 2 -minrate {$minrate}k -crf 23 -maxrate {$maxrate}k -bufsize {$bufsize}k -c:a aac -b:a {$autioBitrate}k -f hls -hls_time 6 -hls_list_size 0 -hls_key_info_file {$destinationFile}keyinfo {$destinationFile}res{$resolution}/index.m3u8',now(),now(),'m3u8','mp4',6);
INSERT INTO `formats` VALUES (31,'Dynamic MP4','-vf scale=-2:{$resolution} -movflags +faststart -preset veryfast -vcodec h264 -acodec aac -strict -2 -b:a {$autioBitrate}k  -max_muxing_queue_size 1024 -y {$destinationFile}',now(),now(),'mp4','mp4',7);
INSERT INTO `formats` VALUES (32,'Dynamic WEBM','-vf scale=-2:{$resolution} -movflags +faststart -preset veryfast -f webm -c:v libvpx -b:v 1M -acodec libvorbis -b:a {$autioBitrate}k  -y {$destinationFile}',now(),now(),'webm','mp4',8);
INSERT INTO `formats` VALUES (33,'Audio to HLS','',now(),now(),'m3u8','mp3',88);
INSERT INTO `formats` VALUES (34,'Audio to MP4','',now(),now(),'mp4','mp3',89);
INSERT INTO `formats` VALUES (35,'Audio to WEBM','',now(),now(),'webm','mp3',90);