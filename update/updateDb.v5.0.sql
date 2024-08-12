ALTER TABLE `streamers`
ADD COLUMN `json` TEXT NULL;
UPDATE configurations_encoder SET  version = '5.0', modified = now() WHERE id = 1;