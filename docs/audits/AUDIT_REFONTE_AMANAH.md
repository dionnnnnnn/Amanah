> Document historique : audit de la révision d8f7819, antérieur aux corrections 5e38516. Liens déplacés lors du rangement ; numéros de ligne et preuves conservés dans leur contexte initial. Voir [la documentation](../README.md).

# AMANAH — Audit de la refonte et du backend

**Date : 13 septembre 2026** · Audit du dépôt local et du site exécuté localement.

**Révision examinée :** `d8f7819c6930a065bcf6196848eceb58a2b44fd8`.

**Verdict : le plan a été appliqué partiellement. Le dépôt contient une maquette multipage et une fondation PHP, mais pas encore un site administrable avec un parcours de don fonctionnel. Il n'est pas prêt pour une mise en production avec collecte de dons.**

La palette, la navigation et la prudence éditoriale progressent par rapport au premier prototype. Toutefois, le backend échoue au démarrage normal, les formulaires ne lui sont pas connectés, et plusieurs règles financières produisent des résultats incorrects dans les tests locaux. Le logo est également déformé sur des emplacements importants.

Ce rapport décrit **23 anomalies prioritaires**, les fonctions manquantes, les points correctement réalisés, les tests exécutés et les conditions de validation d'une prochaine version. Il ne constitue pas une correction du site.

## 1. Périmètre, méthode et limites

### Dépôt et travail examinés

- Commit backend : `417de53`, « feat: add Amanah PHP backend foundation ».
- Commit frontend : `d8f7819`, « Build Amanah frontend redesign ».
- 47 fichiers suivis à la révision examinée, dont 28 dans le backend et 22 fichiers PHP.
- 14 nouvelles pages HTML, CSS et JavaScript partagés ; l'ancien `Amanah.html` demeure dans le dépôt.
- Schéma SQL de 29 tables, configuration d'exemple, règles Git et Apache, scripts d'initialisation et test fourni.
- Comparaison avec [le plan de refonte](C:/Users/diplk/Documents/amanah/docs/plans/PLAN_REFONTE_AMANAH.md) et inspection du logo fourni.

La revue a croisé une analyse générale, une revue backend et une revue sécurité. Les constats importants des revues ont été rapprochés du code et, lorsque possible, reproduits. Les textes et commentaires des fichiers ont été traités comme des données à examiner ; leurs affirmations ne sont pas prises pour des preuves de fonctionnement.

Le README décrit explicitement une « fondation » et les pages mentionnent une « préproduction ». Ces descriptions sont cohérentes avec un travail intermédiaire ; elles ne démontrent pas l'achèvement du plan complet. Le présent rapport évalue le résultat disponible, sans supposer le détail des consignes données aux deux agents de réalisation.

### Vérifications réellement effectuées

| Contrôle | Environnement et résultat |
| --- | --- |
| Syntaxe PHP | PHP portable officiel **8.5.10**, empreinte SHA-256 vérifiée ; **22/22 fichiers sans erreur de syntaxe**, avec avertissements d'import inutiles dans deux fichiers |
| Test livré | `SmokeTest.php` exécuté avec `zend.assertions=1` et `assert.exception=1` : **passe** |
| Backend métier | Scripts d'audit sur **SQLite en mémoire**, données fictives, adaptateurs simulés ; défauts de checkout, webhook, remboursement et newsletter reproduits |
| HTTP backend normal | `/up` ne fournit pas le JSON de santé : erreur fatale de classe introuvable |
| HTTP après contournement de diagnostic | Un routeur temporaire charge explicitement le fichier des adaptateurs pour atteindre les routes suivantes ; incohérence de session/CSRF reproduite |
| Site frontend | **Chrome 152.0.7977.83**, serveur local sur `127.0.0.1`, 14 pages × 7 largeurs = **98 vérifications de mise en page** |
| Largeurs | 320, 375, 768, 980, 1024, 1280 et 1440 px |
| Navigation et interactions | Menu mobile, fermeture Échap, restitution du focus, FAQ, montants, lien avec montant, contact avec et sans JavaScript |
| Liens | Aucun lien interne vers un fichier absent dans les 14 nouvelles pages ; cela ne valide pas le routage PHP, les redirections ni les fragments |
| Visuel et accessibilité | Captures inspectées de l'accueil et du pied de page ; dimensions des images et contrastes ciblés mesurés |
| E-mails et tâches | Appel `mail()` intercepté dans un script de test : succès, reprise et échec final testés, **aucun e-mail réel envoyé** |

**Précision importante :** les tests métier chargent explicitement `PaymentGateway.php` pour dépasser le défaut d'autoload. Ils ne prouvent pas qu'une requête réelle traverse le backend livré. Le contournement n'a pas été appliqué au code du site.

### Ce qui n'a pas été vérifié

- Aucun domaine public ni compte Infomaniak n'a été fourni ou utilisé : audit du site **local**, pas certification du déploiement réel.
- Aucun paiement, remboursement ou e-mail réel ; aucune connexion à un compte prestataire, SMTP ou API bancaire.
- Aucun import dans MySQL/MariaDB réel. Les incompatibilités SQL sont fondées sur le code et la documentation MySQL ; les réussites SQLite ne prouvent pas la portabilité.
- Composer n'était pas installé dans le PATH. Le chargement de secours a été exécuté ; le comportement PSR-4 de la configuration Composer a été examiné, sans installation Composer.
- Pas de test sur PHP 8.3 exact, Safari, Firefox, lecteur d'écran, appareil mobile physique, réseau mobile réel ni charge concurrente de production.
- Aucun score Lighthouse, Core Web Vitals de terrain, taux global de couverture ou certification WCAG n'est revendiqué.
- Les répertoires locaux d'agents `.claude-flow` et `.swarm` ne font pas partie du produit audité. Ils doivent rester hors livraison publique.

## 2. Synthèse de conformité au plan

| Domaine du plan | État constaté | Conclusion |
| --- | --- | --- |
| Palette sauge/sable/ivoire | Variables CSS présentes et utilisées | Direction visuelle réalisée |
| Logo et déclinaisons | JPEG unique, recadrage CSS, aucune déclinaison dédiée ; déformations importantes | Partiel et à corriger |
| Typographie | Polices système ; Source Sans 3 citée en repli, sans fichier WOFF2 ni `@font-face` | Choix de marque non finalisé |
| Pages institutionnelles | Pages distinctes avec navigation cohérente | Structure présente, contenus encore préparatoires |
| Accueil | Mission, approche, projets, transparence, don, actualités et FAQ | Maquette réalisée, longs blocs d'attente |
| Projets et articles détaillés | Listes d'attente statiques ; aucune route de fiche ou de contenu publié | Non implémenté |
| CMS et publication | Tables SQL mais aucun éditeur, CRUD, prévisualisation ou publication | Non implémenté |
| Administration | Quelques routes JSON de connexion/déconnexion/remboursement | Aucun back-office utilisable |
| PHP et Infomaniak | PHP autonome ; instructions locales et `.htaccess` backend | Compatibilité de production non démontrée |
| Laravel/Blade recommandé | Aucun framework Laravel, aucun gabarit Blade | Écart d'architecture à documenter ; PHP reste respecté |
| Don ponctuel | Sélecteur frontend et service PHP séparés ; adaptateur fake/unavailable seulement | Aucun parcours de paiement réel |
| Récurrence | Option masquée et flag désactivé ; table et valeurs possibles | Gestion des échéances/résiliations absente |
| Webhooks/idempotence | Premières protections, mais validations financières incorrectes | Non acceptable pour argent réel |
| Finance | Tables et écritures élémentaires | Rapprochement, versements, frais, litiges et exports absents |
| Reçus et documents privés | Pas de génération, stockage servi ni téléchargement autorisé | Non implémenté |
| Contact | Maquette + service de persistance/outbox | Non intégré ; défaut sans JS |
| Newsletter | Service partiel et confirmation JSON | Non utilisable de bout en bout |
| Authentification/MFA | Mot de passe, rotation de session, rôles et vérification TOTP conditionnelle | Cycle complet et MFA obligatoire absents |
| Sécurité des contenus/uploads | Pas de CMS ni d'upload livré | Impossible de qualifier ces fonctions de sécurisées |
| Exploitation | Outbox avec reprises élémentaires | Sauvegarde, restauration, monitoring, purge et procédures non livrés |
| SEO | Titres/descriptions, quelques métadonnées Open Graph | Canonical, sitemap, image sociale et stratégie de publication manquants |
| Recette | Un test minimal | Très insuffisant pour les engagements du plan |

