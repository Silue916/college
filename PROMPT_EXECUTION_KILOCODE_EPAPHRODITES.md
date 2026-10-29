# PROMPT D’EXÉCUTION KILOCODE — GESTION SCOLAIRE AVEC EPAPHRODITES

> **Utilisation :** place ce fichier à la racine du dépôt, puis demande à KiloCode : « Lis intégralement `PROMPT_EXECUTION_KILOCODE_EPAPHRODITES.md` et commence uniquement par l’audit initial (Tâche 0), sans modifier de fichier. »

## 1. Mission permanente
Tu es l’agent de développement chargé de construire une application web de gestion scolaire destinée à **un seul établissement secondaire ivoirien**, de la 6e à la Terminale. Tu travailles dans le dépôt réellement ouvert dans KiloCode, en utilisant **le framework Epaphrodites déjà installé ou à installer selon l’état constaté du dépôt**.

Le projet doit couvrir, pour la **phase 1 uniquement**, le socle technique, le référentiel scolaire, les rôles et la sécurité, les pages publiques, la préinscription, son traitement, l’inscription définitive, les frais et paiements saisis manuellement, les enseignants, les notes, les moyennes, les bulletins, les absences, les espaces élève et parent, le tableau de bord de direction et le journal d’audit.

**Ne développe pas la phase 2 dans ce cycle.** Elle comprend notamment les décisions de fin d’année, l’orientation, le suivi BEPC/BAC, les certificats, les départs/transferts, l’archivage, le paiement Mobile Money en ligne, les SMS, l’emploi du temps et la messagerie. Préserve une architecture qui pourra accueillir ces fonctionnalités plus tard, sans les implémenter prématurément.

## 2. Références obligatoires et ordre de confiance
Avant tout travail, recherche et lis les documents présents dans le dépôt :
- `PROMPT_EXECUTION_KILOCODE_EPAPHRODITES.md` (ce fichier) ;
- `site_web.txt` (cahier des charges d’origine, s’il est présent) ;
- `epaphrodites_documentation_ia.md` (base de connaissances du framework, si présente) ;
- le `README`, `composer.json`, les configurations et le code source réel d’Epaphrodites ;
- les exemples fonctionnels du même dépôt : routes, contrôleurs, vues Twig, droits utilisateurs, requêtes, migrations, seeders, envoi de mails et exports Excel.

En cas de contradiction, ne choisis pas arbitrairement : signale-la, inspecte l’implémentation réelle et détermine ce qui est compatible avec la version installée. Le code et les signatures présents dans le dépôt priment sur tes connaissances générales. La documentation locale est une aide, pas une autorisation d’inventer une API.

## 3. Stack et contraintes fonctionnelles
- PHP 8.2 ; Epaphrodites via Composer ; gabarits Twig ; HTML/CSS ; JavaScript limité au nécessaire ; Bootstrap 5 ; dompdf pour les PDF.
- MySQL 8 ou MariaDB 10.6+ en développement/production ; SQLite pour les tests si la compatibilité avec la couche de données Epaphrodites est confirmée.
- PHPUnit 10, PHPStan niveau 5 et GitHub Actions à chaque Pull Request, sous réserve de compatibilité vérifiée avec la version réelle du projet.
- Interface en français, mobile-first, rapide et légère sur connexion 3G/4G.
- Rôles : `admin`, `scolarite`, `comptabilite`, `enseignant`, `eleve`, `parent`.
- Niveaux : 6E, 5E, 4E, 3E, 2NDE, 1ERE, TLE. Séries modifiables en base : 2nde A/C ; 1re et Tle A1/A2/C/D.
- Année et type de période configurables (trimestres par défaut, semestres possibles). Notes sur 20 ; coefficients par niveau, série et matière.
- L’élève conserve son matricule MENA s’il en possède un ; l’école attribue un matricule interne unique. Le statut affecté/non affecté est conservé, car il influence les frais.
- Pendant la phase 1, les paiements sont enregistrés par la comptabilité (espèces, chèque, Mobile Money avec référence). Aucun paiement en ligne n’est à intégrer.

## 4. Règles impératives avant chaque modification
Ces règles s’appliquent à **chaque tâche et à chaque reprise après interruption** :
1. **Inspecte avant d’éditer.** Vérifie le dépôt, l’arborescence et les fichiers concernés ; lis les exemples analogues et le code de la version installée. Ne crée ni ne modifie aucun fichier tant que cette inspection n’est pas terminée.
2. **Protège le travail existant.** Commence par `git status` et repère les changements non commités. Ne les écrase pas, ne les annule pas et ne les reformate pas sans rapport avec la tâche.
3. **Ne suppose aucune architecture.** Ne crée pas de répertoires « standards » d’un autre framework. Ne transpose pas Laravel, Symfony, Django, Flask, Express ou Doctrine. Réutilise les mécanismes Epaphrodites qui existent réellement.
4. **Vérifie chaque commande et API.** Avant d’utiliser une commande CLI, vérifie qu’elle existe dans la version présente, ses arguments, ses effets et sa syntaxe via l’aide du projet et/ou le code. Une commande exemple n’est pas automatiquement valide dans ce dépôt.
5. **Planifie le diff.** Avant le code, résume l’objectif, les fichiers probablement concernés, les mécanismes à réutiliser, les risques, les tests et les critères d’acceptation. Ensuite, implémente seulement le périmètre de la tâche active.
6. **Modifie au minimum.** Pas de refonte générale, de renommage massif, de dépendance ajoutée ou de fonctionnalité hors périmètre sans justification démontrée et accord humain si l’impact est important.
7. **Teste réellement.** Exécute les tests, analyse statique et vérifications pertinentes disponibles. Ne déclare jamais un test réussi s’il n’a pas été exécuté. Distingue clairement test réussi, échec, non exécuté et bloqué par l’environnement.
8. **Ne contourne pas les échecs.** Ne désactive pas un test, une validation, CSRF, les autorisations ou PHPStan pour obtenir artificiellement une CI verte. Si la stack proposée est incompatible, documente les preuves et arrête-toi pour proposer une correction à valider.
9. **Opérations à risque.** Ne supprime pas de base/table/données, ne réinitialise pas une base, ne remplace pas une configuration de production et ne lance pas d’envoi réel de mails sans autorisation explicite. Travaille avec des données de test non sensibles.
10. **Sécurité des mineurs.** Les données réelles des élèves, photos, notes, absences, sanctions et coordonnées doivent être protégées. Les permissions doivent être vérifiées côté serveur, y compris lorsqu’un identifiant est modifié dans l’URL.
11. **Langue.** Les interfaces et messages destinés aux utilisateurs sont rédigés en français. Le code suit les conventions de nommage constatées dans le projet.
12. **Pas de déploiement automatique.** Ne déploie pas, ne réalise aucun achat et ne configure pas de service payant. La mise en production est une étape distincte avec validation humaine.

