ALTER TABLE `encoder_queue` ADD COLUMN `retry_count` INT NOT NULL DEFAULT 0;

UPDATE configurations_encoder SET  version = '8.2', modified = now() WHERE id = 1;
