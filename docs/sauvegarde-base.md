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
- **Mot de passe redemandé** à l'**ouverture de la page** (middleware `password.confirm` sur `sauvegarde.index`). Le téléchargement n'est accepté que si cette confirmation date de **moins de 15 minutes** ; sinon il renvoie vers la page, qui redemande le mot de passe. Une session restée ouverte ne suffit donc pas.
  - *Correction du 21/11/2026* : au départ, la confirmation était placée sur le lien de téléchargement lui-même. La page de confirmation de Laravel redirige en **navigation Livewire** (`navigate: true`), qui charge la destination en arrière-plan comme une page HTML. Recevant un fichier compressé, le navigateur se figeait (« Page ne répondant pas ») et rien ne se téléchargeait. La confirmation se fait désormais sur la page, et le bouton est un lien classique, que le navigateur télécharge normalement.
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

## Sauvegarde automatique (21/11/2026)

### Principe
Le cron de l'application passe par un **service externe (cron-job.org)** qui appelle des adresses web, et non par le planificateur de Laravel. La sauvegarde automatique suit donc le même modèle :

- **Adresse** : `https://client.maishaboraasbl.com/cron/sauvegarde/<JETON>`, appelée par cron-job.org.
- **Au plus une sauvegarde par tranche de 20 h** : si une sauvegarde récente existe, l'appel ne fait rien. On peut donc programmer l'appel **toutes les heures**, sans doublon et sans rater une journée si un appel échoue.
- **La réponse part tout de suite** (« Sauvegarde lancée ») et la sauvegarde se fait **juste après**. Elle ne peut donc pas être coupée par la limite de temps de cron-job.org.
- **Stockage** : fichiers `.sql.gz` dans `storage/app/private/backups`, un dossier non accessible par le web.
- **Vérification** : écriture dans un fichier `.part`, relecture complète (gzip lisible et ligne « Fin de la sauvegarde » présente), puis renommage. Une sauvegarde incomplète est supprimée, jamais conservée.
- **Conservation de 7 jours** : les plus anciennes sont supprimées, la plus récente est toujours gardée.
- **Sécurité** : la sauvegarde refuse de démarrer si l'espace disque libre est inférieur à 200 Mo, et un verrou empêche deux sauvegardes simultanées.
- **En cas d'échec** : ligne `error` dans les **Logs du système**, et **notification** aux comptes SUPER IT.
- **Page « Sauvegarde de la base »** :
  - la liste des sauvegardes conservées, chacune téléchargeable avec la même confirmation du mot de passe ;
  - **une alerte rouge** si la dernière a plus de 26 h (l'appel cron ne fonctionne plus) ;
  - le bouton **« Créer une sauvegarde sur le serveur maintenant »**.
- **En ligne de commande** : `php artisan backup:database` (immédiate) ou `php artisan backup:database --si-necessaire`.

### Mise en place en production (à faire une fois)
1. **Générer un jeton secret**, une longue suite aléatoire, par exemple :
   ```bash
   php -r "echo bin2hex(random_bytes(24)), PHP_EOL;"
   ```
2. **L'ajouter au fichier `.env` du serveur** :
   ```
   CRON_SAUVEGARDE_TOKEN=le_jeton_genere
   ```
   puis lancer `php artisan optimize:clear`.
3. **Sur cron-job.org**, créer une tâche :
   - URL : `https://client.maishaboraasbl.com/cron/sauvegarde/le_jeton_genere` ;
   - fréquence : **toutes les heures** (ou au minimum une fois par nuit, vers 02:00).
4. Le lendemain, vérifier sur la page **Sauvegarde de la base** qu'une sauvegarde automatique apparaît, sans alerte rouge.

Réglages facultatifs du `.env` :
- `SAUVEGARDE_RETENTION_JOURS` (7 par défaut) ;
- `SAUVEGARDE_INTERVALLE_HEURES` (20) ;
- `SAUVEGARDE_ALERTE_HEURES` (26) ;
- `SAUVEGARDE_ESPACE_MINIMAL_MO` (200).

### Jeton de l'adresse des retards
L'adresse existante `/cron/executer-retards/<jeton>` avait son jeton **écrit en clair dans le code**, donc visible par toute personne ayant accès au dépôt. Il est désormais lu dans `.env` (`CRON_RETARDS_TOKEN`) et comparé de façon sûre (`hash_equals`). Sans cette variable, l'**ancien jeton reste valable**, et la tâche cron-job.org actuelle continue de fonctionner.

**Conseillé** : définir un nouveau `CRON_RETARDS_TOKEN` dans le `.env`, puis mettre à jour l'adresse sur cron-job.org.

### Vérifié (local)
- Sauvegarde créée et vérifiée : 2,2 Mo, 71 tables, en 2,6 à 4,8 s.
- Un second appel avec `--si-necessaire` ne fait rien.
- Conservation : les 2 faux fichiers de 9 et 10 jours sont supprimés, celui de 3 jours est gardé.
- Un nom de fichier malveillant (`../../.env`) est refusé.
- Adresse cron :
  - un mauvais jeton est rejeté (403) ;
  - un bon jeton sans besoin ne fait rien ;
  - un bon jeton avec besoin répond tout de suite, puis la sauvegarde se fait et est journalisée.
- Échec simulé (espace disque) : journal `error`, notification SUPER IT, aucun fichier `.part` restant.

### Fichiers
| Rôle | Fichier |
|---|---|
| Réglages et jetons | `config/sauvegarde.php` |
| Sauvegardes sur le serveur | `app/Services/AutomaticBackupService.php` |
| Commande | `app/Console/Commands/BackupDatabase.php` (`backup:database`) |
| Adresses cron | `routes/web.php` : `/cron/sauvegarde/{token}`, `/cron/executer-retards/{token}` |
| Page | `DatabaseBackupController` (`downloadFile`, `createNow`), `resources/views/admin/sauvegarde-base.blade.php` |

### À vérifier (constaté pendant l'analyse)
Les tâches programmées dans Laravel (`routes/console.php`, `bootstrap/app.php`) **ne tournent que si le planificateur est lancé**, ce que ne fait pas cron-job.org aujourd'hui. Sont concernés :
- **le calcul quotidien des intérêts des dépôts à terme** (`term-deposits:accrue-monthly-interest`) ;
- **le rapport mensuel des cotisations** (`reports:monthly-contribution`).

À contrôler en production, et à brancher sur cron-job.org de la même façon si besoin.

## Conseillé ensuite
Une **copie hors du serveur** des sauvegardes automatiques (Google Drive par exemple). Si le serveur était perdu, les sauvegardes stockées dessus le seraient aussi.