## 5. Protocole strict d’une tâche
Pour chaque tâche, suis ce cycle sans sauter d’étape :

### A. Inspection (aucune modification)
- Lire l’état Git et la structure pertinente.
- Lire la documentation locale et les fichiers du framework concernés.
- Repérer les conventions exactes et les composants réutilisables.
- Vérifier les dépendances de la tâche et les tâches préalables déjà validées.

### B. Diagnostic et plan
Présente en français : ce qui existe déjà, ce qui manque, les fichiers prévus, les commandes envisagées, les tests et les risques. Si une exigence est incompatible ou ambiguë, explique pourquoi et pose une question précise avant une décision irréversible. Ne demande pas de précisions dont la réponse figure déjà dans les fichiers.

### C. Implémentation limitée
Une fois l’inspection et le plan terminés, modifie uniquement ce qui est nécessaire à la tâche active. N’entame pas la tâche suivante, même si elle paraît facile ou dépendante.

### D. Tests et contrôle qualité
Après les changements :
- vérifie les erreurs de syntaxe PHP pertinentes ;
- exécute les tests ciblés, puis la suite pertinente ;
- lance `vendor/bin/phpunit` et `vendor/bin/phpstan analyse` lorsqu’ils sont installés et configurés ;
- vérifie les migrations/seeders sur les moteurs demandés lorsqu’un environnement test est disponible ;
- vérifie la sécurité, les permissions, les validations, les erreurs attendues et les effets de bord ;
- examine le diff pour exclure les changements hors sujet et les secrets ;
- n’affirme pas la compatibilité MySQL/SQLite sans test ou preuve suffisante.

Si une commande de test n’existe pas ou si l’environnement ne permet pas de l’exécuter, indique la raison et ne la présente pas comme réussie. Si un critère d’acceptation échoue, corrige dans le périmètre de la tâche ou expose le blocage ; ne passe pas à la suite.

### E. Rapport de fin de tâche et verrou humain
Termine chaque tâche avec :
1. Résultat et périmètre réalisé ;
2. fichiers créés/modifiés ;
3. commandes exécutées ;
4. tests : réussis / échoués / non exécutés, avec résultats ;
5. tableau des critères d’acceptation : satisfait / non satisfait / bloqué ;
6. risques ou décisions restantes ;
7. hash/branche/diff si disponible, sans prétendre avoir créé un commit si ce n’est pas le cas.

**Arrête-toi ensuite et attends ma validation explicite.** La tâche ne peut être marquée `VALIDÉE` que si tous ses critères sont satisfaits, les vérifications exigées sont réussies et je confirme dans le chat. La CI GitHub peut rester en attente si elle nécessite une Pull Request ; indique alors précisément ce qui reste à valider à distance.

Commandes de conversation :
- `DÉMARRER AUDIT` : exécuter uniquement la Tâche 0, sans modifier de fichier.
- `AUTORISER TÂCHE N` : autoriser l’implémentation de la tâche N après inspection et planification.
- `VALIDER TÂCHE N` : si tous les critères sont satisfaits, marquer la tâche comme validée et proposer la suivante ; ne pas commencer la suivante sans autorisation explicite.
- `CORRIGER TÂCHE N` : rester sur cette tâche, inspecter de nouveau puis corriger uniquement ce qui manque.
- `ARRÊTER` : cesser les modifications et présenter l’état actuel.

Ne considère pas un simple compte rendu comme une approbation. N’interprète pas un « OK » ambigu comme une validation de tâche s’il ne précise pas clairement quelle tâche est validée.

## 6. Tâche 0 — audit initial obligatoire (aucune modification)
Avant la Tâche 1, inspecte le dépôt et rends un diagnostic sans modifier de fichier :
- confirmer le répertoire de travail et si le projet Epaphrodites existe déjà ;
- lire `composer.json`, le README, la configuration et les scripts réellement disponibles ;
- identifier la version PHP et Epaphrodites visibles, les extensions disponibles si tu peux les vérifier ;
- cartographier les dossiers de vues Twig, contrôleurs, routes/cartes, droits, requêtes SQL/NoSQL, migrations, seeders, mails et exports ;
- trouver et lire `epaphrodites_documentation_ia.md` et `site_web.txt` s’ils sont présents ;
- examiner l’état Git, les branches et les changements existants ;
- évaluer la compatibilité de PHPUnit 10, PHPStan 5, SQLite et Bootstrap 5 sans installer ou modifier de dépendance à cette étape ;
- proposer l’ordre d’exécution qui respecte les dépendances, les risques et les validations humaines ;
- relever les écarts entre cahier des charges, documentation locale et code source.

**Sortie obligatoire :** diagnostic, arborescence pertinente, conventions Epaphrodites confirmées, commandes réellement vérifiées, risques et plan de la Tâche 1. Puis arrête-toi. Pas de création de fichier pendant l’audit. Si le dépôt est déjà initialisé, ne relance pas `composer create-project` dans ce répertoire ; propose l’action appropriée après inspection.

## 7. Registre des tâches de la phase 1
Exécute les tâches dans l’ordre numéroté ci-dessous, une seule à la fois. Les dépendances sont celles du cahier des charges. Une tâche n’est pas autorisée tant que ses dépendances ne sont pas validées. Même si plusieurs tâches sont techniquement prêtes, n’en exécute qu’une par cycle de validation.

## Tâche 1 — Initialiser le projet Epaphrodite, les tests et la CI

**Dépendances :** aucune. Cette tâche suit l’audit initial.

