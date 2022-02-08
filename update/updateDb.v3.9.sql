RENAME TABLE `configurations` TO `configurations_encoder`; 
UPDATE `configurations_encoder` SET `version` = '3.9', `modified` = now() WHERE `id` = 1;
