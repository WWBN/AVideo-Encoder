-- support for the chunked transfer between servers
UPDATE configurations SET  version = '3.1', modified = now() WHERE id = 1;
