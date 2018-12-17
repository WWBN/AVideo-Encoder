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
(7,'MP4 SD','ffmpeg -i {$pathFileName} -vf scale=720:540 -movflags faststart -preset ultrafast -vcodec h264 -acodec aac -strict -2 -y {$destinationFile}',now(),now(),'mp4','mp4',11),
(8,'MP4 HD','ffmpeg -i {$pathFileName} -vf scale=1280:720 -movflags faststart -preset ultrafast -vcodec h264 -acodec aac -strict -2 -y {$destinationFile}',now(),now(),'mp4','mp4',12),
(9,'WEBM SD','ffmpeg -i {$pathFileName} -vf scale=720:540 -movflags faststart -preset ultrafast -f webm -c:v libvpx -b:v 1M -acodec libvorbis -y {$destinationFile}',now(),now(),'webm','mp4',21),
(10,'WEBM HD','ffmpeg -i {$pathFileName} -vf scale=1280:720 -movflags faststart -preset ultrafast -f webm -c:v libvpx -b:v 1M -acodec libvorbis -y {$destinationFile}',now(),now(),'webm','mp4',22),
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
(28,'Both Low SD HD','10-11-12-20-21-22',now(),now(),'mp4','mp4',87);

UPDATE configurations SET  version = '2.1', modified = now() WHERE id = 1;