### Travail demandé
Créer le projet avec « composer create-project epaphrodites/epaphrodites ». Garder l'arborescence générée. Configurer deux connexions : MySQL pour le développement et SQLite pour les tests, avec un fichier d'exemple (.env.example ou fichier de configuration du framework) qui ne contient aucun secret. Ajouter PHPUnit 10 et PHPStan en dépendances de développement, avec un fichier phpunit.xml et un dossier tests/ séparé en Unit et Feature. Écrire un test simple qui vérifie que la page d'accueil répond avec le code 200. Créer le workflow .github/workflows/ci.yml : PHP 8.2, composer install, phpstan, phpunit. Rédiger un README en français : prérequis (PHP 8.2 et les extensions pdo_mysql, pdo_sqlite, gd, intl, mbstring, zip), installation, lancement local, lancement des tests, conventions de branches et de commits. Ajouter un .gitignore qui exclut vendor/, les fichiers d'environnement, les fichiers téléversés et les fichiers de cache.

### Critères d’acceptation obligatoires
- [ ] Un clone neuf suivi de « composer install » et de la commande de lancement du README affiche la page d'accueil en local.
- [ ] « vendor/bin/phpunit » passe, avec au moins 1 test vert.
- [ ] « vendor/bin/phpstan analyse » ne remonte aucune erreur au niveau 5.
- [ ] Le workflow GitHub Actions passe sur la Pull Request.
- [ ] Aucun mot de passe ni clé n'est commité. Seul le fichier d'exemple de configuration est versionné.

### Contrôles spécifiques obligatoires
- Ne pas remplacer l’arborescence produite par Epaphrodites.
- Ne pas stocker de secret dans Git ; le fichier de configuration d’exemple ne contient que des valeurs fictives.
- Vérifier que l’ajout des outils de test ne casse pas les dépendances existantes.

### Conditions de clôture
- Tous les critères ci-dessus sont satisfaits ou un blocage précis est documenté ; aucun échec n’est dissimulé.
- Les tests pertinents sont exécutés et le diff a été inspecté.
- Le rapport de fin de tâche est fourni et KiloCode attend `VALIDER TÂCHE 1` avant de proposer l’implémentation suivante.

## Tâche 2 — Créer le référentiel scolaire (années, niveaux, séries, classes, matières)

**Dépendances :** Tâche 1

### Travail demandé
Créer les migrations Epaphrodite des tables suivantes. annees_scolaires : id, libelle au format « 2026-2027 », date_debut, date_fin, type_periode (trimestre ou semestre), est_active (une seule année active à la fois). periodes : id, annee_id, numero, date_debut, date_fin, est_cloturee. niveaux : id, code (6E, 5E, 4E, 3E, 2NDE, 1ERE, TLE), cycle (1 ou 2), ordre. series : id, code (A, C, A1, A2, D), libelle. classes : id, annee_id, niveau_id, serie_id (vide au premier cycle), nom (par exemple « 4e 2 » ou « Tle D1 »), effectif_max. matieres : id, code, libelle. coefficients : niveau_id, serie_id (peut être vide), matiere_id, coefficient (entier de 1 à 10, clé unique sur ces trois colonnes). Créer les seeders : les 7 niveaux, les séries, les matières courantes (Français, Mathématiques, Anglais, Histoire-Géographie, SVT, Physique-Chimie, EDHC, EPS, Philosophie, Espagnol, Allemand, Arts plastiques, Musique) et une année 2026-2027 avec 3 trimestres. Écrire une classe de service ReferentielService avec les méthodes getAnneeActive(), getPeriodeCourante() et getCoefficient(niveau, serie, matiere).

### Critères d’acceptation obligatoires
- [ ] Les migrations et les seeders s'exécutent sans erreur sur MySQL et sur SQLite.
- [ ] Il est impossible d'avoir deux années actives. Un test vérifie qu'activer une année désactive la précédente.
- [ ] Une classe de 2nde, 1re ou Tle sans série est refusée. Une classe de 6e à 3e avec une série est refusée. Ces deux cas sont testés.
- [ ] getCoefficient renvoie le bon coefficient, et lève une exception explicite si aucun coefficient n'est défini. Les deux cas sont testés.

### Contrôles spécifiques obligatoires
- Garantir l’unicité de l’année active au niveau applicatif et, si compatible, au niveau base de données.
- Respecter les différences SQL entre MySQL et SQLite sans masquer les erreurs.

### Conditions de clôture
- Tous les critères ci-dessus sont satisfaits ou un blocage précis est documenté ; aucun échec n’est dissimulé.
- Les tests pertinents sont exécutés et le diff a été inspecté.
- Le rapport de fin de tâche est fourni et KiloCode attend `VALIDER TÂCHE 2` avant de proposer l’implémentation suivante.

## Tâche 3 — Mettre en place l'authentification et les rôles

**Dépendances :** Tâche 1

### Travail demandé
Utiliser le système de droits utilisateurs d'Epaphrodite pour définir 6 rôles : admin, scolarite, comptabilite, enseignant, eleve, parent. Créer la page de connexion. L'identifiant est l'adresse e-mail ou le matricule interne. Le mot de passe est haché avec password_hash (bcrypt ou argon2). Ajouter un jeton CSRF sur tous les formulaires. Bloquer le compte 15 minutes après 5 échecs de connexion d'affilée. Régénérer l'identifiant de session à la connexion et déconnecter après 30 minutes d'inactivité. Ajouter la réinitialisation du mot de passe par e-mail, avec un lien à usage unique valable 1 heure. Imposer un changement de mot de passe à la première connexion. Mot de passe : 8 caractères minimum, au moins une lettre et un chiffre. Écrire un middleware ou garde de route qui renvoie une erreur 403 quand le rôle ne convient pas, et redirige vers /connexion si l'utilisateur n'est pas connecté. Créer un seeder avec un compte administrateur dont le mot de passe vient de la configuration, jamais écrit en dur.

### Critères d’acceptation obligatoires
- [ ] Un élève qui ouvre une URL d'administration reçoit une erreur 403 (testé).
- [ ] Après 5 mots de passe faux, la 6e tentative est refusée, même avec le bon mot de passe, pendant 15 minutes (testé).
- [ ] Un formulaire envoyé sans jeton CSRF valide est rejeté (testé).
- [ ] Un lien de réinitialisation déjà utilisé ou expiré affiche un message d'erreur et ne change pas le mot de passe (testé).
- [ ] Aucun mot de passe n'est stocké en clair. Le test vérifie le format du hachage en base.

### Contrôles spécifiques obligatoires
- Réutiliser le système de droits et de sessions d’Epaphrodites après inspection.
- Tester les accès anonymes, les rôles insuffisants, CSRF, le verrouillage et les liens de réinitialisation expirés/réutilisés.

