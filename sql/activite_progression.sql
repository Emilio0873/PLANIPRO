-- Suivi de progression des activités (0–100 %)
USE planipro;

ALTER TABLE activites
    ADD COLUMN progression TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER statut;
