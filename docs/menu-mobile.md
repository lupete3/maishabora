# Menu latéral sur téléphone (07/11/2026)

## Problèmes constatés
1. **Impossible d'atteindre les menus du bas** sur téléphone (ex. « Simulation Crédit »), surtout pour les profils qui ont beaucoup de menus (SUPER IT).
2. **Un clic à côté du menu ne le fermait pas**, alors que c'est le comportement attendu.
3. Une erreur JavaScript dans la console : `redit is not defined` (`public/assets/js/main.js`, ligne 117).

## Cause
Le script du menu (`initMain` dans `public/assets/js/main.js`) s'exécutait **deux fois par page** : au chargement, puis sur l'événement `livewire:navigated`, que Livewire 3 émet aussi au premier chargement. Conséquences :
- **deux écouteurs de clic** sur chaque bouton d'ouverture et sur la zone grisée. Un clic appelait la fermeture deux fois : le menu se fermait puis se rouvrait aussitôt ;
- **deux barres de défilement PerfectScrollbar** superposées sur le menu. Sur écran tactile, elles se gênaient et le défilement se bloquait ;
- le gabarit prévoit le défilement natif sur petit écran, mais il ne l'appliquait que lors d'un redimensionnement de la fenêtre, jamais au chargement.

S'y ajoutaient un mot parasite `redit` en fin de ligne et les événements `livewire:load` et `livewire:update`, propres à Livewire 2.

## Corrections (`public/assets/js/main.js`)
- Le menu, chaque bouton d'ouverture et chaque accordéon ne sont initialisés **qu'une seule fois**, grâce aux marques `data-menu-init`, `data-toggle-init` et `data-accordion-init`.
- Après la création du menu, l'appel à `menu.manageScroll()` donne :
  - **sur téléphone et tablette** (moins de 1200 px) : le **défilement natif** (`overflow-auto`), fluide au doigt ;
  - **sur ordinateur** : la barre de défilement existante est conservée (une seule).
- Les infobulles utilisent `bootstrap.Tooltip.getOrCreateInstance`, ce qui évite les doublons.
- Suppression du mot parasite `redit` et des écouteurs Livewire 2. Seul `livewire:navigated` est conservé, sans risque de double installation.

## Rechargement du script chez les utilisateurs
Les navigateurs gardaient l'ancien `main.js` en cache. Le lien du script porte maintenant un numéro de version basé sur la date du fichier (`main.js?v=…`), dans `layouts/backend`, `layouts/guest` et `not-found`. À chaque modification du fichier, les navigateurs téléchargent automatiquement la nouvelle version.

## Vérifié (navigateur intégré)
| Test | Téléphone (375 px) | Ordinateur (1400 px) |
|---|---|---|
| Barres de défilement sur le menu | 0 (défilement natif) | 1 (au lieu de 2) |
| Dernier menu « Simulation Crédit » atteignable | oui | oui |
| Ouverture du menu | 1 seul appel | — |
| Clic à côté du menu | se ferme (1 seul appel au lieu de 2) | — |
| Flèche de fermeture | fonctionne | — |
| Erreur `redit` dans la console | disparue | disparue |

Sur la page Simulation Crédit, le menu s'ouvre directement positionné sur l'élément actif, en bas de la liste.