### Conditions de clôture
- Tous les critères ci-dessus sont satisfaits ou un blocage précis est documenté ; aucun échec n’est dissimulé.
- Les tests pertinents sont exécutés et le diff a été inspecté.
- Le rapport de fin de tâche est fourni et KiloCode attend `VALIDER TÂCHE 3` avant de proposer l’implémentation suivante.

## Tâche 4 — Créer la mise en page et les pages publiques du site

**Dépendances :** Tâche 1, Tâche 3

### Travail demandé
Créer un gabarit Twig de base (layout) avec Bootstrap 5 : en-tête avec le logo et le nom de l'école (modifiables en configuration), menu adapté au mobile, pied de page avec l'adresse, le téléphone au format +225 et les réseaux sociaux. Créer les pages publiques : Accueil (présentation et bouton « Préinscription en ligne »), L'établissement, Conditions d'admission et pièces à fournir, Frais de scolarité indicatifs, Actualités (liste et détail, gérés par l'administrateur avec un CRUD simple : titre, contenu, image facultative, date de publication), et Contact (formulaire nom, téléphone, e-mail, message, envoyé par e-mail à l'école, avec un honeypot anti-spam et une limite de 3 envois par heure et par adresse IP). Créer des pages 404 et 500 personnalisées. Toutes les données sont échappées par Twig, sans aucun filtre raw sur des données saisies par les utilisateurs.

### Critères d’acceptation obligatoires
- [ ] Toutes les pages publiques répondent avec le code 200 et un test de fonctionnalité couvre chacune d'elles.
- [ ] Le site s'affiche correctement sur un écran de 360 px de large (le menu devient un bouton « burger »).
- [ ] Un formulaire de contact avec le honeypot rempli n'envoie aucun e-mail (testé).
- [ ] Le 4e envoi du formulaire de contact dans la même heure depuis la même IP est refusé (testé).
- [ ] Une actualité dont la date de publication est dans le futur n'apparaît pas sur le site public (testé).

### Contrôles spécifiques obligatoires
- Échapper les données utilisateur dans Twig et ne pas utiliser `raw` sur du contenu non fiable.
- Limiter JavaScript et les ressources lourdes ; tester l’affichage étroit mobile.

### Conditions de clôture
- Tous les critères ci-dessus sont satisfaits ou un blocage précis est documenté ; aucun échec n’est dissimulé.
- Les tests pertinents sont exécutés et le diff a été inspecté.
- Le rapport de fin de tâche est fourni et KiloCode attend `VALIDER TÂCHE 4` avant de proposer l’implémentation suivante.

## Tâche 5 — Créer le formulaire de préinscription en ligne

**Dépendances :** Tâche 2, Tâche 4

### Travail demandé
Créer un formulaire public en 3 étapes. Étape 1, l'élève : nom, prénoms, date et lieu de naissance, sexe, nationalité, matricule MENA (facultatif), statut affecté ou non affecté, établissement d'origine, niveau demandé, série demandée (obligatoire si le niveau est 2nde, 1re ou Tle). Étape 2, le responsable légal : nom, lien de parenté, téléphone, e-mail facultatif, profession, commune de résidence. Étape 3, les pièces jointes : extrait d'acte de naissance, dernier bulletin, photo d'identité. Créer la table dossiers_preinscription avec un statut (soumis, incomplet, accepte, refuse) et un numéro de dossier unique au format PRE-2026-00001. Règles de validation : téléphone ivoirien de 10 chiffres commençant par 01, 05, 07, 21, 25 ou 27 (avec ou sans +225) ; âge entre 9 et 25 ans à la rentrée ; fichiers en PDF, JPG ou PNG, de 2 Mo maximum chacun, avec un type MIME vérifié côté serveur ; fichiers renommés de façon aléatoire et rangés hors du dossier public. Après l'envoi, afficher une page de confirmation avec le numéro de dossier et envoyer un e-mail de confirmation si une adresse a été donnée. Ajouter une page « Suivre mon dossier » : on saisit le numéro de dossier et la date de naissance de l'élève pour voir le statut.

### Critères d’acceptation obligatoires
- [ ] Un dossier valide est enregistré, reçoit un numéro unique au bon format, et la page de confirmation l'affiche (testé).
- [ ] Les cas suivants sont refusés avec un message en français sous le champ concerné : téléphone invalide, fichier .exe renommé en .pdf, fichier de plus de 2 Mo, série manquante en 2nde (chacun testé).
- [ ] Les fichiers téléversés ne sont jamais accessibles par une URL publique directe (testé).
- [ ] La page « Suivre mon dossier » affiche le statut seulement si le numéro et la date de naissance correspondent tous les deux (testé).

### Contrôles spécifiques obligatoires
- Vérifier les fichiers téléversés côté serveur, type MIME, taille, nom aléatoire et stockage hors dossier public.
- Ne pas exposer un dossier de pièces jointes par URL directe ; contrôler les droits sur le suivi et les compléments.

### Conditions de clôture
- Tous les critères ci-dessus sont satisfaits ou un blocage précis est documenté ; aucun échec n’est dissimulé.
- Les tests pertinents sont exécutés et le diff a été inspecté.
- Le rapport de fin de tâche est fourni et KiloCode attend `VALIDER TÂCHE 5` avant de proposer l’implémentation suivante.

## Tâche 6 — Créer le back-office de traitement des préinscriptions

**Dépendances :** Tâche 3, Tâche 5

### Travail demandé
Créer l'espace scolarité, réservé aux rôles scolarite et admin. Il contient la liste paginée des dossiers (20 par page), avec des filtres par statut, niveau et série, et une recherche par nom ou numéro. La fiche détail d'un dossier permet de voir les pièces jointes à travers un contrôleur sécurisé qui vérifie les droits. Actions possibles : marquer « incomplet » (le motif est obligatoire), « accepté » ou « refusé » (le motif est obligatoire). Chaque changement de statut est enregistré dans la table historique_dossiers (auteur, date, ancien statut, nouveau statut, motif). Il déclenche aussi un e-mail au responsable si une adresse est connue. Un dossier incomplet peut être complété par le responsable depuis la page « Suivre mon dossier », par l'ajout des pièces manquantes, et repasse alors au statut « soumis ». Ajouter un export Excel de la liste filtrée avec l'outil d'export Excel d'Epaphrodite.

