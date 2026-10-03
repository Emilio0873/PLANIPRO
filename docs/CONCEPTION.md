# PLANIPRO — Conception du système

Document de conception fonctionnelle et technique.  
Application web de gestion des rendez-vous, activités et échanges professionnels.

---

## 1. Vue d’ensemble

**PLANIPRO** permet à une entreprise de :

- recevoir et gérer des demandes de rendez-vous ;
- organiser les activités internes (visiteurs / équipe) ;
- gérer clients, partenaires et visiteurs ;
- communiquer via messagerie et messages publics ;
- visualiser le planning dans un calendrier ;
- suivre les notifications et produire des rapports.

**Stack technique**

| Couche | Technologie |
|--------|-------------|
| Backend | PHP 8+ (sessions, PDO) |
| Base de données | MySQL / MariaDB (`planipro`) |
| Frontend | HTML, CSS (`public/css/style.css`), JS |
| Serveur local | XAMPP (Apache + MySQL) |
| Calendrier | FullCalendar (CDN) |

**Organisation du code**

```
PLANIPRO/
├── index.php                 # Accueil public
├── config/database.php       # Connexion MySQL
├── includes/                 # Init, auth, helpers, messagerie
├── pages/                    # Interfaces par rôle
├── actions/                  # Traitements POST (CRUD, auth…)
├── public/                   # CSS, JS, assets
├── sql/schema.sql            # Schéma complet
└── uploads/                  # Logos entreprises
```

---

## 2. Acteurs (rôles)

Tous les acteurs sont des lignes de la table `users`, distingués par le champ `role`.

| Rôle technique | Libellé UI | Création du compte | Rôle principal |
|----------------|------------|--------------------|----------------|
| `entreprise` | Entreprise | Inscription publique | Pilote le planning, valide les RDV, gère contacts & visiteurs |
| `employe` | **Visiteur** | Créé par l’entreprise | Consulte ses RDV / activités, messagerie, calendrier |
| `client` | Client | Créé par l’entreprise | Demande des RDV, suit son historique |
| `partenaire` | Partenaire | Créé par l’entreprise | Demande des RDV / collaborations |

### 2.1 Entreprise

- Compte propriétaire lié à une fiche `entreprises` (`user_id`).
- Accès : tableau de bord, RDV, activités, visiteurs, contacts, calendrier, rapports, messagerie, messages publics, paramètres.
- Peut accepter / refuser / reporter / annuler / terminer un RDV.
- Peut créer des comptes client, partenaire, visiteur.

### 2.2 Visiteur (`employe`)

- Lié à une entreprise via `users.entreprise_id`.
- Voit **ses** rendez-vous et **ses** activités assignées.
- Peut annuler ses propres RDV et écrire à l’entreprise.

### 2.3 Client

- Profil étendu dans `clients`.
- Demande un RDV auprès de **son** entreprise de rattachement.
- Peut modifier une demande en attente et l’annuler.

### 2.4 Partenaire

- Profil étendu dans `partenaires`.
- Même logique de demandes de RDV que le client.

### 2.5 Visiteur public (non connecté)

- Personne qui contacte une entreprise depuis l’accueil (`contact_public`).
- Suit les réponses via cookie + `notifications_publiques` (sans compte obligatoire).
- Peut ensuite recevoir un compte **Visiteur** créé par l’entreprise.

---

## 3. Modèle de données (tables)

Base : `planipro` — encodage `utf8mb4`.

### 3.1 Schéma relationnel

