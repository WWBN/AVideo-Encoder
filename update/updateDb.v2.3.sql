ALTER TABLE `configurations` 
ADD COLUMN `autodelete` TINYINT(1) NULL DEFAULT 1 AFTER `version`;


UPDATE configurations SET  version = '2.3', modified = now() WHERE id = 1;