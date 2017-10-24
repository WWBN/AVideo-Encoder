SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';


ALTER TABLE `formats` 
ADD COLUMN `order` SMALLINT(5) UNSIGNED NULL DEFAULT NULL;



SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;


UPDATE `formats` SET `name`='MP4 Low', `order`='10' WHERE `id`='1';
UPDATE `formats` SET `name`='WEBM Low', `order`='20' WHERE `id`='2';
UPDATE `formats` SET `order`='30' WHERE `id`='3';
UPDATE `formats` SET `order`='40' WHERE `id`='4';
UPDATE `formats` SET `order`='50' WHERE `id`='5';
UPDATE `formats` SET `order`='60' WHERE `id`='6';
UPDATE `formats` SET `order`='70' WHERE `id`='7';
UPDATE `formats` SET `order`='71' WHERE `id`='8';
UPDATE `formats` SET `order`='72' WHERE `id`='9';
UPDATE `formats` SET `order`='73' WHERE `id`='10';
INSERT INTO `formats` (`name`, `code`, `extension`, `extension_from`, `order`) VALUES ('MP4 SD', 'ffmpeg -i {$pathFileName} -vf scale=720:480 -vcodec h264 -acodec aac -strict -2 -y {$destinationFile}', 'mp4', 'mp4', '11');
INSERT INTO `formats` (`name`, `code`, `extension`, `extension_from`, `order`) VALUES ('MP4 HD', 'ffmpeg -i {$pathFileName} -vf scale=1280:720 -vcodec h264 -acodec aac -strict -2 -y {$destinationFile}', 'mp4', 'mp4', '12');
INSERT INTO `formats` (`name`, `code`, `extension`, `extension_from`, `order`) VALUES ('WEBM SD', 'ffmpeg -i {$pathFileName} -vf scale=720:480 -f webm -c:v libvpx -b:v 1M -acodec libvorbis -y {$destinationFile}', 'webm', 'mp4', '21');
INSERT INTO `formats` (`name`, `code`, `extension`, `extension_from`, `order`) VALUES ('WEBM HD', 'ffmpeg -i {$pathFileName} -vf scale=1280:720 -f webm -c:v libvpx -b:v 1M -acodec libvorbis -y {$destinationFile}', 'webm', 'mp4', '22');
INSERT INTO `formats` (`name`, `code`, `order`) VALUES ('MP4 Low', '10', '74');
INSERT INTO `formats` (`name`, `code`, `order`) VALUES ('MP4 SD', '11', '75');
INSERT INTO `formats` (`name`, `code`, `order`) VALUES ('MP4 HD', '12', '76');
INSERT INTO `formats` (`name`, `code`, `order`) VALUES ('MP4 Low SD', '10-11', '77');
INSERT INTO `formats` (`name`, `code`, `order`) VALUES ('MP4 SD HD', '11-12', '78');
INSERT INTO `formats` (`name`, `code`, `order`) VALUES ('MP4 Low HD', '10 12', '79');
INSERT INTO `formats` (`name`, `code`, `order`) VALUES ('MP4 Low SD HD', '10-11-12', '80');
UPDATE `formats` SET `code`='60-50-10' WHERE `id`='7';
UPDATE `formats` SET `code`='60-40' WHERE `id`='8';
UPDATE `formats` SET `code`='10-20' WHERE `id`='9';
UPDATE `formats` SET `code`='30-40' WHERE `id`='10';
INSERT INTO `formats` (`name`, `code`, `order`) VALUES ('Both Low', '10-20', '81');
INSERT INTO `formats` (`name`, `code`, `order`) VALUES ('Both SD', '11-21', '82');
INSERT INTO `formats` (`name`, `code`, `order`) VALUES ('Both HD', '12-22', '83');
INSERT INTO `formats` (`name`, `code`, `order`) VALUES ('Both Low SD', '10-11-20-21', '84');
INSERT INTO `formats` (`name`, `code`, `order`) VALUES ('Both SD HD', '11-12-21-22', '85');
INSERT INTO `formats` (`name`, `code`, `order`) VALUES ('Both Low HD', '10-12-20-22', '86');
INSERT INTO `formats` (`name`, `code`, `order`) VALUES ('Both Low SD HD', '10-11-12-20-21-22', '87');
UPDATE `formats` SET `extension`='mp4', `extension_from`='mp4' WHERE `id`='23';
UPDATE `formats` SET `extension`='mp4', `extension_from`='mp4' WHERE `id`='24';
UPDATE `formats` SET `extension`='mp4', `extension_from`='mp4' WHERE `id`='25';
UPDATE `formats` SET `extension`='mp4', `extension_from`='mp4' WHERE `id`='26';
UPDATE `formats` SET `extension`='mp4', `extension_from`='mp4' WHERE `id`='27';
UPDATE `formats` SET `extension`='mp4', `extension_from`='mp4' WHERE `id`='28';
UPDATE `formats` SET `extension`='mp4', `extension_from`='mp4' WHERE `id`='29';
UPDATE `formats` SET `extension`='mp4', `extension_from`='mp4' WHERE `id`='30';
UPDATE `formats` SET `extension`='mp4', `extension_from`='mp4' WHERE `id`='31';
UPDATE `formats` SET `extension`='mp4', `extension_from`='mp4' WHERE `id`='32';
UPDATE `formats` SET `extension`='mp4', `extension_from`='mp4' WHERE `id`='33';
UPDATE `formats` SET `extension`='mp4', `extension_from`='mp4' WHERE `id`='34';
UPDATE `formats` SET `extension`='mp4', `extension_from`='mp4' WHERE `id`='35';
UPDATE `formats` SET `extension`='mp4', `extension_from`='mp4' WHERE `id`='36';


UPDATE configurations SET  version = '2.0', modified = now() WHERE id = 1;