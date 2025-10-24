CREATE TABLE IF NOT EXISTS ymobsetspawn_data
(
    `key`  VARCHAR(64) PRIMARY KEY NOT NULL,
    `json` LONGTEXT                NOT NULL
) CHARSET = utf8mb4
  COLLATE utf8mb4_bin;