# Amanah

Site humanitaire avec pages publiques HTML/CSS/JavaScript et backend PHP. **La seule racine web est `public/`.** Le dépôt entier ne doit jamais être exposé par le serveur HTTP.

## Structure

```text
public/                   Racine web à configurer chez Infomaniak
  *.html                  Pages du site
  index.php               Point d'entrée des routes PHP
  .htaccess               Index, réécriture Apache et protection des fichiers cachés
  assets/css/             Feuilles de style
  assets/js/              JavaScript
  assets/images/          Logo et futures images publiques
backend/                  Code PHP privé, configuration et dépendances
  src/ config/            Application
  database/ tests/ bin/   Schéma initial, tests et commandes
  storage/                Données locales, journaux et fichiers privés ignorés par Git
docs/                     Plan, audit historique et guide de déploiement
archive/prototypes/       Ancien prototype conservé
tools/router.php          Routeur du serveur PHP local
```

## Développement

PHP 8.3 ou supérieur et les extensions nécessaires au backend doivent être installés. Depuis la racine du dépôt :

```powershell
php -S 127.0.0.1:8080 -t public tools/router.php
```

Ouvrir `http://127.0.0.1:8080/`. Les chemins des ressources partent de la racine du site : utiliser le serveur HTTP, plutôt qu'ouvrir les HTML directement depuis le disque. Pour initialiser la base et tester le backend, suivre [backend/README.md](backend/README.md). Une fois Composer disponible, `composer --working-dir=backend serve` lance le même serveur et `composer --working-dir=backend test` exécute les tests.

## État et déploiement

Cette organisation prépare le déploiement mais ne valide pas à elle seule la mise en production fonctionnelle. Les pages de démonstration et le backend doivent encore être raccordés et les fonctionnalités métier validées ; un adaptateur de paiement réel reste nécessaire. Ne pas ouvrir une collecte avec le driver de test.

Voir [le guide Infomaniak](docs/DEPLOIEMENT_INFOMANIAK.md), [la documentation](docs/README.md) et [le compte rendu du rangement](docs/REORGANISATION_WORKSPACE.md). L'audit conservé porte sur une révision antérieure aux dernières corrections : il constitue une référence historique, pas une certification de l'état actuel.