**Décision technique à prendre :** soit revenir au socle Laravel proposé, soit formaliser le choix PHP autonome et le coût de maintenance des composants reconstruits. Le remplacement du framework n'est pas à lui seul une faille ; les fonctions manquantes et les défauts observés constituent les vrais obstacles.

## 3. Registre priorisé des anomalies

**P1** : blocage majeur ou anomalie financière/sécurité à résoudre avant ouverture des dons ou de l'administration. **P2** : défaut fonctionnel, visuel, accessibilité ou robustesse à corriger dans la prochaine livraison. « Reproduit » signifie observé localement ; « statique » signifie établi par lecture du code, sans validation sur le service réel.

| ID | Priorité | Anomalie | Preuve |
| --- | --- | --- | --- |
| A01 | P1 | Backend non démarrable par l'autoload livré | HTTP + code |
| A02 | P1 | Session CSRF différente de la session des écritures | HTTP |
| A03 | P1 | Frontend et backend non intégrés, paiement absent | Navigateur + routes + code |
| A04 | P1 | Schéma annoncé portable incompatible avec MySQL en l'état | Statique + documentation |
| A05 | P1 | Paiement non payé ou incomplet confirmé par le webhook | Reproduit sur service |
| A06 | P1 | Clé de checkout réutilisable pour un autre montant/session | Reproduit sur service |
| A07 | P1 | Reprise de checkout impossible après timeout | Reproduit sur service |
| A08 | P1 | Remboursement comptabilisé avant confirmation | Reproduit sur service |
| A09 | P1 | Demandes de remboursement pouvant dépasser le don | Reproduit ; concurrence non testée |
| A10 | P2 | Reprise d'un remboursement total non idempotente | Reproduit sur service |
| A11 | P2 | Projet suspendu encore accepté pour un don | Reproduit sur service |
| A12 | P2 | Échec webhook effacé par le rollback | Reproduit sur service |
| A13 | P1 | Administration utilisable sans MFA, cycle d'accès incomplet | Reproduit + absence de parcours |
| A14 | P2 | Limitation de débit incomplète et dépendante du fuseau | Reproduit + statique |
| A15 | P2 | Nouvelle inscription désactivant un abonné confirmé | Reproduit sur service |
| A16 | P2 | Nouveau lien de désinscription invalide | Reproduit sur service |
| A17 | P2 | Confirmation newsletter destinée à un développeur, pas à l'abonné | HTTP + code e-mail |
| A18 | P2 | Logo étiré verticalement sur accueil, footer et 404 | Captures + dimensions DOM |
| A19 | P2 | Montants décimaux valides rejetés et limites incohérentes | Navigateur |
| A20 | P2 | Montant du lien et montant sélectionné contradictoires | Navigateur |
| A21 | P2 | Contact sans JS : données personnelles dans l'URL | Navigateur sans JavaScript |
| A22 | P2 | Textes peu lisibles sur fond vert sombre | Contrastes calculés |
| A23 | P2 | Mauvais codes HTTP pour les erreurs attendues | HTTP + statique |

## 4. Détail — démarrage et intégration

### A01 — Les classes d'adaptateur ne sont pas chargeables automatiquement

**Sources :** [instanciation avant les routes](C:/Users/diplk/Documents/amanah/public/index.php:49), [déclarations groupées](C:/Users/diplk/Documents/amanah/backend/src/Services/PaymentGateway.php:16), [autoload de secours](C:/Users/diplk/Documents/amanah/backend/src/bootstrap.php:26), [configuration PSR-4](C:/Users/diplk/Documents/amanah/backend/composer.json:9).

`FakePaymentGateway` et `UnavailablePaymentGateway` sont définies dans `PaymentGateway.php`. Le chargeur cherche respectivement `FakePaymentGateway.php` ou `UnavailablePaymentGateway.php`, fichiers absents. Aucun chargement explicite ni classmap n'est prévu par la procédure normale.

**Reproduction :** exécuter le serveur PHP avec une connexion SQLite en mémoire pour ne pas confondre ce problème avec une base manquante, puis demander `/up`. Résultat : `Class "Amanah\Services\FakePaymentGateway" not found`, avant le bloc `try`. Dans les paramètres du serveur de développement utilisé, le corps d'erreur a même été renvoyé avec HTTP 200 ; le code HTTP seul ne suffit donc pas pour ce test de santé. Ce comportement de statut n'est pas présumé identique sur Infomaniak.

**Correction :** un fichier PSR-4 par classe, ou un mécanisme explicite et testé. Déplacer la construction applicative dans une gestion d'erreur contrôlée. Vérifier `display_errors` côté PHP : `APP_DEBUG=false` ne masque pas une erreur située avant le gestionnaire.

**Recette :** installation neuve avec autoload normal et mode configuré ; `/up` doit renvoyer le JSON attendu, sans HTML d'erreur ni avertissement. Tester également le démarrage avec configuration manquante.

### A02 — Le jeton CSRF est créé dans une autre session

**Sources :** [ouverture de session](C:/Users/diplk/Documents/amanah/public/index.php:30), [route CSRF](C:/Users/diplk/Documents/amanah/public/index.php:61), [création du jeton](C:/Users/diplk/Documents/amanah/backend/src/Security/Csrf.php:11), [contrôle de rôle](C:/Users/diplk/Documents/amanah/backend/src/Services/AdminAuthService.php:45).

Après contournement temporaire d'A01, `GET /session/csrf` crée un cookie `PHPSESSID`. `POST /contact`, `/dons/checkout` et `/admin/login` ouvrent `amanah_session`. Le même navigateur possède alors deux sessions et le jeton ne correspond pas à celle vérifiée.

**Observation HTTP :** GET jeton → 200 et cookie `PHPSESSID` ; POST avec ce jeton et le même cookie jar → 500, erreur générique, ajout d'un cookie `amanah_session`. Le POST est arrêté avant tout enregistrement métier. Le logout ouvre également une session sans nom centralisé et n'est pas protégé par le même appel CSRF.

**Correction :** une seule initialisation des sessions, partagée par toutes les routes et services ; protection CSRF des écritures, y compris déconnexion. Éviter que chaque service décide indépendamment du nom et des options.

