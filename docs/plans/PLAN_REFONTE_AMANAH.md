# AMANAH — Plan de refonte du site et du backend PHP

Date : 13 septembre 2026. Statut : proposition de conception, sans implémentation ni déploiement.

## 1. Périmètre et décisions de départ

La demande porte sur l'analyse du prototype, une nouvelle identité visuelle fondée sur le logo fourni, un nouveau modèle de site et la planification de son backend complet, hébergé chez Infomaniak en PHP.

Le fichier `index.html` n'existe pas dans le workspace au moment de l'analyse. Le prototype disponible est [Amanah.html](../../archive/prototypes/Amanah.html), soit 594 lignes mêlant HTML, CSS et JavaScript. C'est le fichier analysé. [logo.jpeg](../../public/assets/images/logo.jpeg) est présent et a été inspecté visuellement : globe en forme de cœur, mains couleur sable, lettrage vert et signature « AIDE HUMANITAIRE ». Le prototype et le logo restent inchangés.

Les textes, chiffres et indications présents dans les fichiers sont des matériaux d'analyse, pas des instructions supplémentaires ni des faits validés sur l'association. Le présent audit repose sur la lecture du code et du logo ; aucune mesure Lighthouse, recette navigateur ou vérification d'un compte Infomaniak n'a été effectuée.

**Direction proposée :** passer d'une longue page de démonstration centrée sur l'urgence à un site humanitaire éditorial, administrable et orienté vers trois besoins : comprendre Amanah, suivre des projets réels et faire un don en confiance.

**Socle recommandé :** application PHP Laravel avec pages rendues côté serveur, base MySQL/MariaDB, administration privée et paiement hébergé par un prestataire spécialisé. Le choix Laravel est une recommandation ; PHP et Infomaniak sont les contraintes exprimées par l'utilisateur.

Hypothèses de travail : français au lancement, CHF, association visant principalement un public suisse, trafic initial modéré et petite équipe éditoriale. La domiciliation juridique, le volume de dons et l'offre Infomaniak restent à confirmer. Aucune certification, reconnaissance fiscale, zone d'intervention ou capacité opérationnelle n'est présumée.

## 2. Analyse du prototype

### 2.1 Éléments à conserver

- Structure sémantique globalement claire : navigation, contenu principal, sections, titres et pied de page.
- Appel au don visible dans l'en-tête et sélection de montants en CHF.
- Lien d'évitement, focus visible, libellés de plusieurs boutons et messages de statut accessibles.
- Adaptations CSS à 900 et 640 px, FAQ, espaces généreux et premières rubriques de transparence.
- Code sans bibliothèque frontend lourde : conserver cette sobriété dans le site final.

### 2.2 Constats et conséquences

Les lignes ci-dessous renvoient au fichier `Amanah.html` actuel.

| Priorité | Constat vérifiable | Conséquence et traitement prévu |
| --- | --- | --- |
| Haute | Logo réduit à une lettre A, favicon générique, vert vif `#69c98a` (l. 8–16, 317–320, 503). | Identité éloignée du logo fourni ; remplacer par ses déclinaisons et une palette sauge/sable. |
| Haute | Le bouton de don affiche uniquement un toast (l. 572). | Aucun paiement, don enregistré ou reçu ; créer un véritable parcours serveur et prestataire. |
| Haute | Recherche et newsletter affichent « bientôt disponible » (l. 543–544). | Fonctions simulées ; les connecter ou les retirer jusqu'à disponibilité. |
| Haute | « 24/7 », partenaires, résultats associés aux montants et récits datés figurent dans la maquette (l. 371–375, 437–439, 491–493). | Exiger une source et une validation éditoriale ; retirer tout contenu non démontrable. |
| Haute | `bonjour@amanah.example`, téléphone nul, articles et réseaux en `href="#"` (l. 481–506). | Parcours sans destination réelle ; inventaire des liens, coordonnées officielles et pages finales. |
| Haute | Le montant libre est mémorisé seulement si le champ n'est pas vide (l. 561–566). | L'effacer conserve l'ancien montant ; le bouton hors formulaire n'impose pas la validité HTML. Prévoir validation client et serveur, erreurs explicites et état vide. |
| Moyenne | « Choisir ce montant » mène seulement à `#donate` (l. 437–439). | Le montant affiché sur la carte n'est pas sélectionné ; transmettre montant et projet au parcours. |
| Moyenne | Photos chargées depuis quatre domaines tiers (Wix, AA, Big Give, Snappages). | Dépendance externe et provenance à vérifier ; utiliser des médias autorisés et hébergés localement. Les descriptions alternatives doivent être vérifiées contre chaque photo. |
| Moyenne | Grande photo avant le titre ; image de 300 px minimum même sur mobile (l. 355–363 et CSS `.hero-media`). | Mission et contexte du don peuvent arriver tard dans le premier écran ; revoir la composition. |
| Moyenne | Fréquences et montants sélectionnés signalés seulement par classe CSS ; points du carrousel sans état actif accessible. | Utiliser des radios natifs et un état sélectionné perceptible sans couleur. |
| Moyenne | Classe `sr-only` utilisée sans définition ; FAQ sans `aria-controls` ; défilement animé sans préférence de mouvement réduit. | Compléter les composants accessibles, gestion du focus et réduction des animations. |
| Moyenne | Images sans attributs intrinsèques de dimensions, ni `srcset`, ni chargement différé. | Prévoir images responsives et réservations d'espace ; mesurer ensuite LCP et stabilité visuelle. |
| Moyenne | Une seule page, métadonnées générales, absence de pages projet/article et de partage fonctionnel. | Créer des URL éditoriales, métadonnées par page, sitemap et aperçus sociaux. |
| Moyenne | Contenus, styles et logique dans un seul fichier. | Extraire gabarits, composants, styles et services ; données éditables depuis l'administration. |

### 2.3 Transformation des sections

