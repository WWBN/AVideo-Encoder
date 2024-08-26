INSERT INTO `formats` (`id`, `name`, `code`, `created`, `modified`, `extension`, `extension_from`, `order`)
VALUES
(1,'MP4 Low','ffmpeg -i {$pathFileName} -vf scale=-2:360 -movflags +faststart -preset veryfast -vcodec h264 -b:v 700k -acodec aac -b:a 96k -max_muxing_queue_size 1024 -y {$destinationFile}',now(),now(),'mp4','mp4',10),
(2,'WEBM Low','ffmpeg -i {$pathFileName} -vf scale=-2:360 -movflags +faststart -preset veryfast -f webm -c:v libvpx -b:v 700k -acodec libvorbis -b:a 96k -y {$destinationFile}',now(),now(),'webm','mp4',20),
(7,'MP4 SD','ffmpeg -i {$pathFileName} -vf scale=-2:540 -movflags +faststart -preset veryfast -vcodec h264 -b:v 1200k -acodec aac -b:a 128k -max_muxing_queue_size 1024 -y {$destinationFile}',now(),now(),'mp4','mp4',11),
(8,'MP4 HD','ffmpeg -i {$pathFileName} -vf scale=-2:720 -movflags +faststart -preset veryfast -vcodec h264 -b:v 2500k -acodec aac -b:a 128k -max_muxing_queue_size 1024 -y {$destinationFile}',now(),now(),'mp4','mp4',12),
(9,'WEBM SD','ffmpeg -i {$pathFileName} -vf scale=-2:540 -movflags +faststart -preset veryfast -f webm -c:v libvpx -b:v 1200k -acodec libvorbis -b:a 128k -y {$destinationFile}',now(),now(),'webm','mp4',21),
(10,'WEBM HD','ffmpeg -i {$pathFileName} -vf scale=-2:720 -movflags +faststart -preset veryfast -f webm -c:v libvpx -b:v 2500k -acodec libvorbis -b:a 128k -y {$destinationFile}',now(),now(),'webm','mp4',22),
(29,'Multi Bitrate HLS VOD encrypted','ffmpeg -re -i {$pathFileName} -c:a aac -b:a 128k -c:v libx264 -vf scale=-2:360 -g 48 -keyint_min 48 -sc_threshold 0 -bf 3 -b_strategy 2 -b:v 800k -maxrate 856k -bufsize 1200k -f hls -hls_time 6 -hls_list_size 0 -hls_key_info_file {$destinationFile}keyinfo {$destinationFile}low/index.m3u8 -c:a aac -b:a 128k -c:v libx264 -vf scale=-2:540 -g 48 -keyint_min 48 -sc_threshold 0 -bf 3 -b_strategy 2 -b:v 1400k -maxrate 1498k -bufsize 2100k -f hls -hls_time 6 -hls_list_size 0 -hls_key_info_file {$destinationFile}keyinfo {$destinationFile}sd/index.m3u8 -c:a aac -b:a 128k -c:v libx264 -vf scale=-2:720 -g 48 -keyint_min 48 -sc_threshold 0 -bf 3 -b_strategy 2 -b:v 2800k -maxrate 2996k -bufsize 4200k -f hls -hls_time 6 -hls_list_size 0 -hls_key_info_file {$destinationFile}keyinfo {$destinationFile}hd/index.m3u8',now(),now(),'mp4','m3u8',9),
(30,'Dynamic HLS','-c:v h264 -vf scale=-2:{$resolution} -r 24 -g 48 -keyint_min 48 -sc_threshold 0 -bf 3 -b_strategy 2 -minrate {$minrate}k -crf 23 -maxrate {$maxrate}k -bufsize {$bufsize}k -c:a aac -b:a {$audioBitrate}k -f hls -hls_time 6 -hls_list_size 0 -hls_key_info_file {$destinationFile}keyinfo {$destinationFile}res{$resolution}/index.m3u8',now(),now(),'m3u8','mp4',6),
(31,'Dynamic MP4','-vf scale=-2:{$resolution} -movflags +faststart -preset veryfast -vcodec h264 -b:v {$bitrate}k -acodec aac -b:a {$audioBitrate}k -max_muxing_queue_size 1024 -y {$destinationFile}',now(),now(),'mp4','mp4',7),
(32,'Dynamic WEBM','-vf scale=-2:{$resolution} -movflags +faststart -preset veryfast -f webm -c:v libvpx -b:v 1M -acodec libvorbis -b:a {$audioBitrate}k -y {$destinationFile}',now(),now(),'webm','mp4',8)
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `code` = VALUES(`code`),
  `created` = VALUES(`created`),
  `modified` = VALUES(`modified`),
  `extension` = VALUES(`extension`),
  `extension_from` = VALUES(`extension_from`),
  `order` = VALUES(`order`);

UPDATE configurations_encoder SET  version = '5.1', modified = now() WHERE id = 1;