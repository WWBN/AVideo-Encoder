ALTER TABLE `encoder_queue` CHANGE COLUMN `return_vars` `return_vars` TEXT NULL DEFAULT NULL;

UPDATE configurations_encoder SET version = '8.1', modified = now() WHERE id = 1;
