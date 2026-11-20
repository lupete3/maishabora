# Sauvegarde de la base depuis l'application (20/11/2026)

Page : **Sauvegarde de la base** (menu, rôle **SUPER IT**) → `/sauvegarde-base`.

## Ce que fait le bouton
Il télécharge une **copie complète de la base** au format **`.sql.gz`** (SQL compressé), qu'on peut réimporter dans MySQL :

```bash
gzip -dc sauvegarde_imf_2026-11-20_18-25.sql.gz | mysql -u UTILISATEUR -p NOM_BASE
```

Sous Windows, on peut d'abord décompresser le fichier avec 7-Zip, puis l'importer avec phpMyAdmin ou HeidiSQL.

## Sécurité
- **Accès** : permission dédiée `exporter-base-donnees`, attribuée au **seul rôle SUPER IT** par la migration `2026_11_20_120000_add_database_export_permission.php`.
- **Mot de passe redemandé** avant chaque téléchargement (middleware `password.confirm`). Une session restée ouverte ne suffit donc pas.
- **Journal** : chaque export est enregistré dans les journaux d'activité (`Export_base_donnees`), avec l'auteur, la date, le nombre de tables et de lignes, la taille et la durée. Les échecs sont aussi enregistrés. Les 10 derniers exports s'affichent sur la page.
- **Aucune copie sur le serveur** : le fichier est compressé et envoyé au fil de l'eau au navigateur, sans être écrit sur le disque.
- **Limite** : une sauvegarde au maximum toutes les 10 minutes, pour ménager le serveur.
- Le fichier contient **toutes les données** (identités, soldes, opérations, mots de passe chiffrés). Il faut le conserver sur un support sûr et ne jamais l'envoyer par WhatsApp ni par e-mail.

## Fonctionnement technique (`app/Services/DatabaseBackupService.php`)
- **Écrit entièrement en PHP**, sans `mysqldump` : il fonctionne aussi en hébergement mutualisé, où l'exécution de programmes est souvent interdite.
- **Photo cohérente** : une transaction `START TRANSACTION WITH CONSISTENT SNAPSHOT` fige la lecture au moment du clic, sans bloquer les opérations en cours (moteur InnoDB).
- **Mémoire faible** : connexion dédiée en lecture non tamponnée, avec des lignes regroupées par 200 dans les `INSERT`.
- **Fuseau UTC** pendant l'export et dans le fichier : les dates (`TIMESTAMP`) sont restaurées à l'identique.
- **Contenu du fichier** : `DROP TABLE IF EXISTS` puis `CREATE TABLE` pour chaque table, avec les contraintes désactivées pendant l'import. Les tables temporaires (`sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`) sont exportées **sans contenu**.
- **En cas d'erreur pendant l'export**, le fichier se termine par « ERREUR : SAUVEGARDE INCOMPLÈTE » et l'échec est journalisé.

## Vérifié (base locale)
- **Export** : 71 tables, 117 820 lignes, **2,2 Mo** compressés, environ 4 à 5 s, 48 Mo de mémoire.
- **Restauration** dans une base séparée `imf_restauration_test` : import sans erreur en 41 s.
  - Les **66 tables** de données sont identiques (même nombre de lignes).
  - L'empreinte du contenu est identique pour `accounts`, `transactions`, `users` et `repayments`.
  - La somme des soldes est la même.
- **Droits** : SUPER IT peut exporter, l'Admin non. Sans confirmation du mot de passe, le lien redirige vers l'écran de confirmation. Un deuxième export immédiat est refusé.

## Fichiers
| Rôle | Fichier |
|---|---|
| Permission | `database/migrations/2026_11_20_120000_add_database_export_permission.php` |
| Export | `app/Services/DatabaseBackupService.php` |
| Page et téléchargement | `app/Http/Controllers/DatabaseBackupController.php`, `resources/views/admin/sauvegarde-base.blade.php` |
| Routes | `routes/web.php` : `sauvegarde.index`, `sauvegarde.download` (+ `password.confirm`) |
| Menu | `resources/views/layouts/partials/aside.blade.php` |
| Journal | `app/Models/UserLog.php` : relation `user()` |

## Déploiement
```bash
php artisan migrate
php artisan optimize:clear
```

## Conseillé ensuite
Une **sauvegarde automatique quotidienne**, programmée la nuit et conservée 7 jours dans un dossier privé du serveur, avec une copie hors du serveur. Le bouton reste utile pour une sauvegarde instantanée, avant une opération sensible.
