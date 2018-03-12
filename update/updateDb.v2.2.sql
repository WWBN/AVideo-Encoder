ALTER TABLE `encoder_queue` 
CHANGE COLUMN `status` `status` ENUM('queue', 'encoding', 'error', 'done', 'downloading', 'transferring') NULL DEFAULT NULL;

UPDATE configurations SET  version = '2.2', modified = now() WHERE id = 1;