| Prototype | Modèle futur |
| --- | --- |
| Photo géante puis discours général | Introduction avec mission courte, photographie contextualisée et deux actions |
| Mission + disponibilité 24/7 | Présentation vérifiée de l'association et engagements concrets |
| Module de don intégré | Résumé de don sur l'accueil, parcours dédié `/faire-un-don` |
| Carrousel terrain | Grille de projets ; fiche détaillée par action |
| Promesses d'impact par montant | Exemples financés documentés, ou montants sans équivalence inventée |
| Liste « transparence » | Page ressources, rapports, gouvernance et utilisation documentée des fonds |
| Newsletter simulée | Formulaire avec confirmation d'inscription et désinscription |
| FAQ et actualités longues sur l'accueil | Extraits utiles avec liens vers pages dédiées |

## 3. Identité visuelle proposée

### 3.1 Intention de marque

Le cœur évoque le soin, les mains l'accompagnement, le globe la solidarité. Traduire ces éléments par une interface chaleureuse, posée et précise : fond ivoire, vert profond pour les actions, sable en soutien, photographies humaines et espace autour des contenus.

Conserver « AMANAH — Aide humanitaire » comme signature du logo. Proposition de titre d'accueil à valider : « Ensemble, apportons une aide concrète. » Le sous-titre final expliquera qui agit, où et comment, à partir d'informations fournies par Amanah. Le mot « urgence » ne doit pas définir tout le site si l'activité réelle est plus large.

### 3.2 Palette de travail

Ces couleurs sont proposées d'après l'observation du JPEG ; ce ne sont pas des références de marque extraites d'un fichier vectoriel.

| Jeton | Valeur proposée | Usage |
| --- | --- | --- |
| `brand-forest` | `#3F5144` | Boutons principaux, titres forts, pied de page |
| `brand-sage` | `#65725E` | Accents graphiques, pictogrammes, détails du logo |
| `brand-sand` | `#B9A18A` | Fonds décoratifs et éléments d'accompagnement |
| `surface-ivory` | `#FAF8F4` | Fond principal chaleureux |
| `surface-white` | `#FFFFFF` | Formulaires, cartes et surfaces de lecture |
| `surface-sage` | `#EDF0E9` | Sections secondaires |
| `text-primary` | `#243229` | Texte courant |
| `text-secondary` | `#526056` | Texte complémentaire |
| `border-subtle` | `#DADFD6` | Séparateurs décoratifs ; renforcer pour champs interactifs |
| `feedback-error` | `#A33333` | Erreurs avec texte et icône |

Tester tous les couples texte/fond et les états avant validation. Ne pas utiliser du blanc sur sable pour du petit texte. Les erreurs, sélections et succès doivent rester compréhensibles sans perception des couleurs.

### 3.3 Logo et ressources

- Conserver `logo.jpeg` comme original. Son grand format carré et ses marges ne conviennent pas directement à un petit en-tête.
- Préparer un logo complet pour les espaces de marque et une composition horizontale pour l'en-tête ; préserver les proportions, les mains et le cœur.
- Préférer un original SVG/PDF vectoriel si disponible. Sinon, programmer une vectorisation contrôlée avec comparaison au logo ; une conversion de format seule ne recrée pas un vrai vectoriel.
- Préparer un symbole simplifié lisible à 16/32 px pour le favicon, ainsi qu'une icône 180 px. Une simplification graphique est à valider avant utilisation.
- Garder la signature humanitaire lisible ; sur mobile, utiliser une variante compacte validée plutôt qu'un logo complet devenu illisible.
- Définir une zone de protection d'environ un quart de la hauteur du symbole. Ne pas étirer, recolorer arbitrairement ou placer le JPEG ivoire sur un fond contrastant.
- Livrables futurs : SVG contrôlé, WebP/PNG de repli, variantes horizontale/complète, favicon, visuel de partage et fiche d'usage.

### 3.4 Typographie et composants

Direction : une sans-serif humaniste pour le texte et l'interface, avec titres sobres de la même famille. Tester une candidate telle que Source Sans 3 ; choix final après comparaison avec le logo et vérification de licence. Polices WOFF2 auto-hébergées, deux ou trois graisses au maximum, repli système et `font-display: swap`.

Base : texte 17–18 px sur grand écran, au moins 16 px dans les champs, interligne 1,55–1,7 ; titres d'accueil autour de 48–64 px sur ordinateur et 34–42 px sur mobile. Corps limité à environ 65–75 caractères par ligne. Largeur de contenu 1160–1200 px, grille 12 colonnes sur ordinateur puis 4 sur mobile, espacements de 8/16/24/32/48/64 px.

Composants à concevoir : en-tête, menu mobile, boutons primaire/secondaire/texte, carte projet, étiquette de statut, chiffre sourcé, carte actualité, accordéon FAQ, formulaire, sélecteur de montant, progression du don, alerte et état vide. Rayons cohérents de 12–16 px ; ombres discrètes ; limiter les grandes pilules aux éléments qui en bénéficient.

Photographies : privilégier l'action et la dignité des personnes, avec provenance, crédits, autorisations et légendes. Éviter les visuels de catastrophe employés comme preuve d'une intervention Amanah sans lien établi. Pas d'animation automatique nécessaire au lancement.

## 4. Nouveau modèle de site

### 4.1 Arborescence cible