```mermaid
erDiagram
    users {
        int id PK
        string email UK
        string role
        int entreprise_id FK
    }
    entreprises {
        int id PK
        int user_id FK_UK
        string raison_sociale
    }
    clients {
        int id PK
        int user_id FK_UK
    }
    partenaires {
        int id PK
        int user_id FK_UK
    }
    rendez_vous {
        int id PK
        int entreprise_id FK
        int demandeur_id FK
        string statut
    }
    activites {
        int id PK
        int entreprise_id FK
        int assigne_a FK
        int cree_par FK
    }
    notifications {
        int id PK
        int user_id FK
    }
    historique {
        int id PK
        int user_id FK
    }
    conversations {
        int id PK
        int participant_a FK
        int participant_b FK
    }
    messages {
        int id PK
        int conversation_id FK
        int sender_id FK
    }
    contact_public {
        int id PK
        int entreprise_id FK
        int expediteur_user_id FK
        string public_token UK
    }
    notifications_publiques {
        int id PK
        int contact_public_id FK
        int entreprise_id FK
        string public_token
    }

    users ||--o| entreprises : "possède (rôle entreprise)"
    entreprises ||--o{ users : "rattache (entreprise_id)"
    users ||--o| clients : "a pour profil"
    users ||--o| partenaires : "a pour profil"
    entreprises ||--o{ rendez_vous : "reçoit"
    users ||--o{ rendez_vous : "demande"
    entreprises ||--o{ activites : "organise"
    users ||--o{ activites : "crée"
    users ||--o{ activites : "est assigné"
    users ||--o{ notifications : "reçoit"
    users ||--o{ historique : "agit"
    users ||--o{ conversations : "participe_a"
    users ||--o{ conversations : "participe_b"
    conversations ||--o{ messages : "contient"
    users ||--o{ messages : "envoie"
    entreprises ||--o{ contact_public : "reçoit"
    users ||--o| contact_public : "peut être lié"
    contact_public ||--o{ notifications_publiques : "génère"
    entreprises ||--o{ notifications_publiques : "concerne"
```

### 3.2 Inventaire des tables

| # | Table | Rôle |
|---|--------|------|
| 1 | `users` | Comptes et authentification |
| 2 | `entreprises` | Fiche métier de l’entreprise |
| 3 | `clients` | Profil étendu client |
| 4 | `partenaires` | Profil étendu partenaire |
| 5 | `rendez_vous` | Demandes et créneaux RDV |
| 6 | `activites` | Tâches / activités internes |
| 7 | `notifications` | Alertes in-app (connectés) |
| 8 | `historique` | Journal des actions |
| 9 | `conversations` | Fils de messagerie 1–1 |
| 10 | `messages` | Messages d’une conversation |
| 11 | `contact_public` | Messages depuis l’accueil public |
| 12 | `notifications_publiques` | Alertes pour visiteurs non connectés |

---

### 3.3 Dictionnaire détaillé des tables

#### Table `users` — comptes

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | `INT UNSIGNED` | PK, AUTO_INCREMENT | Identifiant unique |
| `email` | `VARCHAR(190)` | NOT NULL, UNIQUE | Identifiant de connexion |
| `password_hash` | `VARCHAR(255)` | NOT NULL | Mot de passe hashé (bcrypt) |
| `role` | `ENUM` | NOT NULL | `entreprise`, `employe`, `client`, `partenaire` |
| `nom` | `VARCHAR(100)` | NOT NULL | Nom |
| `prenom` | `VARCHAR(100)` | NOT NULL | Prénom |
| `telephone` | `VARCHAR(30)` | NULL | Téléphone |
| `entreprise_id` | `INT UNSIGNED` | FK → `entreprises.id`, NULL | Rattachement (visiteur / client / partenaire) |
| `actif` | `TINYINT(1)` | NOT NULL, défaut `1` | Compte actif ou désactivé |
| `created_at` | `DATETIME` | NOT NULL, défaut NOW | Date de création |
| `updated_at` | `DATETIME` | NULL, ON UPDATE | Dernière mise à jour |

**Index :** `idx_users_role`, `idx_users_entreprise`

---