### Critères d’acceptation obligatoires
- [ ] Un enseignant ou un élève qui ouvre ce back-office reçoit une erreur 403 (testé).
- [ ] Refuser un dossier ou le marquer incomplet sans motif est impossible (testé).
- [ ] Chaque changement de statut crée une ligne dans historique_dossiers (testé).
- [ ] Un dossier déjà accepté ou refusé ne peut plus changer de statut depuis cet écran (testé).
- [ ] L'export Excel contient exactement les dossiers affichés par le filtre en cours.

### Contrôles spécifiques obligatoires
- Les pièces jointes sont délivrées par une action sécurisée qui vérifie le rôle et les autorisations.
- Tracer l’auteur, la date, l’ancien statut, le nouveau statut et le motif de chaque changement.

### Conditions de clôture
- Tous les critères ci-dessus sont satisfaits ou un blocage précis est documenté ; aucun échec n’est dissimulé.
- Les tests pertinents sont exécutés et le diff a été inspecté.
- Le rapport de fin de tâche est fourni et KiloCode attend `VALIDER TÂCHE 6` avant de proposer l’implémentation suivante.

## Tâche 7 — Inscrire définitivement l'élève et créer ses comptes

**Dépendances :** Tâche 2, Tâche 3, Tâche 6

### Travail demandé
Depuis un dossier accepté, ajouter l'action « Inscrire ». Elle crée l'élève dans la table eleves (identité et matricule interne unique au format AAAA-NNNNN), le responsable dans la table responsables (un responsable peut avoir plusieurs enfants : on le reconnaît par son numéro de téléphone pour éviter les doublons), le lien eleve_responsable, et l'inscription dans la table inscriptions (eleve_id, annee_id, classe_id, statut_affectation, date). Le choix de la classe se limite aux classes du niveau et de la série demandés qui ne sont pas pleines (effectif inférieur à effectif_max). L'action crée aussi un compte utilisateur « eleve » et, si besoin, un compte « parent », avec des mots de passe provisoires. Elle génère une fiche d'inscription en PDF avec dompdf : photo, identité, classe, matricule et identifiants provisoires. Le tout se fait dans une transaction : en cas d'erreur, rien n'est créé. Ajouter aussi la réinscription d'un élève déjà connu pour l'année active, sans nouveau dossier, avec le choix d'une classe.

### Critères d’acceptation obligatoires
- [ ] Inscrire un dossier accepté crée l'élève, l'inscription, le compte élève et le compte parent. Le dossier ne peut plus être inscrit une deuxième fois (testé).
- [ ] Une classe pleine n'est pas proposée et est refusée côté serveur (testé).
- [ ] Un second enfant avec le même téléphone de responsable est rattaché au responsable existant (testé).
- [ ] Si une erreur survient au milieu de l'opération, aucune ligne n'est créée (test de rollback).
- [ ] Un élève ne peut avoir qu'une seule inscription par année scolaire (contrainte unique, testée).
- [ ] Le PDF de la fiche d'inscription se télécharge et contient le matricule.

### Contrôles spécifiques obligatoires
- Effectuer la création liée dans une transaction réelle si le moteur et la couche Epaphrodites le permettent.
- Ne jamais renvoyer de mots de passe provisoires dans des logs ; protéger la fiche PDF et les identifiants.

### Conditions de clôture
- Tous les critères ci-dessus sont satisfaits ou un blocage précis est documenté ; aucun échec n’est dissimulé.
- Les tests pertinents sont exécutés et le diff a été inspecté.
- Le rapport de fin de tâche est fourni et KiloCode attend `VALIDER TÂCHE 7` avant de proposer l’implémentation suivante.

## Tâche 8 — Gérer les frais de scolarité et les paiements

**Dépendances :** Tâche 7

### Travail demandé
Créer la table grilles_frais : année, niveau, statut d'affectation, type (inscription, scolarité, cantine facultative, transport facultatif), montant en FCFA (entier, sans décimales). Créer la table echeances : grille, numéro, date limite, montant ; la somme des échéances doit être égale au montant de la grille. Créer la table paiements : inscription_id, montant, date, mode (especes, cheque, orange_money, mtn_momo, moov_money, wave), reference (obligatoire pour les Mobile Money, unique par mode), caissier_id, et numéro de reçu unique au format REC-2026-000001. Dans l'écran comptabilité : rechercher un élève, voir ce qu'il doit, ce qu'il a payé et son reste à payer par échéance, enregistrer un paiement, imprimer le reçu en PDF, et lister les impayés (échéance dépassée et solde supérieur à 0) avec un export Excel. Un paiement ne se supprime jamais : on peut seulement l'annuler, avec un motif et par un administrateur, et l'annulation est tracée. Un montant supérieur au reste à payer est refusé.

### Critères d’acceptation obligatoires
- [ ] Le reste à payer est juste après plusieurs paiements partiels (test unitaire avec au moins 3 scénarios).
- [ ] Un paiement Mobile Money sans référence, ou avec une référence déjà utilisée, est refusé (testé).
- [ ] Un paiement supérieur au reste à payer est refusé (testé).
- [ ] Un paiement annulé ne compte plus dans le solde et reste visible avec la mention « Annulé » (testé).
- [ ] Seul un administrateur peut annuler un paiement (testé).
- [ ] La liste des impayés n'affiche que les élèves dont une échéance est dépassée et le solde est positif (testé).

### Contrôles spécifiques obligatoires
- Utiliser des montants entiers en FCFA et des opérations transactionnelles pour les paiements/annulations.
- Ne jamais supprimer un paiement : annulation tracée et limitée à l’administrateur.

### Conditions de clôture
- Tous les critères ci-dessus sont satisfaits ou un blocage précis est documenté ; aucun échec n’est dissimulé.
- Les tests pertinents sont exécutés et le diff a été inspecté.
- Le rapport de fin de tâche est fourni et KiloCode attend `VALIDER TÂCHE 8` avant de proposer l’implémentation suivante.

## Tâche 9 — Gérer les enseignants et leurs affectations

**Dépendances :** Tâche 2, Tâche 3

