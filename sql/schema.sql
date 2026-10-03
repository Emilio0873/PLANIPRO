-- PLANIPRO - Schéma de base de données
CREATE DATABASE IF NOT EXISTS planipro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE planipro;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS historique;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS activites;
DROP TABLE IF EXISTS rendez_vous;
DROP TABLE IF EXISTS partenaires;
DROP TABLE IF EXISTS clients;
DROP TABLE IF EXISTS entreprises;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('entreprise','employe','client','partenaire') NOT NULL,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    telephone VARCHAR(30) DEFAULT NULL,
    entreprise_id INT UNSIGNED DEFAULT NULL,
    actif TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_role (role),
    INDEX idx_users_entreprise (entreprise_id)
) ENGINE=InnoDB;

CREATE TABLE entreprises (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    raison_sociale VARCHAR(200) NOT NULL,
    adresse TEXT DEFAULT NULL,
    secteur VARCHAR(120) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    site_web VARCHAR(255) DEFAULT NULL,
    logo VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_entreprises_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

ALTER TABLE users
    ADD CONSTRAINT fk_users_entreprise
    FOREIGN KEY (entreprise_id) REFERENCES entreprises(id) ON DELETE SET NULL;

CREATE TABLE clients (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    adresse TEXT DEFAULT NULL,
    entreprise_nom VARCHAR(200) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_clients_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE partenaires (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    organisation VARCHAR(200) DEFAULT NULL,
    type_partenariat VARCHAR(120) DEFAULT NULL,
    adresse TEXT DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_partenaires_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE rendez_vous (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entreprise_id INT UNSIGNED NOT NULL,
    demandeur_id INT UNSIGNED NOT NULL,
    type_demandeur ENUM('client','partenaire','employe') NOT NULL,
    titre VARCHAR(200) NOT NULL,
    motif TEXT DEFAULT NULL,
    date_debut DATETIME NOT NULL,
    date_fin DATETIME NOT NULL,
    lieu VARCHAR(255) DEFAULT NULL,
    statut ENUM('en_attente','accepte','refuse','reporte','annule','termine') NOT NULL DEFAULT 'en_attente',
    commentaire_entreprise TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_rdv_entreprise FOREIGN KEY (entreprise_id) REFERENCES entreprises(id) ON DELETE CASCADE,
    CONSTRAINT fk_rdv_demandeur FOREIGN KEY (demandeur_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_rdv_statut (statut),
    INDEX idx_rdv_dates (date_debut, date_fin)
) ENGINE=InnoDB;

CREATE TABLE activites (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entreprise_id INT UNSIGNED NOT NULL,
    assigne_a INT UNSIGNED DEFAULT NULL,
    cree_par INT UNSIGNED NOT NULL,
    titre VARCHAR(200) NOT NULL,
    description TEXT DEFAULT NULL,
    priorite ENUM('basse','moyenne','haute','urgente') NOT NULL DEFAULT 'moyenne',
    statut ENUM('a_faire','en_cours','terminee','annulee') NOT NULL DEFAULT 'a_faire',
    progression TINYINT UNSIGNED NOT NULL DEFAULT 0,
    echeance DATE DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_act_entreprise FOREIGN KEY (entreprise_id) REFERENCES entreprises(id) ON DELETE CASCADE,
    CONSTRAINT fk_act_assigne FOREIGN KEY (assigne_a) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_act_createur FOREIGN KEY (cree_par) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_act_statut (statut),
    INDEX idx_act_priorite (priorite)
) ENGINE=InnoDB;

CREATE TABLE notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    titre VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) DEFAULT 'info',
    lien VARCHAR(255) DEFAULT NULL,
    lu TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_notif_lu (lu)
) ENGINE=InnoDB;

CREATE TABLE historique (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED DEFAULT NULL,
    entite_type VARCHAR(50) NOT NULL,
    entite_id INT UNSIGNED NOT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_hist_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_hist_entite (entite_type, entite_id)
) ENGINE=InnoDB;

CREATE TABLE conversations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    participant_a INT UNSIGNED NOT NULL,
    participant_b INT UNSIGNED NOT NULL,
    last_message_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_participants (participant_a, participant_b),
    CONSTRAINT fk_conv_a FOREIGN KEY (participant_a) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_conv_b FOREIGN KEY (participant_b) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_conv_last (last_message_at)
) ENGINE=InnoDB;

CREATE TABLE messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT UNSIGNED NOT NULL,
    sender_id INT UNSIGNED NOT NULL,
    body TEXT NOT NULL,
    lu TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_msg_conv FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    CONSTRAINT fk_msg_sender FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_msg_conv (conversation_id, created_at),
    INDEX idx_msg_lu (lu)
) ENGINE=InnoDB;

CREATE TABLE contact_public (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entreprise_id INT UNSIGNED NOT NULL,
    expediteur_user_id INT UNSIGNED DEFAULT NULL,
    nom VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    telephone VARCHAR(30) DEFAULT NULL,
    sujet VARCHAR(200) NOT NULL DEFAULT 'Message',
    message TEXT NOT NULL,
    public_token VARCHAR(64) DEFAULT NULL,
    mdp_a_envoyer VARCHAR(120) DEFAULT NULL,
    notif_envoyee TINYINT(1) NOT NULL DEFAULT 0,
    lu TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_contact_entreprise FOREIGN KEY (entreprise_id) REFERENCES entreprises(id) ON DELETE CASCADE,
    CONSTRAINT fk_contact_user FOREIGN KEY (expediteur_user_id) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY uniq_contact_public_token (public_token),
    INDEX idx_contact_ent (entreprise_id, lu),
    INDEX idx_contact_created (created_at)
) ENGINE=InnoDB;

CREATE TABLE notifications_publiques (
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
