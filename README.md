# TP Calendrier Dynamique - PHP/MySQL

Ce projet est un calendrier interactif permettant de gérer des événements stockés en base de données.

## Fonctionnalités
- **Affichage dynamique** : Les jours et les mois se calculent automatiquement.
- **Navigation** : Flèches pour changer de mois et d'année.
- **Système CRUD** : Ajouter, afficher, modifier et supprimer des événements.
- **Gestion Utilisateur** : Utilisation d'un cookie pour que seul le créateur d'un événement puisse le modifier ou le supprimer.
- **Images** : Possibilité d'associer une image (.jpg) à un événement.

## Installation pour le test
1. Importer le fichier `calendrier_db.sql` dans votre interface phpMyAdmin.
2. Placer les fichiers dans votre dossier `htdocs` (MAMP).
3. Vérifier la connexion PDO dans `index.php` (configuré par défaut sur root/root).
4. Le dossier `upload` doit être présent à la racine pour le fonctionnement des images.