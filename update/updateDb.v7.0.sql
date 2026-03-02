-- security update checkpoint
UPDATE configurations_encoder SET  version = '7.0', modified = now() WHERE id = 1;