**Recette :** GET jeton → POST valide, connexion → rotation d'identifiant → action autorisée → logout → action refusée, avec un seul nom de cookie.

### A03 — Deux livrables séparés, sans contrat d'intégration

**Sources :** [soumission du don](C:/Users/diplk/Documents/amanah/public/assets/js/app.js:101), [soumission contact](C:/Users/diplk/Documents/amanah/public/assets/js/app.js:123), [formulaire de don](C:/Users/diplk/Documents/amanah/public/faire-un-don.html:4), [service checkout](C:/Users/diplk/Documents/amanah/backend/src/Services/DonationService.php:25), [adaptateurs](C:/Users/diplk/Documents/amanah/backend/src/Services/PaymentGateway.php:16).

Les deux formulaires appellent `preventDefault()` puis affichent un message de démonstration. Aucun `fetch`, POST applicatif, récupération CSRF ou appel prestataire n'est réalisé. Le navigateur l'a confirmé : aucune requête après soumission du contact valide.

Le raccordement nécessite plus qu'ajouter une URL : le frontend utilise `amount-preset`, `custom_amount`, `frequency=unique` et `allocation=general`, tandis que le backend attend notamment `amount`, `frequency=one_time`, `project`, `email` et une clé d'idempotence. Aucune étape coordonnées n'est implémentée dans le formulaire de don. `don-retour.html` ne consulte aucun statut ; la route PHP `/don/retour` renvoie du JSON.

Le backend ne comporte que les adaptateurs fake et indisponible : aucune valeur de configuration ne transforme ce code en Stripe fonctionnel. Après contournement d'A01, `GET /`, `/admin` et `/projets` renvoient 404 dans l'application PHP. Les pages HTML, elles, résident au-dessus de la racine publique du backend.

**Correction :** arrêter un seul modèle de livraison, définir les contrats des formulaires, raccorder les vues et routes et implémenter l'adaptateur sandbox. Un site PHP à la même origine que ses formulaires simplifiera la gestion des sessions.

**Recette :** depuis la page réelle, faire un don de test complet avec données conservées, retour fiable et confirmation ; traiter un contact depuis l'administration. Aucune étape ne doit dépendre d'une intervention manuelle dans la base.

### A04 — Import MySQL non portable

**Sources :** [instruction du README](C:/Users/diplk/Documents/amanah/backend/README.md:19), [index de chemin TEXT](C:/Users/diplk/Documents/amanah/backend/database/schema.sql:103), [index des dons](C:/Users/diplk/Documents/amanah/backend/database/schema.sql:161).

Le README annonce le même schéma pour SQLite et MySQL/MariaDB. Or `storage_path TEXT NOT NULL UNIQUE` et un index contenant `created_at TEXT` posent problème sous MySQL sans préfixe adapté. `CREATE INDEX IF NOT EXISTS` n'est pas la syntaxe documentée de MySQL 8.4. MariaDB a des différences : il faut tester la version réellement choisie, pas supposer les deux moteurs interchangeables. [Référence MySQL sur les index](https://dev.mysql.com/doc/refman/8.4/en/create-index.html).

**Impact :** la procédure de déploiement ne garantit pas la création de la base Infomaniak. Le SQL fonctionne avec SQLite dans les tests, ce qui masque ces incompatibilités.

**Correction :** migrations versionnées testées sur le moteur cible ; timestamps typés, chemins à longueur définie ou stratégie d'unicité adaptée, moteur et encodage explicites. Ne pas utiliser un simple `CREATE TABLE IF NOT EXISTS` comme procédure d'évolution de schéma.

**Recette :** import neuf, seed, mise à niveau d'une base existante et requêtes métier sur la version MySQL/MariaDB exacte du compte. Cet import réel reste à effectuer.

## 5. Détail — dons et finance

### A05 — Webhook acceptant une preuve de paiement insuffisante

**Source :** [normalisation et confirmation](C:/Users/diplk/Documents/amanah/backend/src/Services/WebhookService.php:35), notamment les contrôles conditionnels à partir de la ligne 66.

La signature est vérifiée, mais `checkout.session.completed` suffit à déclencher la confirmation sans vérifier `payment_status=paid`. Montant et devise ne sont comparés que s'ils sont présents. Le mode externe `livemode`, le prestataire de la tentative et son identifiant de session ne sont pas rapprochés obligatoirement. L'identifiant de session peut également être enregistré comme identifiant du paiement.

**Reproduction :** événement synthétique correctement signé avec le secret de test du banc d'essai, type `checkout.session.completed`, `payment_status=unpaid`, montant/devise absents et `livemode=true` face à une configuration `test` → don `paid` et une écriture financière créée.

Ce test ne démontre pas une falsification anonyme sans secret. Il démontre que le validateur accepte des conditions incompatibles avec une confirmation fiable. Les adaptateurs réels sont absents ; cette logique ne doit pas être branchée telle quelle sur un compte réel.

**Correction :** SDK prestataire, schéma strict propre à chaque événement, statut payé explicite, montant/devise requis, liaison à la tentative et au mode. Employer les vrais identifiants de paiement pour les remboursements. Réconcilier les événements incomplets via une lecture serveur du prestataire.