| Page / URL | Rôle | Contenu administrable principal |
| --- | --- | --- |
| `/` | Comprendre et choisir une prochaine action | Mission, projets mis en avant, preuve de transparence, actualités |
| `/association` | Identifier Amanah | Histoire, équipe/gouvernance, fonctionnement et partenaires vérifiés |
| `/projets` | Découvrir les actions | Liste paginée, filtres simples si le volume le justifie |
| `/projets/{slug}` | Comprendre un projet et le soutenir | Besoin, lieu public, objectifs, statut, budget validé, mises à jour et médias |
| `/faire-un-don` | Effectuer un don | Projet ou fonds général, montant, fréquence, coordonnées et récapitulatif |
| `/don/retour`, `/don/annulation` | Expliquer l'état du paiement | Confirmation vérifiée, attente, interruption et reprise |
| `/transparence` | Montrer comment fonctionne l'association | Rapports, comptes publiables, allocation et méthode des indicateurs |
| `/actualites`, `/actualites/{slug}` | Suivre les actions | Articles, dates, crédits et liens vers projets |
| `/faq` | Répondre aux questions | Paiement, récurrence, utilisation, contact et documents disponibles |
| `/contact` | Contacter l'équipe | Coordonnées réelles et formulaire |
| `/mentions-legales`, `/confidentialite` | Informer | Identité de l'entité, traitements et prestataires réellement utilisés |
| `/conditions-des-dons` | Expliquer l'engagement | Affectation, récurrence, interruption et politique de remboursement validées |
| `/admin` | Administrer | Accès privé par rôle |

Navigation principale : Projets, L'association, Transparence, Actualités, avec « Faire un don » distinct. Contact, FAQ et informations légales dans le pied de page. Pas de recherche affichée au lancement si la navigation et le faible volume de contenu suffisent.

### 4.2 Composition de l'accueil

1. En-tête compact : marque, navigation et appel au don.
2. Premier écran : titre, explication de mission, bouton « Découvrir nos projets », bouton « Faire un don » et une photo réelle. Sur mobile, placer le message et les actions avant la grande photo.
3. Présentation courte : qui porte Amanah et comment l'aide est organisée ; lien vers l'association.
4. Projets prioritaires : jusqu'à trois cartes avec lieu, statut, besoin et lien. Sans projet publiable, afficher une présentation honnête de la préparation des actions.
5. Transparence : méthode d'action et accès aux documents ; chiffres uniquement accompagnés de période, source et date de mise à jour.
6. Invitation au don : montant libre et quelques montants proposés, sans affirmation d'équivalence non prouvée.
7. Deux ou trois nouvelles réelles, puis newsletter et FAQ courte.
8. Pied de page : logo, coordonnées, pages utiles et réseaux actifs.

### 4.3 Règles des projets et contenus

Un projet comporte un titre, un résumé, un slug stable, une zone géographique publiable, un besoin, une description de l'action, un responsable interne, des dates, un statut et des mises à jour. Séparer le statut éditorial (brouillon, publié, archivé) du statut opérationnel (préparation, actif, terminé, suspendu).

Une jauge de collecte est facultative. Ne la publier que si objectif, affectation des dons et méthode de calcul sont fiables. Documenter si le montant affiché est brut ou net ; exclure les paiements en attente et déduire les remboursements. Distinguer don confirmé et fonds effectivement versés sur le compte bancaire.

Une politique validée doit traiter les dons à un projet terminé, les objectifs dépassés et une éventuelle réaffectation. Ne jamais réaffecter silencieusement le choix du donateur. Les dons externes peuvent être saisis après rapprochement, avec référence unique et justificatif privé.

### 4.4 Parcours du donateur

Parcours sans création de compte obligatoire : choix du projet/fonds → montant et fréquence → coordonnées minimales → récapitulatif → paiement externe → retour et confirmation.

Les montants 50/150/250 CHF sont des paramètres initiaux à valider. Conserver un montant libre et rendre le choix explicite. Le serveur accepte une représentation décimale contrôlée, la convertit en centimes entiers, vérifie les limites du moyen de paiement et rejette valeur négative, nulle, trop précise ou excessive.

Collecter uniquement les coordonnées nécessaires au paiement et à la confirmation ; demander une adresse postale seulement pour une finalité définie. Newsletter facultative, décochée et indépendante du don. Afficher clairement fréquence, devise, affectation et éventuels frais effectivement applicables avant paiement.

Prévoir les états chargement, erreur de saisie, prestataire indisponible, paiement refusé, retour interrompu, paiement encore en traitement et paiement confirmé. Le bouton de retour du prestataire ne constitue jamais une preuve de paiement.

### 4.5 Accessibilité, référencement et performance

Objectif de recette : WCAG 2.2 niveau AA, à vérifier lors de l'implémentation ; navigation clavier complète, contrastes mesurés, zoom 200 %, champs associés aux erreurs et annonce des changements de statut. Utiliser des radios pour montant/fréquence, des accordéons natifs si adaptés, retour du focus à la fermeture du menu et prise en charge de la touche Échap.

Prévoir titres/meta descriptions uniques, URL canoniques, Open Graph, sitemap des seuls contenus publiés, 404 utile, redirections des anciennes URL si elles ont été publiées et données structurées correspondant uniquement à des faits vérifiés. Administration, résultats privés de dons et préproduction hors indexation ; l'authentification protège réellement les données privées.

Budgets de conception : page d'accueil proche de 1 Mo au premier chargement, JavaScript propre au site inférieur à 80 Ko compressés, image principale idéalement sous 250 Ko. Cibles : LCP ≤ 2,5 s, CLS ≤ 0,1, INP ≤ 200 ms dans les conditions de mesure documentées. Ce sont des objectifs, pas des scores observés. Générer `srcset`, dimensions, WebP/AVIF selon compatibilité et chargement différé hors premier écran ; ne pas retarder l'image principale.

## 5. Architecture PHP et compatibilité Infomaniak

### 5.1 Socle technique

