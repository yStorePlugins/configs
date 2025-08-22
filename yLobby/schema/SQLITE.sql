CREATE TABLE IF NOT EXISTS ylobby_user
(
    `key`  VARCHAR(64) PRIMARY KEY NOT NULL,
    `json` LONGTEXT                NOT NULL
);