# Réorganisation pour le déploiement — 13 septembre 2026

Travail réalisé sans sous-agents, en conservant les corrections du commit `5e38516`. Aucun déploiement externe ni modification des données métier.

## Déplacements

| Ancien emplacement | Emplacement actuel |
| --- | --- |
| Les 14 pages HTML à la racine | `public/*.html` |
| `assets/styles.css` | `public/assets/css/styles.css` |
| `assets/app.js` | `public/assets/js/app.js` |
| `logo.jpeg` | `public/assets/images/logo.jpeg` |
| `backend/public/index.php` et `.htaccess` | `public/index.php` et `.htaccess` |
| `Amanah.html` | `archive/prototypes/Amanah.html` |
| `PLAN_REFONTE_AMANAH.md` | `docs/plans/PLAN_REFONTE_AMANAH.md` |
| `AUDIT_REFONTE_AMANAH.md` et `audit/preuves/` | `docs/audits/` et `docs/audits/preuves/` |

Les CSS, JavaScript, logo et prototype sont identiques octet par octet à leurs versions précédentes. Les pages ont été adaptées aux nouveaux chemins de ressources. Les liens de documentation ont été relocalisés ; les preuves historiques sont conservées sans réécriture.

## Adaptations

- Le contrôleur public charge désormais `backend/src/bootstrap.php` depuis son nouvel emplacement.
- Apache choisit explicitement `index.html` comme accueil et transmet les routes dynamiques au contrôleur PHP. Les chemins cachés sont interdits.
- `tools/router.php` permet de tester localement cette séparation ; la commande Composer a été adaptée.
- `backend/storage/` reste privé et son contenu est ignoré par Git. Les dossiers locaux d'assistants sont conservés et ignorés, y compris `.swarm/`.
- Les guides racine, backend et Infomaniak expliquent le lancement et la racine web unique.

Un blocage PHP préexistant a été corrigé dans le contrôleur : la fonction fléchée déclarée `void` retournait implicitement une expression (`Session::start()`), ce que PHP refuse à la compilation. Elle est remplacée par une fermeture sans retour. Cette erreur empêchait toutes les routes PHP de fonctionner.

## Vérifications réalisées

- PHP 8.5.10 : syntaxe valide pour les 27 fichiers PHP applicatifs et outils ; smoke tests réussis avec assertions activées.
- 14 pages, 306 références locales contrôlées : aucun fichier cible manquant.
- Serveur PHP local avec SQLite en mémoire : accueil, pages, CSS, JS, logo, `/up` et `/session/csrf` répondent en 200 avec les types de contenu attendus.
- `/backend/.env`, `/.git/config` et `/.htaccess` répondent en 403 ; les documents, le prototype archivé et une route inexistante répondent en 404.
- Chrome sans interface, accueil en 1440 et 390 pixels : CSS chargé, aucun débordement horizontal, aucune erreur JavaScript. Les trois logos sont chargés après défilement, celui du pied de page utilisant le chargement différé.
- `git diff --check` : aucune erreur d'espacement.

Les résultats HTTP, références et syntaxe sont enregistrés dans [verification-structure-2026-09-13.json](audits/verification-structure-2026-09-13.json).

## Limites

Cette vérification valide la réorganisation et le démarrage local. Elle ne remplace pas l'audit métier complet, la recette MySQL/MariaDB ou les tests de paiement et d'envoi de messages. Le serveur PHP local ne teste pas les directives Apache : vérifier `.htaccess` sur l'hébergement de préproduction. Une route inconnue renvoie actuellement une erreur JSON du backend ; `404.html` reste une page statique accessible directement.

Le site reste une implémentation partielle : l'intégration frontend/backend et les fonctionnalités réelles de collecte doivent être terminées avant ouverture. Voir [le guide de déploiement](DEPLOIEMENT_INFOMANIAK.md). Aucun fichier n'a été publié chez Infomaniak.
