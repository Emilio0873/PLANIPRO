# PLANIPRO

Système web de gestion des rendez-vous et des activités professionnelles.

## Prérequis

- XAMPP (Apache + MySQL + PHP 8+)
- Navigateur moderne

## Installation

1. Placez le projet dans `C:\xampp\htdocs\PLANIPRO` (déjà le cas).
2. Démarrez **Apache** et **MySQL** dans le panneau XAMPP.
3. Importez la base de données :

### Option A — phpMyAdmin

1. Ouvrez http://localhost/phpmyadmin
2. Onglet **Importer** → sélectionnez `sql/schema.sql` → Exécuter
3. Importez ensuite `sql/seed.sql` (données de démo)

### Option B — ligne de commande

```bash
C:\xampp\mysql\bin\mysql.exe -u root < C:\xampp\htdocs\PLANIPRO\sql\schema.sql
C:\xampp\mysql\bin\mysql.exe -u root < C:\xampp\htdocs\PLANIPRO\sql\seed.sql
```

4. Accédez à l'application : **http://localhost/PLANIPRO/**

## Comptes de démonstration

Mot de passe pour tous : `password`

| Rôle | Email | Comment |
|------|-------|---------|
| Entreprise | entreprise@planipro.local | S'inscrit publiquement |
| Visiteur | employe@planipro.local | Créé par l'entreprise |
| Client | client@planipro.local | Créé par l'entreprise |
| Partenaire | partenaire@planipro.local | Créé par l'entreprise |

**Inscription publique :** entreprises uniquement. Clients et partenaires sont créés depuis l'espace Entreprise → Contacts.

## Fonctionnalités

- Inscription / connexion unifiée (entreprise, client, partenaire)
- Visiteurs créés par l'entreprise (même login)
- Demandes de rendez-vous, validation, refus, report, annulation
- Activités avec priorités, échéances, progression et affectation
- Messagerie intégrée (réponses aux contacts publics)
- Calendrier (jour / semaine / mois)
- Notifications in-app
- Rapports + export PDF / impression

## Documentation

- Conception du système (acteurs, tables, fonctionnalités) : [`docs/CONCEPTION.md`](docs/CONCEPTION.md)

## Configuration

Fichier `config/database.php` : hôtes MySQL, utilisateur `root`, mot de passe vide (défaut XAMPP).