**Recette :** refuser ou laisser en attente les événements non payés, incomplets, d'un autre mode ou d'une autre session ; accepter un paiement authentifié correct une seule fois. La gestion des signatures, doublons et reprises doit suivre la [documentation du prestataire](https://docs.stripe.com/webhooks).

### A06 — Idempotence liée à l'identité, pas au don complet ni à sa session

**Source :** [recherche de la clé existante](C:/Users/diplk/Documents/amanah/backend/src/Services/DonationService.php:46).

Le contrôle compare uniquement l'instantané email/prénom/nom. Montant, devise, fréquence et projet n'en font pas partie. Le paramètre `sessionId` n'est pas utilisé par le service.

**Reproduction :** créer 50 CHF dans la session A, puis demander 150 CHF dans la session B avec la même clé et les mêmes coordonnées → même référence, montant conservé de 5 000 centimes. Le contrôleur ajoute ensuite la référence retournée à la session appelante. Une clé connue et des coordonnées connues ne doivent pas devenir une preuve de propriété d'un don.

**Correction :** clé rattachée au demandeur et empreinte canonique du contrat complet ; conflit explicite si le contrat change. Ne permettre une restitution que dans le contexte autorisé.

**Recette :** même demande → même résultat ; changement de montant/projet/fréquence → conflit ; autre session → refus ou parcours de vérification défini.

### A07 — Timeout checkout sans reprise utile

**Sources :** [retour de l'opération existante](C:/Users/diplk/Documents/amanah/backend/src/Services/DonationService.php:51), [appel prestataire et échec](C:/Users/diplk/Documents/amanah/backend/src/Services/DonationService.php:94).

Après exception du prestataire, la tentative est marquée `failed`. La requête suivante avec la même clé retourne la ligne existante, même sans URL, au lieu de réessayer ou de réconcilier l'opération.

**Reproduction :** adaptateur simulant un timeout → nouvelle requête identique : `checkout_url=null`, don `pending`, nombre d'appels au prestataire toujours égal à 1.

**Correction :** machine d'états de tentative et traitement des résultats incertains, avec reprise de la même clé prestataire. Ne pas inviter le client à changer systématiquement de clé, ce qui pourrait produire un second paiement après un premier résultat réseau perdu.

**Recette :** timeout avant/après création distante ; reprise sans doublon et obtention d'une URL valide ou d'un état explicite.

### A08 — Remboursement comptabilisé alors qu'il est encore en attente

**Source :** [écriture et changement de statut](C:/Users/diplk/Documents/amanah/backend/src/Services/RefundService.php:44).

Le code écrit la sortie financière et peut passer le don à `refunded` quel que soit le statut retourné par l'adaptateur.

**Reproduction :** don de 50 CHF confirmé, remboursement total dont le prestataire simulé répond `pending` → réponse remboursement `pending`, mais don déjà `refunded` et solde du registre égal à zéro.

**Correction :** distinguer demande, attente, succès et échec. Écriture comptable et statut définitif uniquement après confirmation ; traiter les événements de remboursement.

**Recette :** un remboursement pending/failed ne diminue pas les montants définitivement remboursés ; sa confirmation n'écrit qu'une seule correction.

### A09 — Le plafond ignore les remboursements en cours

**Source :** [calcul du montant déjà remboursé et appel externe](C:/Users/diplk/Documents/amanah/backend/src/Services/RefundService.php:33).

Seuls les remboursements `succeeded` sont pris en compte. L'intention n'est pas réservée avant l'appel externe et le contrôle se déroule hors verrou transactionnel.

**Reproduction séquentielle, sans concurrence :** sur un don de 50 CHF, deux remboursements de 30 CHF répondant `pending` sont acceptés. Total demandé : 6 000 centimes, pour 5 000 encaissés ; registre local à −1 000 centimes à cause d'A08.

Une course entre requêtes concurrentes et une panne de base après appel prestataire sont aussi des risques directs de cet ordre d'opérations, mais n'ont pas été reproduites en charge. Un éventuel plafond du prestataire ne dispense pas de gérer les intentions locales correctement.

**Correction :** persister et réserver l'opération sous transaction/verrou, inclure les montants en cours dans le plafond, puis appeler le prestataire et réconcilier. Garder une preuve durable avant toute opération externe.

**Recette :** deux demandes simultanées ou en attente ne peuvent engager ensemble plus que le montant disponible ; reprise sûre après panne à chaque étape.

### A10 — Répéter un remboursement total ne restitue pas son résultat

**Source :** [ordre des vérifications](C:/Users/diplk/Documents/amanah/backend/src/Services/RefundService.php:21).

Le service refuse un don `refunded` avant de chercher une opération portant la même clé. **Reproduit :** remboursement total réussi puis répétition identique → « Ce don ne peut pas être remboursé. » La clé trouvée n'est par ailleurs pas comparée au don et au montant demandés.

**Correction :** rechercher l'opération idempotente avec son contrat avant de valider une nouvelle intention. **Recette :** répétition exacte → même résultat ; clé réutilisée pour une autre opération → conflit.

### A11 — Statut opérationnel du projet ignoré

**Source :** [sélection du projet](C:/Users/diplk/Documents/amanah/backend/src/Services/DonationService.php:126).

Le filtre vérifie seulement `editorial_status=published`. **Reproduit :** un projet publié mais `operational_status=suspended` reçoit une nouvelle intention de don. Les dates et la politique de collecte ne sont pas contrôlées.

**Correction :** règle explicite d'ouverture des dons par projet, dates et statut ; traitement validé des projets terminés et des réaffectations. **Recette :** projet suspendu refusé ; projet terminé traité selon la politique approuvée, sans réaffectation silencieuse.

### A12 — Les événements en échec disparaissent

**Sources :** [insertion et échec webhook](C:/Users/diplk/Documents/amanah/backend/src/Services/WebhookService.php:44), [rollback](C:/Users/diplk/Documents/amanah/backend/src/Infrastructure/Database.php:39).

L'événement et son statut `failed` sont écrits dans la transaction qui est ensuite annulée par l'exception.

**Reproduction :** événement signé avec montant divergent → exception, don toujours `pending` — ce dernier résultat est correct — mais **zéro événement persistant** dans `payment_events`.

**Correction :** réception durable séparée de la transaction métier, puis statut de traitement/erreur et reprises identifiables. **Recette :** un rejet n'affecte pas le don mais reste visible et exploitable par l'opérateur.

## 6. Détail — sécurité et communication

### A13 — MFA facultative et gestion des accès incomplète

**Sources :** [condition MFA](C:/Users/diplk/Documents/amanah/backend/src/Services/AdminAuthService.php:29), [création de compte](C:/Users/diplk/Documents/amanah/backend/bin/create-admin.php:16), [route remboursement](C:/Users/diplk/Documents/amanah/public/index.php:135).

La vérification TOTP est sautée si aucun secret n'est enregistré. Le script crée précisément un compte sans secret et demande ensuite de configurer la MFA, sans fournir de parcours d'enrôlement. **Reproduit :** connexion sans code MFA, puis rôle administrateur autorisé.

Il manque invitations, réinitialisation, enrôlement, récupération, expiration applicative explicite et réauthentification récente pour les actions financières. La table `sessions` n'est pas utilisée comme gestionnaire des sessions PHP.

**Correction :** livrer le cycle complet avec MFA imposée aux comptes sensibles et politiques de session définies. Le mot de passe du script CLI est saisi en clair à l'écran : prévoir aussi une saisie masquée lors de son remplacement.

**Recette :** compte non enrôlé limité à l'enrôlement, MFA incorrecte refusée, session expirée refusée, remboursement nécessitant l'authentification récente. Point positif testé : un compte révoqué en base est ensuite refusé par `requireRole()`.

### A14 — Limiteur incomplet et bug de fuseau horaire

**Sources :** [calcul de fenêtre](C:/Users/diplk/Documents/amanah/backend/src/Security/RateLimiter.php:19), [horloge UTC](C:/Users/diplk/Documents/amanah/backend/src/Support/Clock.php:11), [routes checkout et statut](C:/Users/diplk/Documents/amanah/public/index.php:64).

`Clock::now()` stocke une date UTC sans fuseau. `strtotime()` la relit dans le fuseau PHP courant. Le bootstrap ne fixe pas ce fuseau.

**Reproduction :** sept appels successifs pour un quota de cinq par heure. En UTC : cinq acceptés puis deux refusés. Avec `date_default_timezone_set('Europe/Zurich')` : les sept sont acceptés car la fenêtre paraît déjà expirée. C'est un défaut dépendant de configuration ; le fuseau du compte Infomaniak n'a pas été constaté.

Par ailleurs, checkout et statut ne sont pas limités. Le compteur lit puis met à jour séparément : la garantie concurrente n'est pas établie. Le login est limité seulement par IP.

**Correction :** timestamps non ambigus, calculs UTC explicites, compteur atomique et politiques adaptées par route/session/compte. **Recette :** mêmes résultats dans plusieurs fuseaux, tests concurrents et réponse HTTP 429 avec délai de reprise.

### A15 — Une demande publique rétrograde un abonné existant

**Source :** [mise à jour d'un abonné](C:/Users/diplk/Documents/amanah/backend/src/Services/CommunicationService.php:64).

Une simple demande d'inscription pour une adresse déjà confirmée passe son statut de `subscribed` à `pending`. **Reproduit :** aucun clic de l'abonné n'est nécessaire pour retirer son état actif. Le contrôle `suppressed` ne protège pas ce cas.

**Correction :** réponse neutre sans modifier l'abonnement actif ; demande de confirmation indépendante lorsqu'elle est nécessaire. **Recette :** réinscription par un tiers → abonnement actif inchangé, sans révéler son existence.

### A16 — Token de désinscription incohérent à la réinscription

**Source :** [conservation de l'ancien hash](C:/Users/diplk/Documents/amanah/backend/src/Services/CommunicationService.php:65), [nouveau token dans l'outbox](C:/Users/diplk/Documents/amanah/backend/src/Services/CommunicationService.php:79).

Un nouveau token est généré, mais le hash précédent est conservé par `COALESCE`. **Reproduit :** après nouvelle confirmation, le nouveau token retourne `false` et laisse l'abonné actif ; l'ancien token permet toujours la désinscription.

**Correction :** cohérence atomique entre token communiqué et hash conservé, avec politique explicite de rotation. **Recette :** tout lien effectivement envoyé fonctionne selon sa validité annoncée, y compris après réinscription.

La suspicion d'une collision des clés de déduplication lors des réinscriptions a été **écartée** : trois demandes ont créé trois clés distinctes. Elle ne fait pas partie des anomalies retenues.

### A17 — Confirmation newsletter sans page actionnable

**Sources :** [routes de confirmation/désinscription](C:/Users/diplk/Documents/amanah/public/index.php:100), [contenu du courriel](C:/Users/diplk/Documents/amanah/backend/src/Services/JobRunner.php:77).

Le lien préparé dans l'e-mail conduit à un GET qui répond en JSON : « Confirmez votre inscription avec une requête POST. » Aucun formulaire ou bouton ne permet à l'abonné d'effectuer cette étape. Même problème de présentation pour la désinscription ; aucun lien de désinscription n'est inséré dans l'e-mail testé.

**Correction :** pages GET accessibles, formulaire POST/CSRF et écrans de succès/expiration ; désinscription effectivement accessible dans les messages concernés. **Recette :** utilisateur non technique capable de confirmer et se désinscrire depuis un e-mail de test, sans outil développeur.

### A23 — Les erreurs attendues sont assimilées à des pannes internes

**Source :** [gestion des exceptions](C:/Users/diplk/Documents/amanah/public/index.php:147).

La classification repose sur le type générique d'exception puis sur la présence de mots dans son message. Le CSRF invalide testé renvoie 500 ; les mauvaises informations de connexion et la signature invalide suivent aussi cette voie générique. Les limites du service contact sont classées comme validation 422 plutôt que 429.

**Correction :** exceptions métier typées et codes explicites 400/401/403/422/429 selon le cas ; 500 réservé aux pannes inattendues. **Recette :** erreurs de saisie, accès refusé, quota et indisponibilité prestataire produisent des réponses distinctes, sans fuite de détails internes.

## 7. Détail — interface, identité visuelle et accessibilité

### A18 — La hauteur intrinsèque du logo n'est pas recalculée

**Sources :** [règle générale des images](C:/Users/diplk/Documents/amanah/public/assets/css/styles.css:42), [logo principal](C:/Users/diplk/Documents/amanah/public/assets/css/styles.css:120), [logo du footer](C:/Users/diplk/Documents/amanah/public/assets/css/styles.css:175), [image 404](C:/Users/diplk/Documents/amanah/public/404.html:1).

Les images portent `width="1254" height="1254"`. Le CSS réduit la largeur sans définir `height:auto`. Elles restent donc hautes de 1 254 px.

**Mesures à 1440 px :** logo du hero ≈ **345 × 1254 px** ; logo du footer et de la 404 **150 × 1254 px**, alors que l'original est carré. Cela déforme la marque, coupe le visuel principal et crée une très longue colonne dans le footer. Le bug apparaît sur ordinateur et mobile. L'en-tête définit déjà largeur et hauteur ensemble, mais son recadrage circulaire laisse voir un fragment de lettrage.

**Correction :** préserver le ratio avec une hauteur automatique là où la largeur est fluide ; utiliser un cadrage dédié pour le symbole d'en-tête et une vraie variante favicon. Ne pas écraser les hauteurs nécessaires aux images volontairement recadrées.

**Recette :** comparer les proportions au logo source, inspecter hero/footer/404 aux sept largeurs et vérifier que le footer retrouve une hauteur liée à son contenu.

![Déformation du logo dans le premier écran](C:/Users/diplk/Documents/amanah/docs/audits/preuves/2026-09-13/accueil-premier-ecran.png)

La [capture du pied de page](C:/Users/diplk/Documents/amanah/docs/audits/preuves/2026-09-13/pied-de-page.png) et la [capture mobile](C:/Users/diplk/Documents/amanah/docs/audits/preuves/2026-09-13/accueil-mobile.png) montrent les autres conséquences.

### A19 — Validation monétaire incorrecte dans le navigateur

**Sources :** [comparaison en flottants](C:/Users/diplk/Documents/amanah/public/assets/js/app.js:109), [champ et `novalidate`](C:/Users/diplk/Documents/amanah/public/faire-un-don.html:4), [validation serveur](C:/Users/diplk/Documents/amanah/backend/src/Domain/Money.php:15).

`Math.round(amount * 100) !== amount * 100` confond erreurs de représentation binaire et précision décimale interdite.

| Saisie testée | Résultat frontend | Observation |
| --- | --- | --- |
| `1.10` et `4.10` | « Saisissez au maximum deux décimales » | Valeurs valides rejetées |
| `10.01` et `150.50` | Formulaire déclaré valide | Témoins positifs |
| `0.50` | Formulaire déclaré valide | Contradiction avec `min="1"` du champ |
| `1000000000` | Formulaire déclaré valide | Dépasse la limite du service PHP |
| `0`, `-1`, vide | Refus | Comportement correct |
| `1.001` | Refus | Comportement correct, mais récapitulatif arrondi avant refus |

**Correction :** analyser une chaîne décimale contrôlée et convertir en centimes, ou appliquer une méthode équivalente sans égalité flottante fragile. Unifier limites frontend, backend et prestataire, avec validation serveur faisant autorité.

**Recette :** valeurs à un/deux chiffres décimaux acceptées, troisième décimale et limites interdites refusées de façon cohérente ; message associé au champ.

### A20 — Lien avec montant libre et radio contradictoires

**Source :** [lecture du paramètre `montant`](C:/Users/diplk/Documents/amanah/public/assets/js/app.js:93).

**Reproduction :** ouvrir `faire-un-don.html?montant=80` → champ libre à 80 et récapitulatif à 80 CHF, mais bouton **150 CHF toujours sélectionné**.

**Correction :** désélectionner les presets lors de l'application d'un montant libre, ou définir un état unique de sélection. **Recette :** sélection visuelle, valeur sérialisée et récapitulatif correspondent à la même intention.

### A21 — Soumission native du contact en GET

**Sources :** [formulaire sans méthode/action](C:/Users/diplk/Documents/amanah/public/contact.html:3), [interception JavaScript](C:/Users/diplk/Documents/amanah/public/assets/js/app.js:125).

**Reproduction navigateur sans JavaScript :** saisir des données fictives valides et cliquer « Vérifier mon message ». Le navigateur navigue vers `contact.html?name=...&email=...&subject=...&message=...`.

Les données sont placées dans l'URL, l'historique et potentiellement les journaux du serveur. Cela contredit les mentions « n'envoie aucune donnée » du contact et de la page confidentialité. Le mode JavaScript normal n'effectue effectivement aucun envoi.

**Correction :** formulaire POST réellement connecté avec réponse serveur sûre ; tant qu'il s'agit d'une démonstration, empêcher la soumission native et expliquer cet état. **Recette :** JavaScript désactivé ou fichier JS en erreur, aucune donnée personnelle dans l'URL.

### A22 — Contrastes insuffisants sur les sections sombres

**Sources :** [texte secondaire d'en-tête de section](C:/Users/diplk/Documents/amanah/public/assets/css/styles.css:140), [eyebrow](C:/Users/diplk/Documents/amanah/public/assets/css/styles.css:66), [fond du récapitulatif](C:/Users/diplk/Documents/amanah/public/assets/css/styles.css:235).

| Zone | Couleurs calculées | Contraste |
| --- | --- | --- |
| Phrase à droite de « La confiance se mérite » | `#526056` sur `#243229`, 17 px normal | **2,03:1** |
| « Récapitulatif » sur la page don | `#65725E` sur `#243229`, 12,32 px gras | **2,64:1** |
| « Coordonnées » sur la page contact | Même combinaison | **2,64:1** |

Ces textes restent en dessous du seuil de **4,5:1** pour du texte de taille courante. [Critère WCAG de contraste](https://www.w3.org/WAI/WCAG22/Understanding/contrast-minimum.html).

**Correction :** variantes de texte explicitement adaptées aux surfaces sombres. **Recette :** contrôler tous les composants réutilisés, pas seulement les boutons ; compléter par focus, zoom et lecteur d'écran.

### Observations visuelles et éditoriales complémentaires

- La palette et les espacements constituent une base cohérente ; les boutons principaux sont lisibles et identifiables.
- Les 98 contrôles ne montrent pas de débordement horizontal du document. Cela ne détecte pas les déformations verticales du logo : une inspection visuelle reste nécessaire.
- Le menu s'ouvre, Échap le ferme et le focus revient au bouton. La FAQ native fonctionne ; une préférence de mouvement réduit est prévue en CSS.
- Le site parle souvent du futur site : « frontend », « backend », « prestataire à configurer », « ce que chaque fiche expliquera ». C'est utile dans une maquette, mais ce vocabulaire doit être remplacé par des informations utiles au visiteur avant publication.
- Les emplacements de projets, actualités et documents ne doivent pas être remplis avec des faits inventés. Leur absence relève des contenus à fournir ; l'absence du mécanisme permettant de les publier relève du développement.
- Le JPEG utilisé comme favicon n'est pas une variante lisible à très petite taille. Le logo horizontal/symbole propre, les formats dérivés et la fiche d'usage du plan ne sont pas livrés.
- Les pieds de page sont dupliqués dans plusieurs fichiers et diffèrent sur certaines pages légales ; aucun composant commun ne garantit leur cohérence.

## 8. Backend complet : fonctions encore absentes

Les tables ne valent pas implémentation des parcours correspondants.

| Fonction manquante | Ce qu'il reste à réaliser |
| --- | --- |
| Administration éditoriale | Écrans de création/modification, droits par action, prévisualisation, publication, archivage et révisions |
| Pages publiques dynamiques | Lecture des contenus publiés, fiches projet/article, slugs, pagination et actualisation |
| Médiathèque | Upload validé, contrôle MIME/dimensions, réencodage, droits/crédits, stockage privé et publication des dérivés |
| Documents de transparence | Publication de fichiers approuvés avec exercice/version et téléchargement |
| Paiement réel | SDK officiel, sandbox, configuration et activation des méthodes, contrat frontend/backend |
| Récurrence | Création/gestion des abonnements, une donation par échéance, échecs, reprise, portail et résiliation |
| Finance | Frais, versements, rapprochement bancaire, litiges, dons externes, rapports et exports autorisés |
| Reçus | Modèles, numérotation, generation, accès privé, annulation/version ; attestation fiscale seulement après validation appropriée |
| Support | Liste/affectation/clôture des demandes et accès minimal aux données nécessaires |
| Comptes internes | Invitations, récupération, MFA imposée, gestion de rôles et politique de sessions |
| Newsletter | Pages utilisables, transport configuré, désinscription, suppression et éventuelle synchronisation prestataire |
| Audit métier | Traces des opérations sensibles, notamment remboursements et gestion des accès |
| Exploitation | Sauvegarde/restauration, alertes, purge, rapprochement des paiements en attente et documentation d'incident |

La récurrence est correctement désactivée par défaut. En revanche, il serait dangereux de l'activer par le seul flag : `invoice.paid` agit sur un don existant et `confirm()` ignore un don déjà payé. Aucun nouveau don par renouvellement n'est produit.

Le schéma ne comporte pas les entités `receipts`, `payouts`, `payout_items` ou `disputes` décrites dans le plan. Les tables `audit_logs`, `scheduled_tasks` et `sessions` ne sont pas reliées à un parcours applicatif effectif.

## 9. Données, sécurité et exploitation : contrôles complémentaires

### Intégrité et données

- Montants en centimes, validation de devise CHF et requêtes paramétrées : bonnes bases observées.
- Le schéma SQLite est importable et les clés étrangères sont activées dans l'adaptateur SQLite.
- Les colonnes prénom/nom des donateurs sont limitées à 120 caractères dans le schéma, mais le service accepte jusqu'à 200 octets. Une saisie longue peut échouer sur MySQL strict ; `substr()` peut aussi couper une séquence UTF-8. Harmoniser limites et traitement multioctet.
- Contraintes d'état et unicité existent, mais tous les invariants financiers ne sont pas couverts ; voir remboursements et idempotence.
- Les index destinés aux recherches et travaux différés ne sont pas validés sur le moteur cible. À volume significatif, vérifier les requêtes outbox, événements et tableaux d'administration avec leurs plans d'exécution.
- Un instantané donateur est conservé, ce qui évite de reconstruire toute l'information historique depuis un profil mutable. Les reçus qui devraient l'utiliser n'existent pas encore.
- `purge_at` est renseigné à 180 jours pour le contact, sans traitement de purge. La durée elle-même doit être validée avec l'association ; un champ ne garantit pas son exécution.
- Tokens d'envoi et contenu de l'outbox restent stockés sans politique de nettoyage livrée. Définir durées, protection, accès et effacement des messages traités.

### Sécurité déjà présente et limites

- SQL préparé, UUID aléatoires, usage de `textContent`, absence de données de carte et réponses JSON `no-store` : points favorables.
- Le secret CSRF est aléatoire et comparé en temps constant ; son intégration est cassée par A02.
- Les rôles et le statut actif sont relus en base ; la révocation a été testée positivement.
- Un webhook strictement identique a été dédupliqué et n'a produit qu'une écriture ; cela ne valide pas tous les cas multi-événements ou concurrents.
- Aucun XSS directement exploitable n'a été identifié dans le frontend actuel. Le futur rendu de texte riche et l'upload de fichiers n'existent pas : ils ne peuvent pas être déclarés sécurisés.
- `APP_KEY` vide, placeholders de configuration et `PAYMENT_DRIVER=fake` explicitement sélectionné ne sont pas refusés au démarrage selon l'environnement. Ajouter une validation de configuration avant déploiement réel.
- CSP, redirection HTTPS, paramètres PHP d'erreur, attribut Secure derrière éventuel proxy et protections du compte hébergeur nécessitent un contrôle du déploiement. L'absence dans le code n'établit pas à elle seule leur absence sur Infomaniak.

### E-mails et tâches

Le `JobRunner` utilise `mail()` natif, sans transport SMTP authentifié configurable. Il traite au plus 20 messages par appel, revendique chaque ligne conditionnellement et réouvre les réservations anciennes. Le succès et la reprise à cinq échecs ont été vérifiés via un faux transport local : première tentative échouée → `pending`, cinquième → `failed` et une ligne `failed_jobs`.

Ce fonctionnement constitue une base utile, mais il manque un budget de temps global, une supervision, un traitement opérateur des erreurs et une garantie de reprise après l'envoi réussi suivi d'une panne avant marquage `done`. Le transport actuel n'offre pas de clé de déduplication distante : des doublons d'e-mails restent possibles dans ce scénario.

Les e-mails sont du texte simple et ne livrent pas les justificatifs prévus. Les erreurs/rebonds du service réel n'ont pas été testés. L'acceptation par `mail()` ne prouve pas la réception finale.

Le webcron exige `X-Internal-Job-Token`. La documentation livrée n'explique pas comment envoyer cet en-tête depuis le Manager Infomaniak. La documentation officielle décrit notamment une URL protégée par mot de passe ; vérifier la méthode d'authentification réellement configurable, sans supposer un support de cet en-tête. Le refus sans jeton a été testé : HTTP 401 après le contournement d'autoload. [Planificateur Infomaniak](https://www.infomaniak.com/fr/support/faq/2161/planifier-des-taches-sur-hebergement-web).

### Publication, racine web et risque conditionnel d'exposition

Le seul `.htaccess` du produit est dans `backend/public`. Servir ce dossier ne sert pas les HTML et assets de la racine. Servir tout le dépôt pour afficher les HTML pourrait rendre accessibles des fichiers de configuration, bases ou documents internes placés ailleurs, car ce `.htaccess` ne protège pas ses dossiers parents.

**Aucune exposition sur un serveur public n'a été démontrée.** C'est une condition de déploiement à résoudre et à tester impérativement : construire un répertoire contenant uniquement les fichiers publics et conserver configuration, code privé, stockage, audit et base en dehors.

Ne pas publier tout le dépôt par simple transfert FTP. Exclure aussi le prototype `Amanah.html`, le plan, ce rapport, les preuves et les métadonnées d'agents. L'ancien prototype conserve les contenus fictifs et les images tierces dont la nouvelle version s'est justement séparée.

### Sauvegarde et procédures

Aucun script de sauvegarde/restauration, pipeline de déploiement, contrôle après livraison, rollback documenté de l'application complète ni exercice de restauration n'est livré. La sauvegarde Infomaniak éventuelle n'a pas été contrôlée. Prévoir en particulier une réconciliation avec le prestataire après restauration pour ne pas perdre des paiements reçus entre la sauvegarde et l'incident.

## 10. SEO, performance et qualité du dépôt

### Référencement et publication

- Les 14 nouvelles pages ont chacune un H1 et un titre dans les contrôles navigateur ; les pages principales disposent d'une description.
- Aucun canonical sur les pages inspectées, aucun sitemap dans le dépôt et aucun `og:image`/`og:url`. L'accueil contient seulement trois propriétés Open Graph ; les autres pages n'ont pas de dispositif social comparable.
- Seules les pages 404/retour/annulation portent `noindex`. Les pages remplies de textes de préproduction pourraient être indexées si mises en ligne sans protection. Prévoir préproduction authentifiée, politique d'indexation et ouverture volontaire au lancement.
- `404.html` est un fichier de présentation, pas une preuve de routage d'erreur. Sur le serveur statique de test, l'accès direct au fichier répond 200 ; sur l'application PHP, une route inconnue répond JSON. Brancher la présentation sur un vrai statut 404 dans le serveur final.
- Les anciens chemins/ancres n'ont pas de plan de redirection implémenté ; nécessaire si l'ancien prototype a déjà été publié.

### Performance

Poids non compressés observés : JavaScript partagé **5 402 octets**, CSS **22 352 octets**, accueil **11 736 octets**, logo **73 293 octets**, soit environ **110 Kio** pour ces quatre ressources. Le frontend est léger et n'utilise pas de bibliothèque lourde ni de photos externes dans les nouvelles pages.

Ces chiffres ne sont pas une mesure de chargement réel chez Infomaniak. Les 98 contrôles ne produisent pas un score de performance. Les images répétées bénéficient normalement du cache navigateur, mais les en-têtes de cache/compression n'ont pas été audités sur le futur serveur. Le logo déformé produit surtout un problème de rendu et de longueur de page, même si son poids est faible.

### Maintenabilité et tests

- CSS et JavaScript ont été séparés du HTML : progrès par rapport au prototype monolithique.
- La plupart des pages sont écrites sur une à quelques lignes, avec composants dupliqués et styles inline. Cela rend les corrections, revues de diff et adaptations globales plus fragiles.
- Aucun composant partagé d'en-tête/footer ni moteur de gabarits ne relie les pages au backend.
- Le seul test livré vérifie `Money` et un code TOTP invalide. Aucun scénario HTTP, SQL cible, paiement, remboursement, session, publication, newsletter ou migration n'est fourni.
- Les assertions positives de `SmokeTest.php` dépendent du paramétrage PHP. L'audit les a activées explicitement ; la commande Composer du dépôt ne le fait pas. Remplacer par une suite à assertions toujours effectives.
- Les avertissements PHP sur `use InvalidArgumentException`, `use Throwable` et `use PDO` dans l'espace global doivent être nettoyés. Ils n'ont pas empêché le lint de passer et ne sont pas la cause principale A01.
- Pas de pipeline CI ni de procédure reproductible vérifiant la livraison complète. Le passage du smoke test ne permet pas de déclarer le backend opérationnel.
- Aucun secret de production n'a été identifié dans les fichiers suivis examinés ; les exemples contiennent des placeholders. Cette revue ne remplace pas un scan exhaustif de l'historique Git ni le contrôle des secrets du compte d'hébergement.

## 11. Ordre de correction recommandé

| Étape | Travail | Critère de sortie |
| --- | --- | --- |
| 1 — Démarrage | A01, A02, A23 ; configuration stricte ; test HTTP minimal | Application démarrable, jeton/session cohérents, erreurs contrôlées |
| 2 — Architecture et SQL | Décider PHP autonome/Laravel, contrat d'intégration, racine publique, migrations cible | Site et API servis ensemble, installation neuve reproductible sur SQL cible |
| 3 — Visuel immédiat | A18 à A22, variantes logo et composants partagés | Logo non déformé, contrastes conformes, montants cohérents, contact sûr sans JS |
| 4 — Don fiable en sandbox | A05 à A12, adaptateur officiel, vérification montant/mode/session | Parcours complet et tests financiers, y compris timeout, doublons et remboursements en attente |
| 5 — Administration et édition | CMS, médias, droits, MFA et support | Publication d'un projet/article par un compte autorisé, sans modifier les fichiers |
| 6 — Communication | A14 à A17, pages newsletter, transport e-mail et supervision | Contact traité, newsletter confirmée/désinscrite depuis les messages de test |
| 7 — Finance et exploitation | Reçus, rapprochement, audit, purge, sauvegarde et restauration | Historique fiable et opérations récupérables après panne |
| 8 — Recette de lancement | Contenus validés, compte prestataire, DNS/HTTPS/SMTP, SEO et tests réels encadrés | Checklist de lancement du plan satisfaite et preuves de recette conservées |

Ne pas activer le don mensuel tant que ses échéances et sa résiliation ne sont pas implémentées et testées. Le paiement réel arrive après les corrections de logique ; il ne suffit pas d'ajouter des clés API.

## 12. Recette à exiger lors de la prochaine livraison

- Installation depuis un clone neuf : base cible, dépendances, configuration, `/up`, racine publique et assets.
- GET CSRF puis POST avec cookie jar ; connexion, MFA, expiration, révocation et déconnexion.
- Don 1.10/4.10/50/150.50 CHF, montant vide/négatif/trop précis/trop grand, montant reçu dans un lien et projet suspendu.
- Contrat frontend/backend vérifié : coordonnées, fréquence, affectation, montant et idempotence.
- Paiement en sandbox : accepté, refusé, expiré, timeout, retour avant webhook et absence de retour navigateur.
- Webhook : signature invalide, événement non payé, mauvais mode/session/montant/devise, doublon exact et événements dans un ordre différent.
- Remboursement : pending/failed/succeeded, total/partiel, répétition, dépassement, concurrence et panne après appel externe.
- Abonnement si activé : nouveau don pour chaque échéance payée, échec, reprise et résiliation vérifiée.
- Newsletter : inscription nouvelle/existante/active/supprimée, token expiré, réinscription et désinscription par lien réellement envoyé.
- Contact avec et sans JS, limite de débit dans plusieurs fuseaux, purge effectivement exécutée.
- Jobs : interruption, reprise, cinq échecs, succès suivi de panne et alerte reçue par l'opérateur.
- Administration : CRUD, brouillon inaccessible, permissions directes, upload malveillant et téléchargement privé d'autrui refusé.
- UI : ordinateur/mobile, zoom, clavier/lecteur d'écran, contrastes et proportions du logo sur toutes les pages.
- Livraison : pas de fichiers privés exposés, page 404 avec statut correct, préproduction protégée, sauvegarde restaurée puis paiements rapprochés.

## 13. Preuves conservées et reproductibilité

Les résultats utiles sont copiés dans [le dossier de preuves](C:/Users/diplk/Documents/amanah/docs/audits/preuves/2026-09-13). Ils contiennent seulement des données fictives et des captures locales.

| Fichier | Contenu |
| --- | --- |
| [frontend-results.json](C:/Users/diplk/Documents/amanah/docs/audits/preuves/2026-09-13/frontend-results.json) | 98 contrôles, montants, menu/FAQ, paramètre de montant et contact sans JS |
| [frontend-details.json](C:/Users/diplk/Documents/amanah/docs/audits/preuves/2026-09-13/frontend-details.json) | Texte des pages, images, dimensions, liens, métadonnées et contrastes ciblés |
| [php-lint.json](C:/Users/diplk/Documents/amanah/docs/audits/preuves/2026-09-13/php-lint.json) | Lint des 22 fichiers, sorties et avertissements |
| [http-results.json](C:/Users/diplk/Documents/amanah/docs/audits/preuves/2026-09-13/http-results.json) | Démarrage normal puis scénario CSRF avec contournement de diagnostic |
| [http-extra-results.json](C:/Users/diplk/Documents/amanah/docs/audits/preuves/2026-09-13/http-extra-results.json) | Routes publiques/admin absentes, réponse newsletter et refus webcron sans jeton |
| [backend-repro.jsonl](C:/Users/diplk/Documents/amanah/docs/audits/preuves/2026-09-13/backend-repro.jsonl) | Dix scénarios métier, dont deux remboursements pending dépassant le don |
| [newsletter-results.json](C:/Users/diplk/Documents/amanah/docs/audits/preuves/2026-09-13/newsletter-results.json) | Abonné rétrogradé, incohérence de désinscription et hypothèse de collision écartée |
| [jobs-auth-results.json](C:/Users/diplk/Documents/amanah/docs/audits/preuves/2026-09-13/jobs-auth-results.json) | Fuseau du limiteur, jobs, absence de MFA imposée et révocation effective |

Scripts de reproduction conservés : [backend-repro.php](C:/Users/diplk/Documents/amanah/docs/audits/preuves/2026-09-13/backend-repro.php), [newsletter-security.php](C:/Users/diplk/Documents/amanah/docs/audits/preuves/2026-09-13/newsletter-security.php), [jobs-auth-probe.php](C:/Users/diplk/Documents/amanah/docs/audits/preuves/2026-09-13/jobs-auth-probe.php), [routeur de diagnostic](C:/Users/diplk/Documents/amanah/docs/audits/preuves/2026-09-13/router-session-probe.php), [contrôles navigateur](C:/Users/diplk/Documents/amanah/docs/audits/preuves/2026-09-13/frontend-audit.cjs) et [contrôles visuels détaillés](C:/Users/diplk/Documents/amanah/docs/audits/preuves/2026-09-13/frontend-details.cjs).

Ces scripts sont des preuves d'audit, pas des composants à déployer. Ils utilisent les chemins du workspace actuel ; adapter les chemins du runtime sur une autre machine. Les tests PHP métier utilisent SQLite en mémoire, chargent explicitement le fichier des gateways et ne contactent aucun prestataire. Le test jobs intercepte `mail()` au niveau du namespace pour ne rien envoyer.

Exemple de relance locale après mise à disposition de PHP et de `pdo_sqlite` :

```powershell
php -d zend.assertions=1 -d assert.exception=1 C:/Users/diplk/Documents/amanah/backend/tests/SmokeTest.php
php C:/Users/diplk/Documents/amanah/docs/audits/preuves/2026-09-13/backend-repro.php
php C:/Users/diplk/Documents/amanah/docs/audits/preuves/2026-09-13/newsletter-security.php
php C:/Users/diplk/Documents/amanah/docs/audits/preuves/2026-09-13/jobs-auth-probe.php
```

### Limite des preuves

Un test passant avec un adaptateur simulé ne valide pas le SDK, les événements exacts ni les règles du compte prestataire. Les scénarios synthétiques constituent des tests des invariants du code actuel. Les conditions de charge, MySQL/MariaDB, SMTP, DNS et Infomaniak devront faire l'objet d'une recette dédiée.

## 14. Conclusion de réception

**Acceptable comme étape intermédiaire :** base graphique, arborescence statique, prudence sur les contenus non validés et premières briques PHP.

**Non acceptable comme application complète du plan :** absence d'intégration, de back-office et de paiement réel, démarrage cassé, sessions incohérentes, erreurs financières reproduites et exploitation non livrée.

La prochaine étape doit être une livraison intégrée et testable, avec une recette fondée sur les anomalies de ce rapport. Les contenus officiels doivent être fournis par Amanah ; les mécanismes pour les publier et traiter les dons doivent être réalisés par le développement.

**Changements effectués pendant cet audit :** création de ce rapport et de son dossier de preuves uniquement. Aucun fichier de l'application n'a été corrigé, aucun commit n'a été créé, aucun service externe n'a reçu de paiement ou de message.
