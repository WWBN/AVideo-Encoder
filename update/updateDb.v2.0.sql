SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';


ALTER TABLE `formats` 
ADD COLUMN `order` SMALLINT(5) UNSIGNED NULL DEFAULT NULL;



SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;

SET FOREIGN_KEY_CHECKS = 0; 
TRUNCATE `encoder_queue`;
TRUNCATE `formats`;
SET FOREIGN_KEY_CHECKS = 1;
INSERT INTO `formats` VALUES 
(1,'MP4 Low','ffmpeg -i {$pathFileName} -vf scale=-2:360 -movflags faststart -preset ultrafast -vcodec h264 -acodec aac -strict -2 -y {$destinationFile}',now(),now(),'mp4','mp4',10),
(2,'WEBM Low','ffmpeg -i {$pathFileName} -vf scale=-2:360 -movflags faststart -preset ultrafast -f webm -c:v libvpx -b:v 1M -acodec libvorbis -y {$destinationFile}',now(),now(),'webm','mp4',20),
(3,'MP3','ffmpeg -i {$pathFileName} -acodec libmp3lame -y {$destinationFile}',now(),now(),'mp3','mp3',30),
(4,'OGG','ffmpeg -i {$pathFileName} -acodec libvorbis -y {$destinationFile}',now(),now(),'ogg','mp3',40),
(5,'MP3 to Spectrum.MP4','ffmpeg -i {$pathFileName} -filter_complex \'[0:a]showwaves=s=640x360:mode=line,format=yuv420p[v]\' -map \'[v]\' -map 0:a -c:v libx264 -c:a copy {$destinationFile}',now(),now(),'mp4','mp3',50),
(6,'Video.MP4 to Audio.MP3','ffmpeg -i {$pathFileName} -y {$destinationFile}',now(),now(),'mp3','mp4',60),
(7,'MP4 SD','ffmpeg -i {$pathFileName} -vf scale=720:480 -vcodec h264 -acodec aac -strict -2 -y {$destinationFile}',now(),now(),'mp4','mp4',11),
(8,'MP4 HD','ffmpeg -i {$pathFileName} -vf scale=1280:720 -vcodec h264 -acodec aac -strict -2 -y {$destinationFile}',now(),now(),'mp4','mp4',12),
(9,'WEBM SD','ffmpeg -i {$pathFileName} -vf scale=720:480 -f webm -c:v libvpx -b:v 1M -acodec libvorbis -y {$destinationFile}',now(),now(),'webm','mp4',21),
(10,'WEBM HD','ffmpeg -i {$pathFileName} -vf scale=1280:720 -f webm -c:v libvpx -b:v 1M -acodec libvorbis -y {$destinationFile}',now(),now(),'webm','mp4',22),
(11,'MP4 SD','ffmpeg -i {$pathFileName} -vf scale=720:480 -vcodec h264 -acodec aac -strict -2 -y {$destinationFile}',now(),now(),'mp4','mp4',11),
(12,'MP4 HD','ffmpeg -i {$pathFileName} -vf scale=1280:720 -vcodec h264 -acodec aac -strict -2 -y {$destinationFile}',now(),now(),'mp4','mp4',12),
(13,'WEBM SD','ffmpeg -i {$pathFileName} -vf scale=720:480 -f webm -c:v libvpx -b:v 1M -acodec libvorbis -y {$destinationFile}',now(),now(),'webm','mp4',21),
(14,'WEBM HD','ffmpeg -i {$pathFileName} -vf scale=1280:720 -f webm -c:v libvpx -b:v 1M -acodec libvorbis -y {$destinationFile}',now(),now(),'webm','mp4',22),
(15,'MP4 SD','ffmpeg -i {$pathFileName} -vf scale=720:480 -vcodec h264 -acodec aac -strict -2 -y {$destinationFile}',now(),now(),'mp4','mp4',11),
(16,'MP4 HD','ffmpeg -i {$pathFileName} -vf scale=1280:720 -vcodec h264 -acodec aac -strict -2 -y {$destinationFile}',now(),now(),'mp4','mp4',12),
(17,'WEBM SD','ffmpeg -i {$pathFileName} -vf scale=720:480 -f webm -c:v libvpx -b:v 1M -acodec libvorbis -y {$destinationFile}',now(),now(),'webm','mp4',21),
(18,'WEBM HD','ffmpeg -i {$pathFileName} -vf scale=1280:720 -f webm -c:v libvpx -b:v 1M -acodec libvorbis -y {$destinationFile}',now(),now(),'webm','mp4',22),
(19,'Video to Spectrum','60-50-10','2017-01-01 00:00:00','2017-01-01 00:00:00','mp4','mp4',70),
(20,'Video to Audio','60-40','2017-01-01 00:00:00','2017-01-01 00:00:00','mp3','mp4',71),
(21,'Both Video','10-20','2017-01-01 00:00:00','2017-01-01 00:00:00','mp4','mp4',72),
(22,'Both Audio','30-40','2017-01-01 00:00:00','2017-01-01 00:00:00','mp3','mp3',73),
(23,'MP4 Low','10',now(),now(),'mp4','mp4',74),
(24,'MP4 SD','11',now(),now(),'mp4','mp4',75),
(25,'MP4 HD','12',now(),now(),'mp4','mp4',76),
(26,'MP4 Low SD','10-11',now(),now(),'mp4','mp4',77),
(27,'MP4 SD HD','11-12',now(),now(),'mp4','mp4',78),
(28,'MP4 Low HD','10 12',now(),now(),'mp4','mp4',79),
(29,'MP4 Low SD HD','10-11-12',now(),now(),'mp4','mp4',80),
(30,'Both Low','10-20',now(),now(),'mp4','mp4',81),
(31,'Both SD','11-21',now(),now(),'mp4','mp4',82),
(32,'Both HD','12-22',now(),now(),'mp4','mp4',83),
(33,'Both Low SD','10-11-20-21',now(),now(),'mp4','mp4',84),
(34,'Both SD HD','11-12-21-22',now(),now(),'mp4','mp4',85),
(35,'Both Low HD','10-12-20-22',now(),now(),'mp4','mp4',86),
(36,'Both Low SD HD','10-11-12-20-21-22',now(),now(),'mp4','mp4',87);


UPDATE configurations SET  version = '2.0', modified = now() WHERE id = 1;