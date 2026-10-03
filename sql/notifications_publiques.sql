-- Notifications publiques (visiteurs ayant écrit à une entreprise)
USE planipro;

ALTER TABLE contact_public
    ADD COLUMN public_token VARCHAR(64) NULL AFTER message,
    ADD COLUMN mdp_a_envoyer VARCHAR(120) NULL AFTER public_token,
    ADD COLUMN notif_envoyee TINYINT(1) NOT NULL DEFAULT 0 AFTER mdp_a_envoyer;

CREATE UNIQUE INDEX uniq_contact_public_token ON contact_public (public_token);

CREATE TABLE IF NOT EXISTS notifications_publiques (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    public_token VARCHAR(64) NOT NULL,
    contact_public_id INT UNSIGNED DEFAULT NULL,
    entreprise_id INT UNSIGNED NOT NULL,
    titre VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(30) NOT NULL DEFAULT 'info',
    lu TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_np_token (public_token, lu),
    CONSTRAINT fk_np_contact FOREIGN KEY (contact_public_id) REFERENCES contact_public(id) ON DELETE CASCADE,
    CONSTRAINT fk_np_entreprise FOREIGN KEY (entreprise_id) REFERENCES entreprises(id) ON DELETE CASCADE
) ENGINE=InnoDB;
