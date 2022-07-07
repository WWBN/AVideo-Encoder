ALTER TABLE `streamers` 
CHANGE COLUMN `user` `user` VARCHAR(255) NOT NULL ,
CHANGE COLUMN `pass` `pass` VARCHAR(255) NOT NULL ;
UPDATE configurations SET  version = '4.0', modified = now() WHERE id = 1;