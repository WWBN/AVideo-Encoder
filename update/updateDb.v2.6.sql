
ALTER TABLE `encoder_queue` CHANGE COLUMN `return_vars` `return_vars` TEXT NULL DEFAULT NULL ;


UPDATE configurations SET  version = '2.6', modified = now() WHERE id = 1;
