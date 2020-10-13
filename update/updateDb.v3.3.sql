INSERT INTO `formats` VALUES (30,'HLS','-c:v h264 -vf scale=-2:{$resolution} -r 24 -g 48 -keyint_min 48 -sc_threshold 0 -bf 3 -b_strategy 2 -minrate {$minrate}k -crf 23 -maxrate {$maxrate}k -bufsize {$bufsize}k -c:a aac -b:a {$autioBitrate}k -f hls -hls_time 6 -hls_list_size 0 -hls_key_info_file {$destinationFile}keyinfo {$destinationFile}res{$resolution}/index.m3u8',now(),now(),'m3u8','mp4',6);
INSERT INTO `formats` VALUES (31,'MP4','-vf scale=-2:{$resolution} -movflags +faststart -preset veryfast -vcodec h264 -acodec aac -strict -2 -b:a {$autioBitrate}k  -max_muxing_queue_size 1024 -y {$destinationFile}',now(),now(),'mp4','mp4',7);
INSERT INTO `formats` VALUES (32,'WEBM','-vf scale=-2:{$resolution} -movflags +faststart -preset veryfast -f webm -c:v libvpx -b:v 1M -acodec libvorbis -b:a {$autioBitrate}k  -y {$destinationFile}',now(),now(),'webm','mp4',8);

INSERT INTO `formats` VALUES (33,'Audio to HLS','',now(),now(),'m3u8','mp3',88);
INSERT INTO `formats` VALUES (34,'Audio to MP4','',now(),now(),'mp4','mp3',89);
INSERT INTO `formats` VALUES (35,'Audio to WEBM','',now(),now(),'webm','mp3',90);

UPDATE configurations SET  version = '3.3', modified = now() WHERE id = 1;