### Travail demandé
Créer un CRUD des enseignants, réservé à l'administrateur : nom, prénoms, téléphone, e-mail, matière(s) enseignée(s). La création d'un enseignant crée son compte avec le rôle « enseignant ». Créer la table affectations : enseignant_id, classe_id, matiere_id, annee_id, avec une clé unique sur classe, matière et année (un seul enseignant par matière dans une classe). Désigner un professeur principal par classe (classes.professeur_principal_id). Un enseignant ne peut être affecté qu'à une matière dont un coefficient existe pour le niveau et la série de la classe. Un enseignant qui quitte l'école est désactivé (il ne peut plus se connecter), mais il n'est jamais supprimé, pour garder l'historique des notes.

### Critères d’acceptation obligatoires
- [ ] Affecter deux enseignants à la même matière dans la même classe pour la même année est refusé (testé).
- [ ] Affecter un enseignant à une matière sans coefficient pour ce niveau et cette série est refusé (testé).
- [ ] Un enseignant désactivé ne peut plus se connecter, et ses anciennes affectations restent consultables (testé).
- [ ] L'enseignant voit uniquement ses propres classes et matières sur son tableau de bord (testé).

### Contrôles spécifiques obligatoires
- Désactiver un enseignant sans effacer son historique.
- Chaque affectation doit être autorisée par les coefficients et l’année concernés.

### Conditions de clôture
- Tous les critères ci-dessus sont satisfaits ou un blocage précis est documenté ; aucun échec n’est dissimulé.
- Les tests pertinents sont exécutés et le diff a été inspecté.
- Le rapport de fin de tâche est fourni et KiloCode attend `VALIDER TÂCHE 9` avant de proposer l’implémentation suivante.

## Tâche 10 — Permettre aux enseignants de saisir les notes

**Dépendances :** Tâche 7, Tâche 9

### Travail demandé
Créer la table evaluations : affectation_id, periode_id, type (interrogation, devoir, composition), date, libelle, bareme (20 par défaut, 10 accepté avec ramenage sur 20). Créer la table notes : evaluation_id, eleve_id, valeur (décimal de 0 au barème, deux décimales au plus, vide autorisé avec le motif « absent » ou « dispensé »). L'enseignant crée une évaluation dans une de ses affectations, puis saisit les notes de toute la classe dans un tableau en une seule page, avec enregistrement groupé. Il peut modifier ses notes tant que la période n'est pas clôturée. Une fois la période clôturée par la scolarité, plus personne ne peut modifier les notes, sauf l'administrateur, et chaque modification est tracée (ancienne valeur, nouvelle valeur, auteur, date).

### Critères d’acceptation obligatoires
- [ ] Une note négative, supérieure au barème ou avec plus de 2 décimales est refusée (testé).
- [ ] Un enseignant ne peut pas créer d'évaluation ni saisir de notes dans une classe ou une matière qui ne lui est pas affectée (testé, erreur 403).
- [ ] Après la clôture de la période, la modification par l'enseignant est refusée (testé).
- [ ] La modification par l'administrateur après clôture crée une trace d'audit (testé).
- [ ] Le tableau de saisie d'une classe de 60 élèves s'enregistre en une seule soumission.

### Contrôles spécifiques obligatoires
- Vérifier l’autorisation au niveau serveur pour chaque affectation et chaque élève.
- Les opérations groupées doivent être validées intégralement ; tracer les modifications administratives après clôture.

### Conditions de clôture
- Tous les critères ci-dessus sont satisfaits ou un blocage précis est documenté ; aucun échec n’est dissimulé.
- Les tests pertinents sont exécutés et le diff a été inspecté.
- Le rapport de fin de tâche est fourni et KiloCode attend `VALIDER TÂCHE 10` avant de proposer l’implémentation suivante.

## Tâche 11 — Calculer les moyennes et générer les bulletins

**Dépendances :** Tâche 10

### Travail demandé
Créer un service CalculMoyenneService, testé de façon isolée. Moyenne par matière et par période : moyenne des notes ramenées sur 20 ; les absences et dispenses sont exclues ; règle configurable de pondération de la composition (par défaut, moyenne simple). Moyenne générale : somme de (moyenne de la matière ? coefficient) divisée par la somme des coefficients des seules matières notées. Les résultats sont arrondis à 2 décimales. Rang dans la classe : les ex æquo reçoivent le même rang et le rang suivant est sauté (exemple : 1, 2, 2, 4). Distinctions configurables : Tableau d'honneur si la moyenne est d'au moins 12, Encouragements à partir de 14, Félicitations à partir de 16 ; un avertissement de travail en dessous de 8,5. Moyenne annuelle : moyenne des périodes. Le professeur principal saisit une appréciation générale, et chaque enseignant une appréciation par matière (255 caractères au plus). La scolarité génère les bulletins en PDF pour toute la classe (un fichier par élève, plus un ZIP pour la classe), uniquement pour une période clôturée. Chaque bulletin montre : en-tête de l'école, identité, classe, effectif, notes par matière avec coefficient, moyenne de la classe, moyenne la plus haute et la plus basse, rang, appréciations et distinction.

### Critères d’acceptation obligatoires
- [ ] Des tests unitaires couvrent : un cas simple, une matière sans note exclue, des ex æquo (rangs 1, 2, 2, 4), un barème sur 10, et un élève absent à toutes les évaluations d'une matière.
- [ ] La moyenne générale est arrondie à 2 décimales (testé).
- [ ] Générer les bulletins d'une période non clôturée est refusé (testé).
- [ ] Le bulletin PDF de chaque élève de la classe se génère et le ZIP se télécharge.
- [ ] Les seuils des distinctions viennent de la configuration et non du code (testé en changeant un seuil).

### Contrôles spécifiques obligatoires
- Isoler les calculs dans un service testable sans interface ni accès réseau.
- Ne pas modifier les règles de calcul pédagogique sans décision humaine écrite ; conserver paramètres et seuils configurables.

### Conditions de clôture
- Tous les critères ci-dessus sont satisfaits ou un blocage précis est documenté ; aucun échec n’est dissimulé.
- Les tests pertinents sont exécutés et le diff a été inspecté.
- Le rapport de fin de tâche est fourni et KiloCode attend `VALIDER TÂCHE 11` avant de proposer l’implémentation suivante.

## Tâche 12 — Gérer les absences, les retards et les sanctions

**Dépendances :** Tâche 9, Tâche 11

