# Caisse des agents : historique paginé (17/11/2026)

Page : `/tableau-de-bord-agent` (composant `App\Livewire\Agent\AgentDashboard`).

## Problème
Que ce soit en production ou en local, la fenêtre « Modifier le solde agent » ou « Modifier la transaction » s'ouvrait bien. En revanche, au clic sur **Mettre à jour**, le sablier (`wire:loading`) tournait sans fin et rien n'était validé.

## Cause
L'historique d'un agent était chargé **en entier** (`->get()`, sans pagination) et stocké dans une **propriété publique Livewire** (`$transactions`). Livewire renvoie, recharge et réaffiche les propriétés publiques **à chaque clic** sur la page. En plus, après chaque rectification, modification ou suppression, le composant rechargeait encore toute la liste.

Pour un agent actif (par exemple plus de 24 000 transactions au total pour la caisse 03), chaque clic transportait des milliers de lignes. Avec la croissance des données, l'enregistrement a fini par dépasser le temps maximal accordé par le serveur : la réponse n'arrivait jamais.

## Correction
- La liste n'est plus une propriété publique. Le composant ne garde que l'agent, le filtre et les dates. Les transactions sont calculées dans `render()` par `transactionHistory()`.
- **Pagination à 50 transactions par page** (`TRANSACTIONS_PER_PAGE`), avec un paramètre de page dédié, `txPage`. Le texte « Transactions 1 à 50 sur N » et les liens de pagination s'affichent sous la liste.
- Le **nombre total** et les **totaux par devise** restent calculés sur **toute la période**, directement en base (`COUNT`, `SUM … GROUP BY currency`).
- Après une rectification de solde, une modification ou une suppression, la fenêtre se ferme et seule la page affichée est recalculée. Le rechargement complet a été supprimé.
- Inchangé : les boutons, les fenêtres, les permissions (`modifier-solde-compte`), les messages et l'export PDF/Excel (qui lit toujours toute la période, à part).

## Deuxième cause : composants imbriqués détruits (vraie cause du sablier bloqué)

Reproduit dans le navigateur le 17/11/2026. Le composant « Performance agents » (`agent.agent-performance`, ajouté le 06/11/2026) et le tableau de bord crédit (`credit.credit-overview`) étaient **imbriqués dans** `agent-dashboard`.

- Au premier clic (ouverture d'une fenêtre), Livewire rafraîchit la caisse, et la fenêtre s'insère au-dessus du composant imbriqué. Livewire **détruit** alors ce composant enfant.
- Au clic suivant (« Mettre à jour », « Annuler », la croix), Livewire cherche ce composant disparu. La console affiche `Uncaught Component not found: …`, la réponse n'est jamais appliquée, et le sablier `wire:loading` reste affiché indéfiniment.

**Correction** : ces deux composants sont maintenant placés **dans la page** `resources/views/agent-dashboard.blade.php`, à côté de `<livewire:agent.agent-dashboard lazy />`, et non plus à l'intérieur. Le titre « Caisse des agents » et le bouton « Performance agents » y ont été déplacés : l'affichage reste identique. Les rafraîchissements de la caisse ne touchent plus ces composants.

Vérifié dans le navigateur, sans aucune erreur JavaScript : ouverture et fermeture de la fenêtre de rectification, historique de l'année (4 831 transactions, 50 par page, page 2), ouverture puis validation de la modification d'une transaction, et ouverture du bouton « Performance agents ». Chaque action répond en moins d'une seconde.

**Accueil des agents** : les agents (rôle Recouvreur) n'ont pas accès à `/tableau-de-bord-agent`. Ils voient leur caisse sur leur **tableau de bord d'accueil** (`resources/views/dashboard.blade.php`), qui intègre directement le composant de la caisse. La situation des crédits (`credit.credit-overview`, permission `afficher-tableaudebord-recouvreur`) y est donc aussi placée à côté de la caisse, avec le titre « Caisse des agents ». Vérifié pour deux agents : la caisse et les crédits s'affichent, et le composant des crédits se rend sans erreur.

**Règle pour la suite** : ne pas imbriquer un composant Livewire dans un composant dont des blocs conditionnels (`@if`) changent la structure au-dessus de lui. Le placer plutôt dans la page, à côté.

## Tests
`tests/Feature/AgentDashboardTest.php`, sur une base SQLite en mémoire, sans données réelles :
- l'historique est paginé (50 lignes chargées sur 120), les totaux couvrent toute la période, et la liste n'est plus dans l'état Livewire ;
- la rectification du solde enregistre le nouveau solde, crée la transaction `rectification_solde_agent` et ferme la fenêtre ;
- la modification puis la suppression d'une transaction fonctionnent depuis l'historique.

## Problème distinct constaté en local (poste de développement)
Le serveur MySQL de Laragon bloquait sur l'erreur système 32 : *« un autre programme utilise les fichiers d'InnoDB »*, très probablement l'antivirus Windows Defender. Toute requête sur `transactions` restait alors en attente, et MySQL ne parvenait plus à s'arrêter proprement. Pour corriger :
1. exclure `C:\laragon\data` dans Sécurité Windows › Protection contre les virus et menaces › Exclusions ;
2. arrêter puis redémarrer MySQL dans Laragon, en terminant si besoin les processus `mysqld.exe` restés bloqués.

Ce problème ne concerne pas la production.
