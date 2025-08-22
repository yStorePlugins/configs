CREATE TABLE IF NOT EXISTS ylobby_user
(
    `key`  VARCHAR(64) PRIMARY KEY,
    `json` LONGTEXT       NOT NULL
);