#### Table `entreprises` — fiche entreprise

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | `INT UNSIGNED` | PK, AUTO_INCREMENT | Identifiant |
| `user_id` | `INT UNSIGNED` | NOT NULL, UNIQUE, FK → `users.id` | Compte propriétaire |
| `raison_sociale` | `VARCHAR(200)` | NOT NULL | Nom commercial |
| `adresse` | `TEXT` | NULL | Adresse |
| `secteur` | `VARCHAR(120)` | NULL | Secteur d’activité |
| `description` | `TEXT` | NULL | Présentation |
| `site_web` | `VARCHAR(255)` | NULL | URL du site |
| `logo` | `VARCHAR(255)` | NULL | Fichier logo (`uploads/entreprises/`) |
| `created_at` | `DATETIME` | NOT NULL, défaut NOW | Date de création |

---

#### Table `clients` — profil client

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | `INT UNSIGNED` | PK, AUTO_INCREMENT | Identifiant |
| `user_id` | `INT UNSIGNED` | NOT NULL, UNIQUE, FK → `users.id` | Compte lié |
| `adresse` | `TEXT` | NULL | Adresse |
| `entreprise_nom` | `VARCHAR(200)` | NULL | Société du client |
| `notes` | `TEXT` | NULL | Notes internes |
| `created_at` | `DATETIME` | NOT NULL, défaut NOW | Date de création |

---

#### Table `partenaires` — profil partenaire

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | `INT UNSIGNED` | PK, AUTO_INCREMENT | Identifiant |
| `user_id` | `INT UNSIGNED` | NOT NULL, UNIQUE, FK → `users.id` | Compte lié |
| `organisation` | `VARCHAR(200)` | NULL | Organisation |
| `type_partenariat` | `VARCHAR(120)` | NULL | Type de partenariat |
| `adresse` | `TEXT` | NULL | Adresse |
| `notes` | `TEXT` | NULL | Notes internes |
| `created_at` | `DATETIME` | NOT NULL, défaut NOW | Date de création |

---

#### Table `rendez_vous` — rendez-vous

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | `INT UNSIGNED` | PK, AUTO_INCREMENT | Identifiant |
| `entreprise_id` | `INT UNSIGNED` | NOT NULL, FK → `entreprises.id` | Entreprise destinataire |
| `demandeur_id` | `INT UNSIGNED` | NOT NULL, FK → `users.id` | Demandeur |
| `type_demandeur` | `ENUM` | NOT NULL | `client`, `partenaire`, `employe` |
| `titre` | `VARCHAR(200)` | NOT NULL | Titre du RDV |
| `motif` | `TEXT` | NULL | Motif / détail |
| `date_debut` | `DATETIME` | NOT NULL | Début |
| `date_fin` | `DATETIME` | NOT NULL | Fin |
| `lieu` | `VARCHAR(255)` | NULL | Lieu |
| `statut` | `ENUM` | NOT NULL, défaut `en_attente` | Voir cycle ci-dessous |
| `commentaire_entreprise` | `TEXT` | NULL | Commentaire de décision |
| `created_at` | `DATETIME` | NOT NULL, défaut NOW | Création |
| `updated_at` | `DATETIME` | NULL, ON UPDATE | Mise à jour |

**Statuts :** `en_attente`, `accepte`, `refuse`, `reporte`, `annule`, `termine`  
**Index :** `idx_rdv_statut`, `idx_rdv_dates`

**Cycle de vie**

```
en_attente → accepte → termine
     │           ↓
     │        reporte → accepte (reconfirmation)
     ↓
  refuse / annule
```

---

#### Table `activites` — activités / tâches

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | `INT UNSIGNED` | PK, AUTO_INCREMENT | Identifiant |
| `entreprise_id` | `INT UNSIGNED` | NOT NULL, FK → `entreprises.id` | Entreprise propriétaire |
| `assigne_a` | `INT UNSIGNED` | FK → `users.id`, NULL | Responsable assigné |
| `cree_par` | `INT UNSIGNED` | NOT NULL, FK → `users.id` | Créateur |
| `titre` | `VARCHAR(200)` | NOT NULL | Titre |
| `description` | `TEXT` | NULL | Description |
| `priorite` | `ENUM` | NOT NULL, défaut `moyenne` | `basse`, `moyenne`, `haute`, `urgente` |
| `statut` | `ENUM` | NOT NULL, défaut `a_faire` | `a_faire`, `en_cours`, `terminee`, `annulee` |
| `progression` | `TINYINT UNSIGNED` | NOT NULL, défaut `0` | Avancement 0–100 % |
| `echeance` | `DATE` | NULL | Date limite (calendrier) |
| `created_at` | `DATETIME` | NOT NULL, défaut NOW | Création |
| `updated_at` | `DATETIME` | NULL, ON UPDATE | Mise à jour |

