# Backend Amanah

Fondation PHP 8.3 autonome, sans modification du prototype frontend. Elle suit le plan de refonte : données éditoriales séparées, dons en centimes, checkout hébergé via un adaptateur, webhook signé et idempotent, newsletter double opt-in, contact, rôles d'administration et jobs différés.

## Démarrage local

```powershell
cd backend
Copy-Item .env.example .env
New-Item -ItemType Directory -Force storage | Out-Null
sqlite3 storage/database.sqlite ".read database/schema.sql"
composer dump-autoload
php bin/seed.php
composer serve
```

Pour MySQL/MariaDB, définir `DB_DSN` avec le DSN PDO du serveur et exécuter `database/schema.sql` sur une base neuve avec la version réellement utilisée par l'hébergeur. Le fichier est une migration d'installation initiale : les évolutions doivent être ajoutées dans des migrations versionnées et testées sur le moteur cible.

Le dépôt actuel ne contient pas PHP/Composer dans le PATH ; les commandes ci-dessus sont donc à exécuter sur l'environnement de développement ou Infomaniak.

## Routes backend

- `GET /up` : santé minimale.
- `POST /dons/checkout` : valide et crée/reprend un checkout.
- `GET /don/retour` et `GET /dons/{reference}/statut` : état serveur du don.
- `POST /webhooks/paiement` : webhook signé, dédupliqué et transactionnel.
- `POST /contact` : réception anti-spam.
- `POST /newsletter/inscription`, confirmation et désinscription : double opt-in.
- `GET /internal/jobs/run` : lot de jobs borné, authentifié par en-tête.
- `/admin/*` : accès interne avec rôle ; aucun compte public n'est créé.

Le driver `fake` permet de développer sans compte de paiement. Avant production, il doit être remplacé par un adaptateur Stripe utilisant le SDK officiel, après validation du compte Amanah, des moyens de paiement et des clés test/live.

## Sécurité importante

Les originaux privés, reçus et exports doivent rester hors de `public/`. Les secrets vont dans l'environnement serveur. Le webhook ne s'appuie jamais sur un paramètre `success=true` et aucune donnée de carte n'est stockée.