### Travail demandé
Créer la table absences : eleve_id, date, créneau horaire ou demi-journée, type (absence ou retard), matiere_id (facultatif), saisi_par, justifiee (booléen), motif_justification. Les enseignants saisissent les absences de leurs classes pendant leurs cours, et la scolarité peut aussi en saisir pour toutes les classes. La scolarité marque une absence comme justifiée, avec un motif. Créer la table sanctions : eleve_id, type (avertissement, blâme, exclusion temporaire, avec un nombre de jours), motif, date, decide_par ; réservée à la scolarité et à l'administrateur. Calculer le total des heures d'absence justifiées et non justifiées par période et l'afficher sur le bulletin (modification du gabarit de la tâche 11). Envoyer un e-mail au parent quand les absences non justifiées dépassent un seuil configurable (6 heures par défaut) sur la période.

### Critères d’acceptation obligatoires
- [ ] Un enseignant ne peut saisir des absences que pour ses propres classes (testé).
- [ ] Saisir deux fois la même absence (même élève, même date, même créneau) est refusé (testé).
- [ ] Les totaux justifiés et non justifiés par période sont justes (test unitaire) et apparaissent sur le bulletin.
- [ ] L'e-mail d'alerte part une seule fois quand le seuil est franchi, et pas à chaque nouvelle absence (testé avec un faux service d'envoi de mails).

### Contrôles spécifiques obligatoires
- Protéger les données disciplinaires et justifications ; tracer qui a justifié/modifié quoi.
- Le faux service de mail doit être utilisé en test : ne pas envoyer de mail réel depuis la suite automatisée.

### Conditions de clôture
- Tous les critères ci-dessus sont satisfaits ou un blocage précis est documenté ; aucun échec n’est dissimulé.
- Les tests pertinents sont exécutés et le diff a été inspecté.
- Le rapport de fin de tâche est fourni et KiloCode attend `VALIDER TÂCHE 12` avant de proposer l’implémentation suivante.

## Tâche 13 — Créer l'espace élève

**Dépendances :** Tâche 8, Tâche 11, Tâche 12

### Travail demandé
Créer le tableau de bord de l'élève connecté. Il est en lecture seule et ne montre que les données de cet élève. Il contient : sa fiche (photo, matricule, classe, professeur principal) ; ses notes par période et par matière, avec la moyenne actuelle (provisoire tant que la période n'est pas clôturée, avec la mention « provisoire ») ; ses bulletins des périodes clôturées en PDF ; ses absences, retards et sanctions ; sa situation financière (total dû, payé, reste à payer, prochaine échéance) ; les actualités de l'école. Toute requête vers les données d'un autre élève, par exemple en changeant l'identifiant dans l'URL, renvoie une erreur 403. L'affichage doit être lisible sur un smartphone.

### Critères d’acceptation obligatoires
- [ ] Un élève qui change l'identifiant dans l'URL pour voir le bulletin d'un autre élève reçoit une erreur 403 (testé).
- [ ] Les bulletins d'une période non clôturée ne sont pas téléchargeables (testé).
- [ ] La moyenne provisoire est clairement indiquée comme « provisoire ».
- [ ] Le reste à payer affiché est le même que celui de l'écran comptabilité (testé avec les mêmes données).

### Contrôles spécifiques obligatoires
- Ne pas faire confiance à l’identifiant fourni dans l’URL : retrouver l’élève associé à la session connectée.
- Refuser côté serveur tout accès à un autre élève, y compris les PDF et téléchargements.

### Conditions de clôture
- Tous les critères ci-dessus sont satisfaits ou un blocage précis est documenté ; aucun échec n’est dissimulé.
- Les tests pertinents sont exécutés et le diff a été inspecté.
- Le rapport de fin de tâche est fourni et KiloCode attend `VALIDER TÂCHE 13` avant de proposer l’implémentation suivante.

## Tâche 14 — Créer l'espace parent

**Dépendances :** Tâche 13

### Travail demandé
Créer le tableau de bord du parent connecté. On y trouve la liste de ses enfants inscrits (relation eleve_responsable) et un sélecteur pour changer d'enfant. Pour chaque enfant, le parent voit les mêmes écrans que l'espace élève (tâche 13), en réutilisant les mêmes contrôleurs et gabarits avec un contrôle d'accès « parent de cet élève ». Le parent peut télécharger les reçus de paiement de chaque enfant. Il peut déclarer un justificatif d'absence (texte et pièce jointe PDF ou JPG de 2 Mo maximum) : l'absence reste non justifiée tant que la scolarité ne l'a pas validée. Le parent peut modifier son téléphone et son e-mail, avec une nouvelle vérification de l'e-mail, mais pas l'identité de l'enfant.

### Critères d’acceptation obligatoires
- [ ] Un parent ne voit que ses propres enfants. Accéder à un autre élève renvoie une erreur 403 (testé).
- [ ] Un parent de 2 enfants peut passer de l'un à l'autre et voit des données différentes (testé).
- [ ] Un justificatif déposé ne rend pas l'absence justifiée tant que la scolarité ne l'a pas validé (testé).
- [ ] Les reçus téléchargés sont identiques à ceux de la comptabilité.

### Contrôles spécifiques obligatoires
- Vérifier la relation parent-enfant à chaque requête, pas uniquement dans le sélecteur de l’interface.
- Les justificatifs soumis par le parent restent en attente jusqu’à la validation de la scolarité.

### Conditions de clôture
- Tous les critères ci-dessus sont satisfaits ou un blocage précis est documenté ; aucun échec n’est dissimulé.
- Les tests pertinents sont exécutés et le diff a été inspecté.
- Le rapport de fin de tâche est fourni et KiloCode attend `VALIDER TÂCHE 14` avant de proposer l’implémentation suivante.

## Tâche 15 — Créer le tableau de bord de la direction et le journal d'audit

**Dépendances :** Tâche 6, Tâche 8, Tâche 11, Tâche 12

