-- Logo / photo de profil entreprise
USE planipro;

ALTER TABLE entreprises
    ADD COLUMN IF NOT EXISTS logo VARCHAR(255) DEFAULT NULL AFTER site_web;