**Index :** `idx_act_statut`, `idx_act_priorite`

---

#### Table `notifications` — notifications in-app

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | `INT UNSIGNED` | PK, AUTO_INCREMENT | Identifiant |
| `user_id` | `INT UNSIGNED` | NOT NULL, FK → `users.id` | Destinataire |
| `titre` | `VARCHAR(200)` | NOT NULL | Titre |
| `message` | `TEXT` | NOT NULL | Contenu |
| `type` | `VARCHAR(50)` | défaut `info` | `info`, `success`, `warning`, `rappel`… |
| `lien` | `VARCHAR(255)` | NULL | Lien relatif vers une page |
| `lu` | `TINYINT(1)` | NOT NULL, défaut `0` | Lu / non lu |
| `created_at` | `DATETIME` | NOT NULL, défaut NOW | Date |

**Index :** `idx_notif_lu`

---

#### Table `historique` — journal d’actions

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | `INT UNSIGNED` | PK, AUTO_INCREMENT | Identifiant |
| `user_id` | `INT UNSIGNED` | FK → `users.id`, NULL | Auteur de l’action |
| `entite_type` | `VARCHAR(50)` | NOT NULL | Ex. `rendez_vous`, `activite` |
| `entite_id` | `INT UNSIGNED` | NOT NULL | Id de l’entité |
| `action` | `VARCHAR(100)` | NOT NULL | Ex. `accepter`, `reporter` |
| `details` | `TEXT` | NULL | Détail libre |
| `created_at` | `DATETIME` | NOT NULL, défaut NOW | Date |

**Index :** `idx_hist_entite (entite_type, entite_id)`

---

#### Table `conversations` — fils de discussion

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | `INT UNSIGNED` | PK, AUTO_INCREMENT | Identifiant |
| `participant_a` | `INT UNSIGNED` | NOT NULL, FK → `users.id` | 1er participant (id min) |
| `participant_b` | `INT UNSIGNED` | NOT NULL, FK → `users.id` | 2e participant (id max) |
| `last_message_at` | `DATETIME` | NOT NULL, défaut NOW | Dernier message |
| `created_at` | `DATETIME` | NOT NULL, défaut NOW | Création |

**Contrainte :** `UNIQUE (participant_a, participant_b)`  
**Index :** `idx_conv_last`

---

#### Table `messages` — messages

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | `INT UNSIGNED` | PK, AUTO_INCREMENT | Identifiant |
| `conversation_id` | `INT UNSIGNED` | NOT NULL, FK → `conversations.id` | Conversation |
| `sender_id` | `INT UNSIGNED` | NOT NULL, FK → `users.id` | Expéditeur |
| `body` | `TEXT` | NOT NULL | Contenu du message |
| `lu` | `TINYINT(1)` | NOT NULL, défaut `0` | Lu par le destinataire |
| `created_at` | `DATETIME` | NOT NULL, défaut NOW | Date d’envoi |

**Index :** `idx_msg_conv`, `idx_msg_lu`

---

