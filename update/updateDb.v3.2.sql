-- support for the chunked transfer between servers
UPDATE configurations SET  version = '3.2', modified = now() WHERE id = 1;
