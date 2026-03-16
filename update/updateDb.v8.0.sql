-- security update checkpoint
UPDATE configurations_encoder SET  version = '8.0', modified = now() WHERE id = 1;