#### Table `contact_public` — messages publics

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | `INT UNSIGNED` | PK, AUTO_INCREMENT | Identifiant |
| `entreprise_id` | `INT UNSIGNED` | NOT NULL, FK → `entreprises.id` | Entreprise contactée |
| `expediteur_user_id` | `INT UNSIGNED` | FK → `users.id`, NULL | Si compte lié / créé |
| `nom` | `VARCHAR(120)` | NOT NULL | Nom de l’expéditeur |
| `email` | `VARCHAR(190)` | NOT NULL | Email de contact |
| `telephone` | `VARCHAR(30)` | NULL | Téléphone |
| `sujet` | `VARCHAR(200)` | NOT NULL, défaut `Message` | Sujet |
| `message` | `TEXT` | NOT NULL | Corps du message |
| `public_token` | `VARCHAR(64)` | UNIQUE, NULL | Jeton de suivi (cookie) |
| `mdp_a_envoyer` | `VARCHAR(120)` | NULL | MDP temporaire (effacé après notif) |
| `notif_envoyee` | `TINYINT(1)` | NOT NULL, défaut `0` | Notif publique déjà envoyée |
| `lu` | `TINYINT(1)` | NOT NULL, défaut `0` | Lu par l’entreprise |
| `created_at` | `DATETIME` | NOT NULL, défaut NOW | Date |

**Index :** `idx_contact_ent`, `idx_contact_created`

---

#### Table `notifications_publiques` — alertes visiteurs publics

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | `INT UNSIGNED` | PK, AUTO_INCREMENT | Identifiant |
| `public_token` | `VARCHAR(64)` | NOT NULL | Jeton du destinataire |
| `contact_public_id` | `INT UNSIGNED` | FK → `contact_public.id`, NULL | Message d’origine |
| `entreprise_id` | `INT UNSIGNED` | NOT NULL, FK → `entreprises.id` | Entreprise concernée |
| `titre` | `VARCHAR(200)` | NOT NULL | Titre |
| `message` | `TEXT` | NOT NULL | Contenu (MDP masqué après lecture) |
| `type` | `VARCHAR(30)` | NOT NULL, défaut `info` | Type d’alerte |
| `lu` | `TINYINT(1)` | NOT NULL, défaut `0` | Lu / non lu |
| `created_at` | `DATETIME` | NOT NULL, défaut NOW | Date |

**Index :** `idx_np_token (public_token, lu)`

---

### 3.4 Relations entre tables

Légende de cardinalité :

| Notation | Signification |
|----------|----------------|
| **1** | Exactement un |
| **0..1** | Zéro ou un (optionnel) |
| **1..N** | Un ou plusieurs |
| **0..N** | Zéro ou plusieurs |

#### 3.4.1 Catalogue des relations