Proposition : Laravel 13 avec Blade, PHP 8.3 minimum et de préférence une version PHP plus récente encore maintenue compatible avec toutes les dépendances. Verrouiller les versions au début du développement après vérification du compte Infomaniak, de PHP web/CLI et de la fenêtre de maintenance. La documentation Laravel consultée exige PHP ≥ 8.3 et les extensions listées dans son guide de déploiement. [Source Laravel](https://laravel.com/framework/docs/deployment).

Infomaniak documente Laravel sur hébergement mutualisé ou Cloud, avec SSH, base MySQL/MariaDB et racine web dirigée vers `public`. Son exemple vise Laravel 11/PHP 8.2 : il confirme le principe d'hébergement, mais ne valide pas automatiquement la combinaison plus récente proposée ici. [Guide Infomaniak](https://www.infomaniak.com/en/support/faq/2119/install-laravel-on-an-infomaniak-hosting-account).

| Couche | Choix proposé et raison |
| --- | --- |
| Frontend | Blade, CSS structuré avec variables de marque, JavaScript léger pour les interactions |
| Backend | Monolithe modulaire Laravel : validation, sessions, permissions, ORM, migrations et courriels |
| Base | MySQL/MariaDB compatible, tables transactionnelles InnoDB, UTF-8 `utf8mb4` |
| Administration | Écrans Blade dédiés utilisant la même logique métier ; éviter un second backend |
| Dépendances | Composer avec fichier de verrouillage ; assets compilés en local/CI et livrés au serveur |
| Fichiers | Images publiques dérivées ; originaux et pièces privées hors racine publique |
| Paiement | Adaptateur prestataire, page de paiement hébergée et webhooks HTTPS |
| E-mails | SMTP authentifié pour le transactionnel ; service de newsletter distinct si envois de masse |
| Tâches | File en base, exécutions bornées et verrouillées compatibles mutualisé |
| Sessions/cache | Base pour sessions/verrous partagés ; cache fichiers ou base selon configuration |

Pas de serveur Node en production, de Redis obligatoire, de WebSocket ou de processus permanent requis pour le lancement. Ne pas supposer les droits root ou la présence de Supervisor sur l'hébergement mutualisé.

### 5.2 Organisation future du projet

```text
app/
  Http/Controllers/       # pages publiques, administration, webhooks
  Http/Requests/          # validation serveur
  Models/                # données et relations
  Policies/              # autorisations par objet et par action
  Services/Content/       # publication et révisions
  Services/Donations/     # dons, récurrence et rapprochement
  Services/Payments/      # interface prestataire et adaptateur choisi
  Services/Media/         # traitement et accès aux fichiers
  Jobs/                  # e-mails, exports et reprises
  Console/Commands/      # opérations et maintenance
resources/views/         # gabarits Blade et composants
resources/css/           # fondations et composants de marque
resources/js/            # interactions progressives
routes/                  # routes publiques, admin et techniques
database/migrations/     # schéma versionné
storage/app/private/     # reçus, exports, originaux privés
public/                  # seule racine web : index.php et assets publics
tests/                   # tests métier, HTTP et intégration
```

La séparation est logique dans un seul déploiement. Aucun microservice ni API publique généraliste n'est nécessaire. Prévoir une interface de paiement limitée aux opérations réellement utilisées, sans développer plusieurs intégrations d'avance.

### 5.3 Traitements différés sur mutualisé

Le planificateur Web Infomaniak appelle une URL et son intervalle minimal documenté est de 15 minutes sur mutualisé, contre une minute sur Cloud. Concevoir le traitement autour de cette contrainte, sans supposer un cron SSH chaque minute. [Source Infomaniak](https://www.infomaniak.com/fr/support/faq/2161/planifier-des-taches-sur-hebergement-web).

- Prévoir une route technique HTTPS avec authentification dédiée compatible avec le planificateur ; aucun secret dans les paramètres d'URL, aucun nom de commande arbitraire transmis par requête.
- Exécuter un lot borné selon le temps/mémoire disponibles, avec verrou en base et expiration ; libérer ou récupérer les réservations après interruption.
- Choisir les tâches dues via `next_run_at <= maintenant`, afin qu'un appel manqué soit rattrapé au passage suivant. Ne pas dépendre d'une minute exacte.
- Confirmer les dons via un traitement transactionnel bref du webhook ou une vérification serveur du paiement lors du retour ; les e-mails/reçus et autres travaux lourds passent dans une boîte d'envoi persistante.
- Accepter au lancement un délai d'e-mail pouvant atteindre environ 15 minutes, augmenté des reprises. Afficher la confirmation du paiement sur la page indépendamment de cet envoi.
- Surveiller l'âge de la plus ancienne tâche, le dernier passage réussi et les échecs. Si le volume ou le délai cible exige un worker continu, revoir l'offre Infomaniak avant de promettre ce service.

## 6. Backend fonctionnel et administration

| Module | Fonctions au lancement | Contrôles et états |
| --- | --- | --- |
| Accès administration | Invitation, connexion, réinitialisation, MFA, déconnexion et révocation | Aucune inscription admin publique ; limitation des essais et expiration des sessions |
| Pages et navigation | Modifier les rubriques prévues, prévisualiser, publier et archiver | Blocs autorisés, contenu HTML assaini, historique des révisions |
| Projets | Fiches, statuts, images, mises à jour, éventuel objectif | Publication réservée au rôle habilité ; affectation financière contrôlée |
| Actualités et FAQ | Rédaction, ordre, publication immédiate ou planifiée | Brouillon inaccessible au public ; planification à la granularité de l'hébergement |
| Médiathèque | Upload, crédits, texte alternatif, droits et dérivés | Taille/type contrôlés, aucun fichier exécutable, visibilité publique/privée |
| Documents de transparence | Rapport, exercice, version et publication | Publication explicite ; pièces donateurs exclues de cette bibliothèque |
| Dons | Liste, filtres, détail, tentatives et suivi du paiement | Lecture du statut vérifié ; aucune édition libre d'un paiement confirmé |
| Récurrence | Abonnement, prochaine échéance connue, échec, résiliation | Pilotage du débit par le prestataire ; confirmation de chaque échéance |
| Finance | Export, rapprochement, dons externes, remboursements et litiges | Autorisation forte, audit et référence unique ; pas de suppression comptable silencieuse |
| Contact | Réception, notification, attribution et clôture | Anti-spam, rétention et accès limité |
| Newsletter | Inscription, confirmation, désinscription, synchronisation | Preuve de choix, liste de suppression et reprises sans réabonnement involontaire |
| Paramètres | Coordonnées, réseaux, montants suggérés, SEO général | Réglages typés ; clés prestataires gérées hors éditeur public |
| Exploitation | Échecs de tâches, santé des intégrations, audit | Données sensibles masquées ; reprise d'une opération de manière idempotente |

Rôles : **administrateur** (comptes et configuration), **éditeur** (contenus et médias), **finance** (dons, rapprochement et remboursements), **support** (demandes et informations minimales utiles). Une personne peut cumuler des rôles ; les permissions restent distinctes. Vérifier les droits sur chaque route et objet, pas seulement sur les menus.

Le donateur n'a pas besoin d'un espace personnel au lancement. La gestion d'un abonnement passe par le portail sécurisé du prestataire, ouvert après vérification de l'adresse via un lien temporaire à usage unique et session courte. Les liens ne doivent pas être indexés, journalisés en clair ou chargés avec des outils tiers de suivi.

## 7. Modèle de données

Schéma logique à transformer en migrations. Les identifiants internes restent distincts des références publiques aléatoires. Dates en UTC, affichage localisé Europe/Zurich. Montants en centimes entiers, devise explicite ; aucune arithmétique financière en flottants.

| Entités | Champs / relations essentiels |
| --- | --- |
| `users`, `roles`, `role_user` | Email unique, hash mot de passe, MFA chiffré, statut, dernière connexion ; utilisateurs internes |
| `pages`, `page_revisions` | Slug unique, titre, blocs structurés, SEO, statut, auteur, version et publication |
| `projects`, `project_updates` | Slug, résumé, description, lieu public, statuts éditorial/opérationnel, dates, objectif optionnel ; mises à jour liées |
| `posts`, `faqs` | Contenu, ordre/catégorie si utile, statut, publication, projet optionnel |
| `media`, `media_usages` | Chemin, type MIME, dimensions, taille, visibilité, crédit, droits, texte alternatif ; usages dans contenus |
| `documents` | Média lié, titre, exercice, version et visibilité publique explicitement approuvée |
| `donors` | Email et coordonnées nécessaires, langue, référence client prestataire éventuelle ; pas de compte implicite |
| `donations` | Référence publique, donateur optionnel, projet optionnel, montant attendu, devise, fréquence, abonnement optionnel et statut |
| `payment_attempts` | Don lié, clé d'idempotence, session et identifiant paiement prestataire, mode test/live, statut et dates |
| `subscriptions` | Donateur/projet, référence prestataire, montant/période, statut, dates et demande de résiliation |
| `payment_events` | Prestataire, mode, identifiant événement unique, objet concerné, état de traitement, tentative et erreur masquée |
| `refunds`, `disputes` | Paiement lié, référence externe unique, montant, motif, statut, initiateur et dates |
| `financial_entries`, `payouts`, `payout_items` | Écritures immuables de don/frais/remboursement/ajustement ; versements prestataire et lignes rapprochées |
| `receipts` | Don ou période liée, numéro unique, type de document, version, fichier privé, émission/annulation |
| `contact_messages` | Identité minimale, message, statut, personne assignée, date prévue de purge |
| `newsletter_subscribers`, `consent_events` | Email normalisé unique, état, jeton haché/expiration, texte de choix versionné et événements datés |
| `audit_logs` | Acteur, action, objet, date, résultat et modification pertinente expurgée des secrets |
| `outbox_messages`, `jobs`, `failed_jobs` | Action métier persistante, clé de déduplication, disponibilité, réservation et nombre d'essais |
| `settings`, `scheduled_tasks`, `sessions` | Paramètres typés, échéances/verrous/dernière exécution et sessions serveur |

Relations principales : un donateur peut faire plusieurs dons ; un projet reçoit plusieurs dons ; un don peut avoir plusieurs tentatives de paiement ; un abonnement produit un don distinct par échéance réellement payée ; un paiement peut avoir plusieurs remboursements partiels. Une écriture d'ajustement corrige l'historique financier sans le réécrire.

Contraintes à prévoir : clés étrangères, index sur statuts/dates/projet, slugs uniques, identifiants prestataire uniques par prestataire et environnement, montant positif, devise autorisée, remboursement cumulé inférieur ou égal au montant encaissé. Verrouillage transactionnel pour les remboursements concurrents. Les états de paiement, remboursement et litige sont suivis séparément lorsque nécessaire.

Les transactions financières conservent un instantané des informations nécessaires au justificatif : une modification ultérieure du profil ne modifie pas les documents historiques. Définir avec l'association les durées de conservation et règles d'anonymisation avant production ; ne pas appliquer une suppression en cascade des donateurs aux paiements. Aucun dossier individuel de bénéficiaire humanitaire n'est prévu dans ce site.

## 8. Paiements, récurrence et rapprochement

### 8.1 Prestataire et moyens de paiement

Prévoir une page de paiement hébergée avec SDK PHP officiel. **Candidat de travail : Stripe Checkout**, à confirmer après examen de l'éligibilité de l'entité, des conditions, coûts, devises, versements et méthodes activables. Ne pas signer de contrat ni créer de compte dans cette phase de planification.

Priorité fonctionnelle : don ponctuel par carte et TWINT si le compte le permet ; mensuel si la récurrence est validée ; annuel en extension. La documentation Stripe consultée annonce TWINT en CHF, avec Checkout et paiements récurrents ; l'activation effective et les limites doivent être testées sur le compte retenu. Ne pas généraliser ces capacités à d'autres prestataires. [Documentation TWINT](https://docs.stripe.com/payments/twint).

Ne stocker ni numéro de carte ni cryptogramme. Les prélèvements récurrents sont déclenchés par le prestataire, jamais par une tâche PHP recréant des paiements chaque mois. Prévoir le renouvellement, l'échec, les relances prestataire et la résiliation ; ne proposer une fréquence que si toute sa gestion fonctionne.

### 8.2 Flux fiable du don

1. Le formulaire envoie montant, devise, fréquence, projet et coordonnées au serveur avec protection CSRF.
2. Le serveur valide les données et la disponibilité du projet, puis crée un don `pending` et une tentative avec clé d'idempotence persistée.
3. Il crée une session chez le prestataire, en transmettant uniquement les références nécessaires. En cas de délai dépassé, réessayer avec la même clé plutôt que créer un second débit.
4. Le navigateur est redirigé vers le domaine de paiement autorisé.
5. Le webhook HTTPS vérifie la signature sur le corps brut, l'environnement et la validité temporelle selon le SDK. Il contrôle référence, montant, devise et état payé.
6. Dans une transaction courte, le serveur déduplique l'événement et l'objet paiement, enregistre le paiement confirmé et crée une entrée d'envoi différé unique. L'accusé HTTP positif intervient après enregistrement durable. Une erreur transitoire renvoie une erreur pour permettre la reprise.
7. Le retour navigateur affiche l'état connu côté serveur. Si le webhook tarde, une consultation serveur du prestataire peut alimenter le même service idempotent ; sinon afficher « vérification en cours ». Aucun paramètre `success=true` ne valide un don.
8. Le traitement différé génère le justificatif et envoie la confirmation. Un e-mail en échec n'annule pas le paiement et peut être renvoyé sans créer un nouveau don.

Les événements peuvent arriver plusieurs fois ou dans le désordre. Leur déduplication, la vérification de signature et le traitement des reprises sont nécessaires dans l'intégration ; s'appuyer sur le SDK et la documentation du prestataire. [Webhooks Stripe](https://docs.stripe.com/webhooks).

Pour limiter le coût du webhook sur mutualisé, réserver son chemin immédiat aux contrôles et écritures courtes. Tout enrichissement lourd est persisté puis repris par lot. Si la charge dépasse ce fonctionnement, passer à un worker adapté ; le modèle `payment_events` permet la transition.

### 8.3 Cas particuliers à couvrir

- Double clic, retour rafraîchi, session expirée : retrouver la tentative, proposer une reprise et éviter les doublons.
- Paiement asynchrone : `pending` reste distinct de `paid` ; attendre la preuve finale.
- Événement ancien après remboursement : aucune régression de l'état vers « payé sans remboursement ».
- Échéance récurrente : une référence de facture/échéance externe unique produit au maximum un don confirmé.
- Résiliation : montrer la date d'effet connue ; ne pas affirmer qu'une échéance déjà en cours a été annulée sans confirmation.
- Remboursement : rôle finance, réauthentification, montant contrôlé, demande idempotente puis confirmation prestataire ; conserver l'historique et ajuster les totaux.
- Versements : rapprocher les montants bruts, frais, remboursements et versements bancaires ; une liste de dons ne remplace pas la comptabilité de l'association.
- Don bancaire externe, si retenu : saisie ou import contrôlé après rapprochement, référence unique et justificatif ; aucune validation sur simple capture d'écran du donateur.
- Reçu : distinguer confirmation de paiement et éventuelle attestation fiscale. N'émettre cette dernière que si le statut de l'association et le modèle ont été validés par la personne compétente.

## 9. Routes et contrats principaux

Les routes publiques HTML rendent les contenus publiés. Les écritures utilisent validation serveur, limitation de débit et CSRF, à l'exception ciblée du webhook authentifié par signature et de la route technique avec ses propres contrôles.

| Méthode / route proposée | Contrat |
| --- | --- |
| `POST /dons/checkout` | Valide le don, crée/reprend une tentative et redirige vers le prestataire ; erreurs rattachées aux champs |
| `GET /don/retour` | Consulte l'état via session et référence opaque ; ne révèle pas les coordonnées d'un autre donateur |
| `GET /dons/{reference}/statut` | État minimal pour la session autorisée, sans cache et avec limitation de fréquence |
| `POST /webhooks/paiement` | Corps brut signé, événements dédupliqués et reprise sûre |
| `POST /contact` | Enregistre une demande, réponse générique et notification différée |
| `POST /newsletter/inscription` | Crée ou renouvelle une demande de confirmation sans divulguer l'existence d'un abonné |
| `GET /newsletter/confirmer/{token}` puis `POST` | Page de confirmation puis action explicite ; jeton expirant à usage unique |
| `GET /newsletter/desinscription/{token}` puis `POST` | Désinscription simple et persistante ; compatibilité one-click via endpoint prestataire dédié si utilisé |
| `POST /abonnement/acces` | Envoie un lien temporaire après contrôle de débit ; réponse neutre |
| `POST /admin/dons/{id}/remboursements` | Finance uniquement, contrôle du montant, idempotence et audit |
| `GET /admin/exports/{id}` | Téléchargement privé autorisé, fichier expirant et journalisé |
| `GET /internal/jobs/run` | Appel webcron authentifié, lot fixe et borné ; jamais de commande libre |
| `GET /up` | Santé publique minimale ; diagnostics détaillés réservés à l'exploitation |

Ne pas placer de données personnelles dans les URL, les journaux d'accès ou les métadonnées de partage. Le formulaire contact échappe le contenu à l'affichage ; l'adresse d'envoi SMTP est fixe et validée, avec un Reply-To contrôlé.

## 10. Sécurité, données et e-mails

- Utiliser l'authentification du framework, hachage robuste, MFA admin, rotation de session après connexion et cookies Secure/HttpOnly/SameSite adaptés au retour du prestataire.
- Protéger les écritures par CSRF, les requêtes SQL par paramètres/ORM et les contenus par échappement ; assainir le texte riche selon une liste de balises autorisées.
- Limiter les essais de connexion, formulaires et créations de paiements. Anti-spam progressif : champ piège et contrôle temporel, puis challenge si nécessaire et accessible.
- Images : contrôler signature MIME, dimensions, poids, réencoder, supprimer les métadonnées sensibles. PDF : stockage isolé, analyse avant publication et téléchargement avec en-têtes adaptés. Refuser les SVG non contrôlés envoyés par les éditeurs.
- Secrets en configuration serveur privée, jamais dans Git ou l'administration éditoriale. Séparer les clés test/live, faire tourner les secrets et conserver la clé applicative de façon sécurisée pour la restauration.
- HTTPS, en-têtes de sécurité et politique CSP ajustée aux seuls domaines nécessaires ; vérifier la compatibilité avec le parcours de paiement.
- Journaliser les actions sensibles sans mot de passe, jeton ni coordonnées complètes. Exports CSV protégés contre l'interprétation de formules, accès limité et expiration automatique.
- Documenter finalité, destinataires, hébergement, durée et suppression pour chaque famille de données. Faire valider le cadre applicable à l'entité et au public visé, notamment LPD suisse et éventuelle application du RGPD ; ce document ne tranche pas leur applicabilité.
- Distinguer consentement marketing et communication transactionnelle. Une demande d'effacement doit examiner les obligations de conservation avant suppression de données financières.
- Limiter les services externes et partir sans traqueurs publicitaires. Prévoir une gestion du consentement uniquement en fonction des outils finalement déployés et des exigences applicables.

E-mails à préparer : confirmation de don, paiement encore en attente si utile, remboursement confirmé, accès à la gestion d'abonnement, confirmation newsletter, réponse contact et invitations/réinitialisation admin. Versions HTML et texte, logo lisible, aucune donnée bancaire sensible. Configuration DNS SPF/DKIM/DMARC à vérifier avec le service d'envoi retenu. Prévoir suivi des erreurs et rebonds ; les campagnes de masse seront confiées à un outil prévu pour cela, avec désinscriptions synchronisées.

## 11. Déploiement et exploitation Infomaniak

### 11.1 Vérifications préalables dans le Manager

Confirmer : offre mutualisée ou Cloud, version PHP web et CLI, extensions Laravel et traitement d'images, moteur/version SQL, SSH/Composer, quotas mémoire et temps d'exécution, accès HTTPS sortant au prestataire, SMTP, racine `public`, certificats et webcron. Vérifier la politique réelle de sauvegarde/restauration et les possibilités de déploiement par répertoire versionné. Aucun de ces réglages de compte n'a été contrôlé dans cette analyse.

Environnements distincts : local, préproduction protégée et production. Bases, secrets et clés de paiement séparés ; données de test fictives ou anonymisées. Pour les tests webhooks en préproduction, laisser la route signée accessible au prestataire tout en protégeant les pages et l'administration.

### 11.2 Procédure de livraison future

1. Exécuter les tests, audit des dépendances et compilation des assets ; produire un artefact versionné avec dépendances de production.
2. Sauvegarder base et fichiers privés ; conserver une version déployée précédente et vérifier l'accès aux moyens de restauration.
3. Livrer dans un répertoire de release, configurer les secrets privés et permissions minimales sur stockage/cache.
4. Vérifier les prérequis PHP/Composer de l'artefact ; appliquer des migrations compatibles avec la version précédente quand possible.
5. Diriger le site vers `public`, activer caches adaptés, mode production et débogage désactivé ; contrôler qu'aucun `.env`, dépôt Git ou fichier privé n'est accessible.
6. Configurer HTTPS, SMTP, webhook, tâches planifiées et monitoring ; effectuer les vérifications fonctionnelles.
7. Basculer vers la release si la méthode atomique est supportée. Sinon prévoir une courte fenêtre de maintenance et une procédure de bascule documentée.
8. Conserver la release précédente. En cas de défaut, revenir au code compatible avec le schéma ; ne pas restaurer aveuglément une ancienne base qui supprimerait des dons récents.

Un endpoint de réception des paiements doit rester disponible pendant les déploiements, ou permettre les reprises du prestataire. Après incident ou restauration, rapprocher systématiquement les paiements externes et la base locale.

### 11.3 Sauvegardes, alertes et maintenance

Définir une sauvegarde automatisée de la base, des médias privés et de la configuration nécessaire à la restauration ; copie indépendante chiffrée avec accès distinct. Les sauvegardes proposées par l'hébergeur ne dispensent pas d'un test réel de restauration.

Cibles initiales à valider selon budget : perte maximale de 24 h pour les contenus et restauration sous 4 h. Pour les paiements, l'objectif est de reconstruire tous les mouvements depuis le prestataire après restauration ; tester les exports/API, la reprise des événements et les liens avec les dons locaux. Si cette reconstruction n'est pas fiable ou assez rapide, renforcer les sauvegardes avant lancement.

Alertes : site indisponible, taux d'erreur webhook, paiements en attente anormalement anciens, tâches arrêtées, e-mails en échec, espace disque et sauvegarde absente. Notification à un responsable nommé ; procédure écrite pour panne paiement, compromission de clé et restauration. Contrôle régulier des dépendances et mises à jour de sécurité, revue mensuelle des accès, exercice trimestriel de restauration proposé.

## 12. Plan de réalisation et dépendances

Charges indicatives en jours de travail, pour une personne expérimentée et des contenus disponibles. Ce ne sont pas des engagements de délai ; les validations, contrats et créations de contenu peuvent prolonger le calendrier.

| Lot | Travail et livrable | Dépendance / critère de sortie | Charge indicative |
| --- | --- | --- | --- |
| 0 — Cadrage | Informations de l'association, inventaire contenus, compte hébergeur et décision prestataire | Identité légale, moyens de paiement et contraintes techniques confirmés | 1–2 j |
| 1 — Identité et UX | Déclinaisons logo, palette, composants et maquettes accueil/projet/don sur mobile et ordinateur | Direction graphique et parcours validés | 3–5 j |
| 2 — Fondations | Laravel, base, migrations, accès admin/MFA et préproduction | Déploiement minimal, permissions et stockage privé vérifiés | 2–4 j |
| 3 — Site et édition | Gabarits publics, projets, actualités, FAQ, médias et transparence | Publication d'un projet et d'un article sans modification de code | 4–6 j |
| 4 — Don ponctuel | Checkout, webhooks, rapprochement, remboursements et confirmations | Scénarios financiers critiques passés en sandbox | 4–6 j |
| 5 — Récurrence et communication | Mensuel, portail prestataire, newsletter, contact, tâches et e-mails | Échéance, échec, résiliation et désinscription vérifiés | 3–5 j |
| 6 — Recette et lancement | Contenus réels, accessibilité, performance, sauvegardes, formation et mise en production | Checklist de lancement entièrement satisfaite | 3–5 j |

Total indicatif : **20–33 jours de réalisation**, hors attente externe. L'intégration d'un autre prestataire, de plusieurs langues ou d'un import historique important entraîne une réestimation.

Périmètre de lancement : site public français, projets/actualités/transparence, don ponctuel fiable, administration, contact, newsletter si son service est prêt, exploitation et sécurité. Don mensuel au lancement seulement si le lot complet est validé ; sinon retirer son option et le livrer ensuite. La planification du backend inclut la récurrence même si son activation est décalée.

Extensions après stabilisation : don annuel, allemand/anglais, recherche, QR-facture si nécessaire, portail donateur propre au site, intégration comptable, bénévolat ou partenariats si ces besoins sont confirmés. Ne pas développer ces extensions sur la seule base d'un usage possible.

## 13. Recette et définition de « prêt à lancer »

| Domaine | Scénarios nécessaires |
| --- | --- |
| Don | Montants prédéfinis/libres, champ effacé, négatif, décimales, limites, changement de projet, double clic |
| Paiement | Succès, refus, annulation, expiration, réseau coupé, délai de réponse, retour avant webhook et absence de retour |
| Webhooks | Signature invalide, doublon, événements différents pour le même paiement, désordre, concurrence et reprise après panne |
| Récurrence | Première échéance, renouvellement, échec, paiement récupéré, résiliation et absence de double comptage |
| Finance | Remboursement partiel/total, requêtes concurrentes, litige, frais, versements et rapprochement après restauration |
| Administration | Droits de chaque rôle, accès direct à une URL interdite, brouillon non public, invitation/MFA/révocation |
| Médias et données | Fichier malveillant, accès à un reçu d'autrui, export expiré, suppression conforme à la politique validée |
| Formulaires | Erreurs lisibles, anti-spam, newsletter non précochée, confirmation et désinscription résistante aux reprises |
| Interface | 320/375/768/1280 px, clavier, lecteur d'écran, zoom, focus, contraste, textes longs et mouvement réduit |
| Qualité web | Images optimisées, liens réels, titres/meta/404, responsive, budgets de performance mesurés |
| Exploitation | SMTP indisponible, webcron manqué, lot interrompu, sauvegarde/restauration, rollback et alertes reçues |

Utiliser des tests unitaires pour les montants/transitions, des tests HTTP et base pour les droits/idempotences, puis des tests d'intégration prestataire en sandbox. Compléter par une recette navigateur sur les parcours essentiels et des contrôles manuels d'accessibilité. Les contrôles automatisés ne suffisent pas à déclarer une conformité complète.

Checklist finale :

- [ ] Logo validé, photos autorisées et textes approuvés par Amanah.
- [ ] Aucun chiffre, partenaire, témoignage, téléphone ou article fictif publié.
- [ ] Aucun bouton simulé, `href="#"` sans fonction ni option de paiement inactive.
- [ ] Coordonnées et informations légales réelles ; politique de don et traitement des données validés.
- [ ] Identifiants live activés par le titulaire du compte ; un petit paiement réel autorisé et son remboursement rapprochés avant ouverture générale.
- [ ] Confirmation serveur, remboursements et récurrence activée testés de bout en bout.
- [ ] Administration et fichiers privés protégés ; sauvegarde restaurée avec succès.
- [ ] Tâches planifiées et e-mails supervisés, responsables d'exploitation identifiés.
- [ ] Guide d'administration et procédure d'incident remis à l'association.

## 14. Décisions encore attendues avant l'implémentation concernée

1. Identité officielle de l'entité, domiciliation, responsables, coordonnées, domaine et éventuel statut fiscal reconnu.
2. Mission exacte, pays et actions réels : aide d'urgence uniquement ou aide humanitaire plus large ?
3. Ressources disponibles : logo vectoriel éventuel, photographies autorisées, projets, rapports et textes.
4. Offre Infomaniak, accès techniques, volumes attendus et objectif de délai pour les e-mails.
5. Compte prestataire au nom de l'entité, moyens de paiement, fréquences et politique d'affectation/remboursement.
6. Personnes qui publieront, traiteront les demandes et suivront la finance ; besoin de formation.
7. Outil newsletter existant, langues ultérieures et éventuels dons/donateurs à importer.

Ces décisions ne bloquent pas la rédaction du plan ni les premières maquettes. Elles conditionnent les intégrations, les contenus factuels et l'ouverture des dons.

## 15. Sources et limites

Sources locales : `Amanah.html` lu intégralement et `logo.jpeg` inspecté visuellement. Les constats de code sont distincts des propositions de conception et des hypothèses métier.

Documentation officielle consultée le 13 septembre 2026 : [Laravel — déploiement](https://laravel.com/framework/docs/deployment), [Infomaniak — Laravel](https://www.infomaniak.com/en/support/faq/2119/install-laravel-on-an-infomaniak-hosting-account), [Infomaniak — tâches planifiées](https://www.infomaniak.com/fr/support/faq/2161/planifier-des-taches-sur-hebergement-web), [Stripe — webhooks](https://docs.stripe.com/webhooks), [Stripe — TWINT](https://docs.stripe.com/payments/twint). Revérifier ces prérequis lors de l'implémentation ; la documentation générale ne remplace pas les capacités du compte réellement souscrit.

Les outils Ruflo/ToolSearch demandés par les instructions du workspace n'étaient pas exposés dans cette session après recherche du catalogue disponible. L'analyse et la planification ont donc été réalisées directement, sans invocation Ruflo ni modification de son état.
