# Gestion scolaire (Epaphrodites)

## Prérequis
- PHP 8.2+ avec extensions curl, pdo_mysql, pdo_sqlite, gd, intl, mbstring, zip
- Composer
- MySQL 8 / MariaDB 10.6+

## Installation
```bash
composer install
cp bin/config/Config.ini.example bin/config/Config.ini
cp bin/config/email.ini.example bin/config/email.ini
```
Renseigner les paramètres locaux dans `Config.ini` et `email.ini`. Ces fichiers locaux ne doivent pas être commités ; seuls leurs exemples sans secret sont versionnés.
Dans la section `[AUTHENTICATION]` de `Config.ini`, renseigner l’adresse administrateur et un mot de passe provisoire conforme (8 caractères minimum, une lettre et un chiffre). Ce mot de passe n’est jamais fourni par défaut ; le compte administrateur doit le changer à sa première connexion. Renseigner également `APP_URL` avec l’URL publique de l’application afin que les liens de réinitialisation ne dépendent pas de l’en-tête `Host` de la requête.
Dans `[SITE]`, configurer le nom, le logo local, l’adresse, le téléphone ivoirien au format `+225` et l’adresse `CONTACT_EMAIL` qui recevra les messages du formulaire. Les réseaux sociaux sont facultatifs et doivent être des URL HTTP(S). Les paramètres d’e-mail SMTP restent dans `email.ini`.

## Lancement local
```bash
php heredia run:server --port=8000
```
Ouvrir ensuite `http://127.0.0.1:8000`.

## Tests
Dans un premier terminal, lancer le serveur local comme indiqué ci-dessus. Dans un second terminal :
```bash
vendor/bin/phpunit
vendor/bin/phpstan analyse
```
La suite PHPUnit vérifie la page d'accueil par HTTP. La CI démarre automatiquement son serveur PHP avant de lancer les tests.
La CI exécute aussi les migrations et le seeder sur un service MySQL 8 isolé ; les tests locaux utilisent SQLite en mémoire.

## Conventions
- Branches : `feature/<tache>`, `fix/<issue>`
- Commits : messages concis en français

## Configuration des bases de données
`bin/config/Config.ini.example` fournit deux profils fictifs : MySQL pour le développement et SQLite pour les tests. Copier ce fichier vers `bin/config/Config.ini`, puis renseigner les paramètres adaptés à l'environnement. Les migrations ne sont pas exécutées automatiquement par la commande d'installation.

## Gestion des étudiants
- Route d'administration : `students/manage_students/`.
- L'accès suit le système de droits Epaphrodites (module `students`, droit `studentsFolder/manage_students`).
- Champs obligatoires : matricule, nom, prénom et classe. Date de naissance, sexe, téléphone, e-mail et adresse sont facultatifs.
- La migration `eleves` est enregistrée dans `makeGearShift::up()` sur la connexion 1 et n'est pas appliquée automatiquement. La commande `php heredia migrate -u` exécute toutes les migrations `up` ; vérifier leur état et sauvegarder la base avant toute exécution.
- Les suppressions sont des archivages réversibles (`est_archive`).
- `classe_id` référence `classes.id` et est vérifié lors de l'enregistrement ou de la modification.

## Site public et actualités
- Les pages d’accueil, établissement, admissions, frais, actualités et contact sont publiques et partagent une mise en page responsive Bootstrap 5.
- Les actualités sont gérées par l’administrateur sur `/news/manage_news/`. Les dates de publication futures ne sont pas visibles publiquement ; les images sont limitées à 2 Mo et aux formats JPG, PNG ou WebP.
- Le formulaire de contact utilise un honeypot et limite les envois à trois par heure et par adresse IP. Le quatrième envoi est refusé avec HTTP 429. Les tentatives plus anciennes sont purgées lors d’un nouvel envoi.
- La migration `actualites` et les tables de limitation du formulaire doivent être appliquées avant d’activer ces fonctionnalités ; aucune migration n’est lancée automatiquement.
- Les frais affichés sont informatifs : les montants exacts doivent être fournis et validés par la direction avant publication.

## Préinscriptions
- Formulaire public en trois étapes : `/preinscription/`; suivi du statut par numéro de dossier et date de naissance : `/suivi_preinscription/`.
- Les dossiers portent un numéro `PRE-AAAA-00001`. Téléphone ivoirien obligatoire ; âge contrôlé à la date de rentrée de l’année scolaire active.
- Les trois pièces obligatoires (acte, bulletin et photo) sont vérifiées par MIME et limitées à 2 Mo chacune. Elles sont stockées dans `../epaphrodites-private/preinscriptions/` (depuis la racine du projet), hors du répertoire du projet/public ; le processus PHP doit pouvoir créer et écrire dans ce dossier.
- La migration `dossiers_preinscription` doit être appliquée avant d’accepter les demandes. La commande globale `php heredia migrate -u` relance toutes les migrations, et n’est donc pas un mécanisme de migration incrémentale sûr sur une base déjà initialisée.

## Authentification et rôles
- Connexion : `/view/login/`, avec adresse e-mail ou matricule.
- Rôles applicatifs : `admin`, `scolarite`, `comptabilite`, `enseignant`, `eleve` et `parent`. Les identifiants historiques 1 à 3 des groupes Epaphrodites sont conservés ; les nouveaux groupes scolaires sont 4 à 8.
- Les routes d’administration vérifient la session, son expiration après 30 minutes d’inactivité, le rôle et les droits Epaphrodites. Les formulaires POST non-API doivent inclure `{{ __csrf() }}`.
- Les mots de passe et jetons de réinitialisation sont hachés ; les liens de réinitialisation expirent après une heure et ne sont utilisables qu’une fois. Après cinq échecs consécutifs, le compte est verrouillé pendant 15 minutes.