| # | Relation | Table A | Cardinalité | Table B | Clé étrangère | ON DELETE | Description métier |
|---|----------|---------|-------------|---------|---------------|-----------|--------------------|
| 1 | Possession | `users` | **1** — **0..1** | `entreprises` | `entreprises.user_id` → `users.id` | CASCADE | Un compte rôle `entreprise` possède **au plus une** fiche entreprise. Supprimer l’user → supprime l’entreprise. |
| 2 | Rattachement | `entreprises` | **1** — **0..N** | `users` | `users.entreprise_id` → `entreprises.id` | SET NULL | Une entreprise regroupe visiteurs, clients et partenaires. Supprimer l’entreprise → `entreprise_id` devient NULL. |
| 3 | Profil client | `users` | **1** — **0..1** | `clients` | `clients.user_id` → `users.id` | CASCADE | Un user `client` a **au plus un** profil `clients`. |
| 4 | Profil partenaire | `users` | **1** — **0..1** | `partenaires` | `partenaires.user_id` → `users.id` | CASCADE | Un user `partenaire` a **au plus un** profil `partenaires`. |
| 5 | RDV reçu | `entreprises` | **1** — **0..N** | `rendez_vous` | `rendez_vous.entreprise_id` → `entreprises.id` | CASCADE | Une entreprise reçoit plusieurs rendez-vous. |
| 6 | RDV demandé | `users` | **1** — **0..N** | `rendez_vous` | `rendez_vous.demandeur_id` → `users.id` | CASCADE | Un user (client / partenaire / visiteur) demande plusieurs RDV. |
| 7 | Activités de l’entreprise | `entreprises` | **1** — **0..N** | `activites` | `activites.entreprise_id` → `entreprises.id` | CASCADE | Les activités appartiennent à une entreprise. |
| 8 | Création d’activité | `users` | **1** — **0..N** | `activites` | `activites.cree_par` → `users.id` | CASCADE | Un user crée des activités. |
| 9 | Assignation | `users` | **0..1** — **0..N** | `activites` | `activites.assigne_a` → `users.id` | SET NULL | Une activité peut être assignée à un user (souvent visiteur). Suppression du user → activité non assignée. |
| 10 | Notifications | `users` | **1** — **0..N** | `notifications` | `notifications.user_id` → `users.id` | CASCADE | Chaque user connecté reçoit ses alertes. |
| 11 | Historique | `users` | **0..1** — **0..N** | `historique` | `historique.user_id` → `users.id` | SET NULL | Trace qui a fait l’action (peut rester si user effacé). |
| 12 | Conversation (A) | `users` | **1** — **0..N** | `conversations` | `conversations.participant_a` → `users.id` | CASCADE | Premier participant (id le plus petit). |
| 13 | Conversation (B) | `users` | **1** — **0..N** | `conversations` | `conversations.participant_b` → `users.id` | CASCADE | Second participant (id le plus grand). |
| 14 | Messages du fil | `conversations` | **1** — **1..N** | `messages` | `messages.conversation_id` → `conversations.id` | CASCADE | Une conversation contient plusieurs messages. |
| 15 | Expéditeur | `users` | **1** — **0..N** | `messages` | `messages.sender_id` → `users.id` | CASCADE | Un user envoie des messages. |
| 16 | Contact public | `entreprises` | **1** — **0..N** | `contact_public` | `contact_public.entreprise_id` → `entreprises.id` | CASCADE | Messages reçus depuis l’accueil. |
| 17 | Lien compte | `users` | **0..1** — **0..N** | `contact_public` | `contact_public.expediteur_user_id` → `users.id` | SET NULL | Message public éventuellement lié à un compte (connecté ou devenu visiteur). |
| 18 | Notif publique | `contact_public` | **0..1** — **0..N** | `notifications_publiques` | `notifications_publiques.contact_public_id` → `contact_public.id` | CASCADE | Réponses / identifiants liés au contact. |
| 19 | Notif ↔ entreprise | `entreprises` | **1** — **0..N** | `notifications_publiques` | `notifications_publiques.entreprise_id` → `entreprises.id` | CASCADE | Chaque notif publique concerne une entreprise. |

#### 3.4.2 Relations détaillées (texte)

**Identité & profils**

- `users` **1 — 0..1** `entreprises` : le propriétaire du compte entreprise.
- `entreprises` **1 — 0..N** `users` : membres rattachés (`employe`, `client`, `partenaire`) via `entreprise_id`.
- `users` **1 — 0..1** `clients` / `partenaires` : extension de profil selon le rôle.

**Planning**

- `entreprises` **1 — 0..N** `rendez_vous` : l’entreprise gère les demandes.
- `users` **1 — 0..N** `rendez_vous` : le demandeur (client, partenaire ou visiteur).
- Un RDV relie donc **toujours** une entreprise **et** un demandeur (relation ternaire logique A–B–C).
- `entreprises` **1 — 0..N** `activites`.
- `users` crée (`cree_par`) et peut être assigné (`assigne_a`, optionnel).

**Communication**

- Deux `users` ↔ une `conversations` (contrainte UNIQUE sur la paire).
- `conversations` **1 — N** `messages`.
- `entreprises` **1 — N** `contact_public`.
- `contact_public` **1 — N** `notifications_publiques` (suivi anonyme via `public_token`).

**Suivi**

- `users` **1 — N** `notifications` (in-app).
- `users` **0..1 — N** `historique` (audit).

#### 3.4.3 Diagramme des cardinalités (vue simplifiée)

