-- Messages publics vers les entreprises PLANIPRO
USE planipro;

CREATE TABLE IF NOT EXISTS contact_public (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entreprise_id INT UNSIGNED NOT NULL,
    expediteur_user_id INT UNSIGNED DEFAULT NULL,
    nom VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    telephone VARCHAR(30) DEFAULT NULL,
    sujet VARCHAR(200) NOT NULL DEFAULT 'Message',
    message TEXT NOT NULL,
    lu TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_contact_entreprise FOREIGN KEY (entreprise_id) REFERENCES entreprises(id) ON DELETE CASCADE,
    CONSTRAINT fk_contact_user FOREIGN KEY (expediteur_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_contact_ent (entreprise_id, lu),
    INDEX idx_contact_created (created_at)
) ENGINE=InnoDB;
