# Déploiement Infomaniak

## Racine web

Transférer l'application avec cette disposition, en adaptant le chemin parent à l'hébergement :

```text
amanah/
  public/        ← dossier cible du site
  backend/       ← dossier frère, inaccessible par HTTP
```

Configurer le dossier cible du site sur `amanah/public`, et jamais sur `amanah`. Infomaniak documente ce réglage dans son [guide du dossier cible](https://www.infomaniak.com/fr/support/faq/963/modifier-le-dossier-dun-site-web), consulté le 13 septembre 2026. Vérifier également qu'aucun autre site ou alias ne publie le dossier parent.

Transférer les fichiers cachés nécessaires, dont `public/.htaccess`. L'accueil est `index.html` ; les chemins qui ne correspondent pas à des fichiers sont transmis à `index.php`. Les URL des assets supposent que `public/` est la racine du domaine ou sous-domaine, sans préfixe `/amanah/public/`.

## Configuration privée

1. Choisir une version PHP compatible avec la contrainte `>=8.3` du projet et vérifier les extensions utilisées : PDO avec le pilote de la base choisie, mbstring, OpenSSL et sessions. Vérifier PHP web et CLI séparément.
2. Installer les dépendances dans `backend/` avec Composer si nécessaire (`composer install --no-dev --optimize-autoloader`). Si des dépendances sont ajoutées, conserver leur fichier de verrouillage dans Git.
3. Renseigner `backend/.env` ou l'environnement serveur : `APP_ENV=production`, `APP_DEBUG=false`, URL HTTPS réelle, clés aléatoires privées, accès à la base et adresses mail. Ne pas réutiliser les valeurs d'exemple. Le driver de paiement réel n'est pas encore implémenté : les clés seules ne le rendent pas opérationnel.
4. Créer `backend/storage/private` et `backend/storage/logs`. Autoriser l'écriture au compte PHP sur le stockage nécessaire uniquement ; protéger la lecture de `.env`. Ne pas appliquer de droits `777` globaux.
5. Installer le schéma sur une base neuve de préproduction et le valider sur le moteur réellement fourni. Ne pas réimporter aveuglément le schéma initial sur une base existante ; sauvegarder et prévoir des migrations pour les évolutions.
6. Activer le certificat HTTPS et la redirection HTTPS de l'hébergement. Vérifier les tâches différées et leur authentification dans l'environnement cible avant activation.

Ne pas transférer `.git/`, `.claude-flow/`, `.swarm/`, `.codex/`, `.agents/`, les preuves d'audit, les archives, les tests ou `tools/` dans le dossier public. Ne pas écraser les secrets et données persistantes lors d'une mise à jour. Sauvegarder fichiers privés et base avant chaque livraison, conserver la version précédente pour un retour arrière.

## Recette avant ouverture

- Vérifier l'accueil, les 14 pages, les ressources et les routes PHP sous Apache : le serveur de développement ne lit pas `.htaccess`.
- Vérifier que `/.git/config`, `/backend/.env`, `/docs/` et `/archive/` ne servent aucun fichier privé.
- Exécuter les tests PHP, puis une recette avec la base cible, les formulaires raccordés, les envois de messages et les droits d'administration.
- Avant collecte : intégrer et tester le prestataire réel, les webhooks, échecs, reprises, remboursements et éventuelle récurrence ; remplacer les contenus de démonstration et valider les pages publiques.

Le rangement du dépôt n'est pas une validation de ces fonctions. Aucun déploiement, accès au compte Infomaniak ou test de paiement réel n'a été effectué pendant cette réorganisation.