```
users 1 ──────── 0..1 entreprises
  │                    │
  │                    ├── 0..N users (rattachement)
  │                    ├── 0..N rendez_vous
  │                    ├── 0..N activites
  │                    ├── 0..N contact_public
  │                    └── 0..N notifications_publiques
  │
  ├── 0..1 clients
  ├── 0..1 partenaires
  ├── 0..N rendez_vous (demandeur)
  ├── 0..N activites (crée / assigné)
  ├── 0..N notifications
  ├── 0..N historique
  ├── 0..N conversations (participant_a / b)
  └── 0..N messages (expéditeur)

conversations 1 ─── N messages
contact_public 0..1 ─── N notifications_publiques
```

#### 3.4.4 Clés étrangères techniques

| De | Vers | ON DELETE |
|----|------|-----------|
| `entreprises.user_id` | `users.id` | CASCADE |
| `users.entreprise_id` | `entreprises.id` | SET NULL |
| `clients.user_id` | `users.id` | CASCADE |
| `partenaires.user_id` | `users.id` | CASCADE |
| `rendez_vous.entreprise_id` | `entreprises.id` | CASCADE |
| `rendez_vous.demandeur_id` | `users.id` | CASCADE |
| `activites.entreprise_id` | `entreprises.id` | CASCADE |
| `activites.assigne_a` | `users.id` | SET NULL |
| `activites.cree_par` | `users.id` | CASCADE |
| `notifications.user_id` | `users.id` | CASCADE |
| `historique.user_id` | `users.id` | SET NULL |
| `conversations.participant_a` | `users.id` | CASCADE |
| `conversations.participant_b` | `users.id` | CASCADE |
| `messages.conversation_id` | `conversations.id` | CASCADE |
| `messages.sender_id` | `users.id` | CASCADE |
| `contact_public.entreprise_id` | `entreprises.id` | CASCADE |
| `contact_public.expediteur_user_id` | `users.id` | SET NULL |
| `notifications_publiques.contact_public_id` | `contact_public.id` | CASCADE |
| `notifications_publiques.entreprise_id` | `entreprises.id` | CASCADE |

Source SQL : [`sql/schema.sql`](../sql/schema.sql).

---

## 4. Fonctionnalités par module

### 4.1 Authentification

- Connexion unifiée (`pages/auth/login.php`).
- Inscription publique **réservée aux entreprises**.
- Sessions PHP + jeton CSRF sur les formulaires POST.
- Contrôle d’accès : `require_login()` / `require_role()`.

### 4.2 Accueil public

- Liste des entreprises.
- Contact d’une entreprise (`pages/public/contacter.php`).
- Suivi des réponses (`pages/public/notifications.php`) via cookie `planipro_public`.

### 4.3 Rendez-vous

| Acteur | Capacités |
|--------|-----------|
| Entreprise | Créer, filtrer, confirmer, refuser, reporter, annuler, terminer ; ouvrir messagerie ; créer compte visiteur depuis un RDV |
| Client / Partenaire | Demander, modifier (si en attente), annuler |
| Visiteur | Voir ses RDV, annuler, contacter l’entreprise |

### 4.4 Activités

- Création / édition / suppression par l’entreprise.
- Assignation à un visiteur.
- Suivi statut + progression.
- Visiteur : consulte / suit les activités qui lui sont assignées.

### 4.5 Calendrier

- Vues mois / semaine / jour / liste (FullCalendar).
- Événements : RDV + activités avec échéance.
- Clic → menu de détail.
- Responsive : vue liste sur mobile.

### 4.6 Contacts & visiteurs

- **Contacts** : clients et partenaires (création, activation).
- **Visiteurs** : comptes `employe`, notifications d’identifiants, messagerie.

### 4.7 Messagerie

- Conversations 1–1 entre utilisateurs autorisés.
- Règles : même entreprise, écriture vers une entreprise, réponse entreprise aux contacts liés.
- Si un utilisateur connecté écrit via le formulaire public → conversation créée automatiquement.

### 4.8 Notifications & rappels