### Travail demandé
Créer le tableau de bord de l'administrateur avec des indicateurs pour l'année active : nombre de préinscriptions par statut, effectifs par niveau, par classe et par sexe, taux de remplissage des classes, montant attendu, encaissé et impayé en FCFA, moyennes par classe pour la dernière période clôturée, et les 10 élèves cumulant le plus d'absences non justifiées. Créer une table audit_logs (utilisateur, action, entité, identifiant, données avant et après en JSON, adresse IP, date). Elle est alimentée automatiquement pour les actions sensibles : changements de statut de dossier, inscriptions, paiements et annulations, modifications de notes après clôture, sanctions, changements de rôle. Ajouter un écran de consultation du journal avec des filtres par date, utilisateur et action, réservé à l'administrateur. Ajouter des exports Excel : listes de classe (matricule, nom, prénoms, date de naissance, sexe, statut d'affectation) et état des encaissements par période. Ajouter un test de non-régression de bout en bout : préinscription, acceptation, inscription, paiement, saisie de notes, clôture, bulletin, consultation par l'élève.

### Critères d’acceptation obligatoires
- [ ] Les indicateurs sont justes sur un jeu de données de test connu (testé).
- [ ] Chaque action sensible listée crée bien une ligne dans audit_logs (un test par type d'action).
- [ ] Le journal d'audit n'est consultable que par l'administrateur, et aucune route ne permet de modifier ou supprimer ses lignes (testé).
- [ ] Le test de bout en bout passe dans la CI.
- [ ] Les exports Excel s'ouvrent dans un tableur et contiennent les colonnes prévues.

### Contrôles spécifiques obligatoires
- Journal d’audit append-only depuis l’application : aucune route d’édition ou de suppression.
- Les indicateurs et exports doivent respecter les mêmes filtres, permissions et règles de calcul que les écrans sources.

### Conditions de clôture
- Tous les critères ci-dessus sont satisfaits ou un blocage précis est documenté ; aucun échec n’est dissimulé.
- Les tests pertinents sont exécutés et le diff a été inspecté.
- Le rapport de fin de tâche est fourni et KiloCode attend `VALIDER TÂCHE 15` avant de proposer l’implémentation suivante.

## 8. Jalons de validation humaine
- [ ] Choix et achat de l'hébergement (serveur PHP 8.2 et MySQL), du nom de domaine et du certificat SSL : ce sont des dépenses à valider avant la mise en production.
- [ ] Mise en production : aucune tâche ne déploie l'application. Le déploiement, la configuration du serveur de mails (SMTP) et les sauvegardes automatiques de la base doivent être faits et validés par une personne.
- [ ] Protection des données personnelles de mineurs : vérifier les obligations de la loi ivoirienne n°2013-450 et une éventuelle déclaration auprès de l'ARTCI avant de collecter de vraies données.
- [ ] Validation par la direction des règles pédagogiques : coefficients par niveau et par série, mode de calcul des moyennes (poids de la composition), seuils des distinctions, trimestres ou semestres.
- [ ] Validation par la comptabilité des grilles de frais (affectés et non affectés), des échéanciers et du format des reçus.
- [ ] Paiement Mobile Money en ligne (Orange Money, MTN MoMo, Moov Money, Wave), prévu en phase 2 : il demande un contrat marchand ou un agrégateur de paiement, avec des frais, à valider.
- [ ] Notifications SMS, prévues en phase 2 : abonnement payant auprès d'un fournisseur.
- [ ] Import des élèves déjà présents dans l'école (données existantes) : le fichier source et la méthode d'import doivent être validés, et l'import fait sur une copie avant la production.
- [ ] Archivage ou suppression des données des anciens élèves (phase 2) : la durée de conservation et toute suppression doivent être décidées par la direction.
- [ ] Validation de la phase 1 avant de lancer la planification détaillée de la phase 2 (fin de parcours, orientation, examens, certificats).

Ces points ne doivent pas être réglés silencieusement par l’agent. Quand l’une de ces décisions devient bloquante, arrête-toi et demande une décision explicite. En particulier : règles pédagogiques, coefficients, formule de moyenne, distinctions, seuils, grilles de frais et échéanciers doivent être approuvés avant leur utilisation en production.

## 9. Risques à surveiller
- Epaphrodite est un framework récent avec une petite communauté. Codex peut mal connaître ses conventions (routes, contrôleurs, droits, migrations). Il faut imposer la lecture de la documentation officielle et du squelette généré, et relire attentivement la Pull Request de la tâche 1, qui servira de modèle aux suivantes.
- La compatibilité de PHPUnit et de la base SQLite de test avec les outils du framework n'a pas été vérifiée. Si un blocage apparaît dans la tâche 1, prévoir une alternative : base MySQL de test dans la CI via un service Docker.
- Les règles de calcul des moyennes et des rangs varient d'un établissement à l'autre. Une erreur sur les bulletins entraînerait des contestations. Les règles doivent être validées par écrit avant la tâche 11 et rester configurables.
- Les données concernent des mineurs (identité, photos, notes, sanctions). Une fuite par une faille de contrôle d'accès (accès direct par l'identifiant dans l'URL) serait grave. D'où les tests 403 systématiques dans les tâches 3, 13 et 14.
- Les connexions internet sont parfois lentes ou instables chez les familles. Il faut des pages légères, des fichiers limités à 2 Mo et peu de JavaScript.
- La génération en masse de bulletins PDF (plusieurs centaines d'élèves) peut dépasser le temps d'exécution maximal de PHP. Il faut générer classe par classe et, si besoin, passer en tâche de fond en phase 2.
- La fin de parcours de l'élève, au cœur de la demande (passage, orientation, BEPC, BAC, départ), ne fait pas partie de la phase 1. Le schéma des inscriptions par année scolaire est conçu pour l'accueillir sans refonte.

## 10. Définition globale de « terminé » pour la phase 1
La phase 1 est terminée uniquement lorsque les 15 tâches sont individuellement validées, les tests de non-régression passent, la CI est confirmée verte (ou les blocages externes sont explicitement listés), les contrôles d’accès sensibles sont testés, aucune donnée réelle/secrète n’a été ajoutée au dépôt, et le porteur du projet a approuvé les règles pédagogiques et financières indispensables. La fin de parcours scolaire et les autres fonctionnalités phase 2 restent hors périmètre.

## 11. Instruction de démarrage pour KiloCode
Lis ce document intégralement ainsi que les documents de référence disponibles. Commence par `DÉMARRER AUDIT`. Ne modifie aucun fichier avant d’avoir terminé la Tâche 0 et présenté son compte rendu. Ensuite, attends mon autorisation explicite pour la Tâche 1. Respecte le verrou de validation à la fin de chaque tâche.

---
**Principe non négociable : inspecter → comprendre → planifier → modifier au minimum → tester → rapporter → attendre la validation humaine.**
