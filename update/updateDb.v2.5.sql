UPDATE `formats` 
SET code = 'ffmpeg -i {$pathFileName} -vf scale=-2:360 -movflags faststart -preset ultrafast -vcodec h264 -acodec aac -strict -2 -max_muxing_queue_size 1024 -y {$destinationFile}'
WHERE id = 1;

UPDATE `formats` 
SET code = 'ffmpeg -i {$pathFileName} -vf scale=-2:540 -movflags faststart -preset ultrafast -vcodec h264 -acodec aac -strict -2 -max_muxing_queue_size 1024 -y {$destinationFile}'
WHERE id = 7;

UPDATE `formats` 
SET code = 'ffmpeg -i {$pathFileName} -vf scale=-2:720 -movflags faststart -preset ultrafast -vcodec h264 -acodec aac -strict -2 -max_muxing_queue_size 1024 -y {$destinationFile}'
WHERE id = 8;

UPDATE configurations SET  version = '2.5', modified = now() WHERE id = 1;