- Notifications in-app (cloche).
- Rappels automatiques pour les RDV dans les 24 h (`includes/reminders.php`).
- Notifications publiques pour les contacts externes.

### 4.9 Rapports

- Synthèses côté entreprise.
- Export PDF / impression.

### 4.10 Paramètres

- Compte (nom, téléphone, mot de passe).
- Infos entreprise (raison sociale, logo, site web…).

---

## 5. Architecture applicative

```
Navigateur
    │
    ▼
Apache (XAMPP)
    │
    ├── pages/*        → affichage (HTML)
    ├── actions/*      → traitements (POST + redirect)
    └── includes/*     → auth, helpers, messaging, init
            │
            ▼
        MySQL (planipro)
```

**Principes**

1. Une page affiche ; une action traite et redirige (PRG).
2. Les requêtes SQL passent par PDO préparé.
3. Les sorties HTML sont échappées (`e()`).
4. Les rôles isolent les données via `entreprise_id` / `demandeur_id` / `assigne_a`.

---

## 6. Matrice des droits (synthèse)

| Fonction | Entreprise | Visiteur | Client | Partenaire | Public |
|----------|:----------:|:--------:|:------:|:----------:|:------:|
| Inscription | ✓ | — | — | — | Voir entreprises |
| Gérer RDV (validation) | ✓ | — | — | — | — |
| Demander RDV | ✓ (création) | — | ✓ | ✓ | Via message |
| Activités | ✓ | Lecture assignées | — | — | — |
| Calendrier | ✓ | Ses événements | — | — | — |
| Messagerie | ✓ | ✓ | ✓ | ✓ | — |
| Messages publics | ✓ | — | — | — | Écrire / suivre |
| Rapports | ✓ | — | — | — | — |
| Paramètres | ✓ | ✓ | ✓ | ✓ | — |

---

## 7. Flux métier importants

### 7.1 Demande de rendez-vous (client / partenaire)

1. L’utilisateur choisit l’entreprise / le créneau.
2. Insertion `rendez_vous` en `en_attente`.
3. Notification à l’entreprise.
4. L’entreprise confirme, refuse ou reporte.
5. Notification au demandeur ; événement visible au calendrier si accepté / reporté.

### 7.2 Contact public → compte visiteur

1. Visiteur public envoie un message (`contact_public` + token).
2. L’entreprise lit le message, peut créer un compte visiteur.
3. Envoi d’une notification publique (identifiants, affichage unique du mot de passe).
4. Le visiteur se connecte avec le rôle `employe`.

### 7.3 Messagerie depuis un RDV

1. Depuis la liste RDV, bouton **Messagerie**.
2. Ouverture de `pages/messagerie/index.php?nouveau=1&to={userId}`.
3. Conversation créée au premier envoi (`find_or_create_conversation`).

---

## 8. Sécurité (points clés)

- Mots de passe hashés (`password_hash` / `password_verify`).
- Protection CSRF sur les actions POST.
- Contrôle de rôle sur chaque page / action sensible.
- Isolation multi-entreprises via `entreprise_id`.
- URLs externes limitées à `http` / `https`.
- Redirections internes sécurisées (anti open-redirect).
- Mot de passe public masqué après lecture de la notification.

---

## 9. Installation (rappel)

1. Placer le projet dans `htdocs/PLANIPRO`.
2. Démarrer Apache + MySQL (XAMPP).
3. Importer `sql/schema.sql` puis `sql/seed.sql`.
4. Ouvrir `http://localhost/PLANIPRO/`.

Comptes démo (mot de passe : `password`) : voir `README.md`.

---

## 10. Évolutions possibles

- Reset de mot de passe par e-mail (sans envoi en clair).
- Pièces jointes en messagerie.
- Disponibilités / créneaux bloqués.
- API REST pour mobile.
- Migrations SQL versionnées (au lieu de scripts partiels).

---

*Document généré pour PLANIPRO — conception acteurs, tables et fonctionnalités.*
