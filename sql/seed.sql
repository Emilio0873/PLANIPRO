-- PLANIPRO - Données de démonstration
-- Mot de passe pour tous les comptes démo : password

USE planipro;

-- password_hash for "password"
SET @pwd = '$2y$10$6YJEBmw/sZEyBfYf9aVUL.SUYRqxgGwTZlub1beW8N/lm9mfLTjUa';

INSERT INTO users (id, email, password_hash, role, nom, prenom, telephone, entreprise_id, actif) VALUES
(1, 'entreprise@planipro.local', @pwd, 'entreprise', 'Mukendi', 'Jean', '+243800000001', NULL, 1);

INSERT INTO entreprises (id, user_id, raison_sociale, adresse, secteur, description) VALUES
(1, 1, 'Tech Congo SARL', 'Av. Lumumba, Kinshasa', 'Informatique', 'Solutions numériques pour entreprises');

INSERT INTO users (id, email, password_hash, role, nom, prenom, telephone, entreprise_id, actif) VALUES
(2, 'client@planipro.local', @pwd, 'client', 'Kalonji', 'Alice', '+243800000002', 1, 1),
(3, 'partenaire@planipro.local', @pwd, 'partenaire', 'Ilunga', 'Marc', '+243800000003', 1, 1),
(4, 'employe@planipro.local', @pwd, 'employe', 'Mwamba', 'Sarah', '+243800000004', 1, 1);

INSERT INTO clients (user_id, adresse, entreprise_nom) VALUES
(2, 'Gombe, Kinshasa', 'Boutique Alice');

INSERT INTO partenaires (user_id, organisation, type_partenariat) VALUES
(3, 'Partenaires Plus', 'Fournisseur');

INSERT INTO rendez_vous (entreprise_id, demandeur_id, type_demandeur, titre, motif, date_debut, date_fin, lieu, statut) VALUES
(1, 2, 'client', 'Présentation produit', 'Découverte de l\'offre PLANIPRO', DATE_ADD(NOW(), INTERVAL 2 DAY), DATE_ADD(DATE_ADD(NOW(), INTERVAL 2 DAY), INTERVAL 1 HOUR), 'Siège Tech Congo', 'en_attente'),
(1, 3, 'partenaire', 'Réunion partenariat', 'Discussion contrat annuel', DATE_ADD(NOW(), INTERVAL 5 DAY), DATE_ADD(DATE_ADD(NOW(), INTERVAL 5 DAY), INTERVAL 2 HOUR), 'Visio', 'accepte');

INSERT INTO activites (entreprise_id, assigne_a, cree_par, titre, description, priorite, statut, echeance) VALUES
(1, 4, 1, 'Préparer dossier client', 'Compiler les besoins de Boutique Alice', 'haute', 'en_cours', DATE_ADD(CURDATE(), INTERVAL 3 DAY)),
(1, 4, 1, 'Relancer partenaires', 'Appels de suivi Q3', 'moyenne', 'a_faire', DATE_ADD(CURDATE(), INTERVAL 7 DAY));

INSERT INTO notifications (user_id, titre, message, type, lien) VALUES
(1, 'Bienvenue sur PLANIPRO', 'Votre espace entreprise est prêt.', 'info', 'pages/entreprise/dashboard.php'),
(2, 'Bienvenue', 'Votre compte a été créé par l\'entreprise. Demandez un rendez-vous depuis votre espace.', 'info', 'pages/client/entreprises.php'),
(4, 'Activité assignée', 'Une activité vous a été assignée.', 'info', 'pages/employe/activites.php');
