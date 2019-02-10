ALTER TABLE `formats` CHANGE COLUMN `code` `code` TEXT NOT NULL ;
INSERT INTO `formats` VALUES (29,'Multi Bitrate HLS VOD encrypted','ffmpeg -re -i {$pathFileName} -c:a aac -strict -2 -b:a 128k -c:v libx264 -vf scale=-2:360 -g 48 -keyint_min 48  -sc_threshold 0 -bf 3 -b_strategy 2 -b:v 800k -maxrate 856k -bufsize 1200k -b:a 96k -f hls -hls_time 6 -hls_list_size 0 -hls_key_info_file {$destinationFile}keyinfo {$destinationFile}low/index.m3u8 -c:a aac -strict -2 -b:a 128k -c:v libx264 -vf scale=-2:540 -g 48 -keyint_min 48 -sc_threshold 0 -bf 3 -b_strategy 2 -b:v 1400k -maxrate 1498k -bufsize 2100k -b:a 128k -f hls -hls_time 6 -hls_list_size 0 -hls_key_info_file {$destinationFile}keyinfo {$destinationFile}sd/index.m3u8 -c:a aac -strict -2 -b:a 128k -c:v libx264 -vf scale=-2:720 -g 48 -keyint_min 48 -sc_threshold 0 -bf 3 -b_strategy 2 -b:v 2800k -maxrate 2996k -bufsize 4200k -b:a 128k -f hls -hls_time 6 -hls_list_size 0 -hls_key_info_file {$destinationFile}keyinfo {$destinationFile}hd/index.m3u8',now(),now(),'mp4','m3u8',9);

ALTER TABLE `encoder_queue` 
CHANGE COLUMN `status` `status` VARCHAR(45) NULL DEFAULT NULL ;


UPDATE configurations SET  version = '2.4', modified = now() WHERE id = 1;