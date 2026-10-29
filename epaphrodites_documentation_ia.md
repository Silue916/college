# Epaphrodites — documentation de référence pour une IA de développement

> **Objectif :** donner à une IA une référence pratique pour comprendre un projet Epaphrodites, choisir les bonnes commandes, créer des vues, gérer des bases SQL/NoSQL, écrire des contrôleurs et éviter les erreurs dangereuses.
>
> **Documentation consultée le :** 29 octobre 2026.  
> **Référence de version :** le site officiel affiche une documentation ancienne (notamment un libellé V0.01) et le dépôt GitHub contient aussi des commandes qui ne figurent pas toutes dans les pages du site. Il faut donc vérifier la version installée avant d'exécuter une commande.

---

## 1. Règles de travail pour l'IA

L'IA qui utilise ce document doit appliquer les règles suivantes :

1. **Commencer par inspecter le projet.** Identifier la racine du projet, `composer.json`, `heredia`, `bin/config/SetDirectory.php`, `bin/config/Config.ini`, `bin/controllers/controllerMap/controllerMap.php` et les répertoires concernés.
2. **Vérifier la CLI locale avant d'utiliser une commande peu connue :**
   ```bash
   # Affiche les commandes disponibles dans la version installée.
   php heredia list

   # Affiche l'aide d'une commande particulière, si la version la prend en charge.
   php heredia help make:schema
   # Variante souvent prise en charge par Symfony Console :
   php heredia make:schema --help
   ```
3. **Ne pas inventer la syntaxe.** Si la commande ou ses arguments ne sont pas confirmés par le site, le code de la version installée ou `--help`, demander une vérification plutôt que deviner.
4. **Ne jamais exécuter automatiquement une action destructive.** `drop:db`, `migrate -d` et une suppression de table peuvent perdre des données. Exiger une confirmation explicite et recommander une sauvegarde.
5. **Ne pas exposer les secrets.** Ne pas afficher les mots de passe ou les valeurs sensibles de `Config.ini`, `.env` ou `email.ini` dans une sortie destinée à l'utilisateur. Ne pas ajouter de vrais secrets au dépôt Git.
6. **Privilégier les requêtes paramétrées.** Utiliser `param([...])` avec des paramètres `?` au lieu de concaténer des valeurs utilisateur dans du SQL.
7. **Créer puis vérifier.** Après toute génération de contrôleur, vue, requête ou migration, vérifier le fichier créé, son chemin, son contenu et les éventuelles erreurs de syntaxe.
8. **Distinguer génération et exécution.** `make:schema` génère du code de migration ; `migrate -u` l'applique. La génération d'une vue n'enregistre pas nécessairement une route utilisable par elle-même.
9. **Respecter la version installée.** Le nom exact d'une commande peut être sensible à la casse. Le dépôt étudié expose par exemple `add:Right` ; utiliser exactement le nom retourné par `php heredia list`.

---

## 2. Epaphrodites en bref

Epaphrodites est un framework applicatif PHP avec des intégrations Python et C. La documentation présente notamment :

- des contrôleurs et un système de routage/mapping ;
- des vues Twig, des layouts et des macros d'interface ;
- une couche d'accès aux données et un query builder ;
- des connexions multiples, SQL et NoSQL ;
- des migrations de schéma et des seeders ;
- des fonctionnalités de sécurité, de gestion des droits, de génération d'API et d'outils en ligne de commande ;
- des intégrations pour Python, des extensions natives C, l'e-mail, l'import/export Excel et les fichiers JSON/TOML.

La commande habituelle du projet est `php heredia ...` depuis la racine du projet. Le lanceur `php epaphrodites ...` est aussi utilisé pour certaines opérations d'installation.

---

## 3. Installation et démarrage

### 3.1 Prérequis répertoriés par le guide

- PHP **8.2 ou plus récent** ;
- Composer ;
- extensions PHP usuelles : OpenSSL, ZIP, PDO, GD, intl, XML, JSON, Mbstring et `pdo_sqlite` ;
- selon le système de données utilisé : `pdo_mysql`, `pdo_pgsql`, `pdo_sqlsrv`, extensions MongoDB/Redis ou autres pilotes adaptés ;
- Python 3 si les fonctions Python du projet sont utilisées ;
- outils de compilation PHP/C (`php-dev`, `phpize`, `php-config`, compilateur) pour construire une extension native C.

Les noms exacts des extensions disponibles dépendent du système d'exploitation, de la version de PHP et du pilote choisi. Vérifier l'environnement local avant d'installer des composants.

### 3.2 Commandes d'installation

```bash
# Crée un nouveau projet Epaphrodites avec Composer.
composer create-project epaphrodites/epaphrodites mon-projet

# Entre dans la racine du projet nouvellement créé.
cd mon-projet

# Lance le vérificateur/installeur d'extensions proposé par le framework.
php epaphrodites -i

# Met à jour les dépendances selon composer.json et composer.lock.
composer update

# Reconstruit l'autoload Composer après une modification d'autoload ou de classes.
composer dump-autoload

# Exemple facultatif : ajoute le paquet PHP MongoDB si l'application utilise MongoDB.
composer require mongodb/mongodb

# Installe les composants Python déclarés par le projet (si cette fonction est configurée).
php heredia pip:component
```

> `composer update` peut mettre à niveau plusieurs dépendances : sur un projet existant, lire le diff et tester l'application avant de déployer.

### 3.3 Lancement local

```bash
# Démarre le serveur applicatif sur le port 8000.
php heredia run:server --port=8000
```

L'URL de développement indiquée dans le guide est `http://127.0.0.1:8000`.

---

## 4. Arborescence utile

L'organisation exacte peut varier selon la version et la configuration, mais la documentation renvoie notamment aux emplacements suivants :

| Chemin | Usage principal |
|---|---|
| `bin/config/SetDirectory.php` | Constantes, environnements et réglages de répertoires du framework. |
| `bin/config/Config.ini` | Paramètres de connexions aux bases de données. |
| `bin/config/email.ini` | Paramètres de courrier électronique. |
| `bin/config/config.json` | Paramètres JSON de certaines parties du projet. |
| `bin/controllers/controllers/` | Classes contrôleurs. |
| `bin/controllers/controllerMap/controllerMap.php` | Déclaration/mapping des contrôleurs et de leurs routes. |
| `bin/database/config/` | Configuration de la couche d'accès aux bases. |
| `bin/database/datas/` | Fichiers de données/configurations (dont JSON selon les fonctions utilisées). |
| `bin/database/gearShift/schema/` | Code de migration généré, notamment `makeUpGearShift.php` et `makeDownGearShift.php`. |
| `bin/database/query/` | Constructeurs et outils de requêtes. |
| `bin/database/requests/` | Classes/fichiers de requêtes de l'application. |
| `bin/database/seeders/` | Seeders et données d'initialisation. |
| `bin/epaphrodites/` | Services et composants internes du framework. |
| `public/views/main/` | Vues Twig destinées aux pages principales. |
| `public/views/admin/` | Vues Twig destinées aux interfaces d'administration. |
| `public/layouts/template/` | Gabarits/layouts. |
| `public/layouts/display/` | Parties de rendu et composants d'affichage. |
| `public/layouts/widgets/` | Macros et widgets Twig. |
| `static/css/`, `static/js/`, `static/img/` | Ressources statiques. |

La documentation ou certains noms historiques peuvent contenir des variantes orthographiques (par exemple `controlleur` dans certaines classes internes). Il faut prendre le chemin réellement présent dans le projet comme référence.

---

## 5. Configuration générale et environnements

### 5.1 `SetDirectory.php`

Le fichier `bin/config/SetDirectory.php` contient des constantes qui influencent l'application. La documentation donne notamment ces exemples :

```php
// Mode développement ; ne pas conserver ce réglage en production.
define('_PRODUCTION_', false);

// Choisit le pilote de base par défaut, ici SQLite.
define('_FIRST_DRIVER_', 'sqlite');
```

Le guide mentionne entre autres les pilotes MySQL, PostgreSQL, SQL Server, SQLite, MongoDB et Redis. Les libellés exacts acceptés peuvent différer selon la version ; le dépôt inspecté vérifie notamment `mysql`, `pgsql`, `sqlserver`, `sqlite`, `mongodb` et `redis` pour `update:driver`.

### 5.2 `Config.ini`

Le fichier `bin/config/Config.ini` peut définir plusieurs connexions numérotées. Exemple de structure à adapter aux paramètres réels :

```ini
; Connexion n°1 : exemple SQL local
[1_CONFIGURATION]
DRIVER = sqlite
DATABASE = app.db
HOST = 127.0.0.1
PORT = 0
USER =
PASSWORD =
SOCKET =
SOCKET_PATH =

; Connexion n°2 : exemple d'une autre base. Remplacer les valeurs fictives.
[2_CONFIGURATION]
DRIVER = mysql
DATABASE = mon_application
HOST = 127.0.0.1
PORT = 3306
USER = utilisateur_application
PASSWORD = remplacer_ce_secret
SOCKET =
SOCKET_PATH =
```

Notes :

- Les sections `[1_CONFIGURATION]`, `[2_CONFIGURATION]`, etc., représentent des profils de connexion ; le numéro passé à certaines commandes/méthodes choisit le profil.
- Ne pas laisser le mot de passe d'exemple tel quel et ne pas publier de secret dans Git.
- Le site contient au moins une coquille (`DIVER`) dans un exemple ; utiliser `DRIVER`, nom présent dans les autres instructions.
- Pour une application existante, ne remplacez pas le fichier de configuration à l'aveugle : conservez les options propres au projet.

### 5.3 Générer ou charger `.env`

Le code du dépôt inspecté enregistre une commande `env` :

```bash
# Génère le fichier .env à partir de Config.ini.
php heredia env --generate
# Variante courte, si disponible dans la version installée.
php heredia env -g

# Charge les variables depuis le fichier .env.
php heredia env --load
# Variante courte.
php heredia env -l
```

Cette commande est absente de certaines listes plus anciennes du site. Vérifier `php heredia env --help` avant utilisation. Le code consulté indique qu'un `.env` manquant peut être généré automatiquement.

### 5.4 Générer la configuration de premier pilote

```bash
# Lit _FIRST_DRIVER_ dans SetDirectory.php et génère les fichiers associés.
php heredia update:driver
```

Il n'y a pas d'argument documenté pour cette commande : le pilote cible vient de la configuration.

---

## 6. Référence des commandes `heredia`

### 6.1 Vérification et aide

```bash
# Liste les commandes disponibles dans la version installée.
php heredia list

# Affiche l'aide générale de la CLI.
php heredia help

# Affiche l'aide d'une commande (si la version de Symfony Console le permet).
php heredia help create:db

# Variante usuelle pour consulter les arguments et options d'une commande.
php heredia create:db --help
```

**Important :** la liste ci-dessous combine la documentation publique du site et les commandes enregistrées dans le dépôt GitHub consulté. Elle n'est pas une garantie que toutes existent dans chaque version installée. La sortie locale de `php heredia list` reste prioritaire.

### 6.2 Commandes générales et serveur

| Commande | Syntaxe / exemple | Fonction et précautions |
|---|---|---|
| `list` | `php heredia list` | Liste les commandes CLI disponibles. Cette commande peut venir de Symfony Console. |
| `help` | `php heredia help create:front` | Aide détaillée d'une commande, si la version le permet. |
| `run:server` | `php heredia run:server --port=8000` | Démarre le serveur web local. Le port doit être dans la plage 1–65535 et libre. |
| `server` | `php heredia server` | Commande enregistrée dans le dépôt consulté pour gérer le serveur Python. Son comportement précis et ses options doivent être vérifiés avec `--help`. |
| `pip:component` | `php heredia pip:component` | Installe les composants Python prévus par le framework/projet. Utiliser dans un environnement Python configuré. |
| `env` | `php heredia env --generate` ou `php heredia env --load` | Génère ou charge les variables d'environnement ; peut n'exister que dans des versions plus récentes. |

### 6.3 Bases de données, schémas et migrations

| Commande | Syntaxe / exemple | Fonction et précautions |
|---|---|---|
| `create:db` | `php heredia create:db nom_base` | Crée une base en utilisant le profil de connexion n°1 par défaut. |
| `create:db` avec connexion | `php heredia create:db nom_base 2` | Crée la base en utilisant la configuration numéro 2 de `Config.ini`. |
| `drop:db` | `php heredia drop:db nom_base 2` | Supprime la base visée dans le profil 2. **Destructif :** sauvegarde et confirmation obligatoires. La définition de commande du dépôt demande le numéro de configuration ; fournissez-le explicitement. |
| `make:schema` — créer une table | `php heredia make:schema create_post_table` | Génère une migration de création de table dans `makeUpGearShift.php`. Ne doit pas être confondu avec l'application effective de la migration. |
| `make:schema` — ajouter une colonne | `php heredia make:schema add_label_to_post_table` | Génère une migration ajoutant la colonne `label` à la table `post`. Le nom suit le modèle `add_<colonne>_to_<table>_table`. Vérifier le code généré. |
| `make:schema` — supprimer une table | `php heredia make:schema drop_post_table` | Génère une opération de suppression dans `makeDownGearShift.php`. **Destructif au moment de l'application.** |
| `migrate -u` | `php heredia migrate -u` | Applique les opérations « up » générées dans le schéma. Inspecter d'abord la migration et la connexion cible. |
| `migrate -d` | `php heredia migrate -d` | Exécute les opérations « down » de suppression/retour arrière configurées. **À ne pas exécuter en production sans sauvegarde et validation.** |
| `make:seeder` SQL | `php heredia make:seeder sql` | Lance/génère le traitement de seeding SQL selon l'implémentation de la version installée. |
| `make:seeder` NoSQL | `php heredia make:seeder nosql` | Lance/génère le traitement de seeding NoSQL. Les valeurs admises par le code consulté sont `sql` et `nosql`. |
| `copy:table` | `php heredia copy:table -c` | Lance le mécanisme configuré de copie de tables entre bases. Nécessite une configuration adaptée. Ne pas le lancer sur des bases de production sans contrôle. |
| `update:driver` | `php heredia update:driver` | Génère les premiers fichiers/services liés au pilote indiqué par `_FIRST_DRIVER_`. |

#### 6.3.1 Exemple de code de migration

Le site montre un exemple de ce type (à adapter, car certains extraits publiés présentent des coquilles de syntaxe) :

```php
// Exemple conceptuel d'une migration de table ; vérifier la syntaxe supportée
// par la version installée et relire le code généré avant de l'appliquer.
public function createPost()
{
    return $this->createTable('post', function ($table) {
        $table->addColumn('idpost', 'INTEGER', ['PRIMARY KEY']);
        $table->addColumn('title', 'VARCHAR(100)', ['NOT NULL']);
        $table->addColumn('content', 'TEXT', ['NOT NULL']);
        $table->addColumn('created_at', 'DATETIME');
        $table->addColumn('updated_at', 'DATETIME');
        $table->addIndex('title');
        // Exemple seulement : ajuster la contrainte étrangère à la vraie API du builder.
        // $table->addForeign('category_id', '_id');
        $table->db(1); // Profil de connexion n°1 dans Config.ini.
    });
}
```

Après génération :

```bash
# Étape 1 : examiner le fichier créé dans bin/database/gearShift/schema/.
# Étape 2 : seulement après validation, appliquer la migration.
php heredia migrate -u
```

Une migration doit être vérifiée pour le pilote cible, les types SQL, les noms de colonnes, les contraintes, les clés étrangères et l'existence préalable de la base.

### 6.4 Vues et contrôleurs

| Commande | Syntaxe / exemple | Fonction et précautions |
|---|---|---|
| `create:front` | `php heredia create:front main accueil` | Crée une vue Twig `accueil` dans le répertoire principal correspondant, normalement sous `public/views/main/`. Le répertoire cible doit déjà exister. |
| `create:front` dans un sous-dossier | `php heredia create:front admin@utilisateurs liste` | Utilise `@` comme séparateur de répertoires ; cible attendue sous `public/views/admin/utilisateurs/`. Vérifier que ce dossier existe. |
| `create:controller` | `php heredia create:controller UtilisateurController` | Génère le fichier du contrôleur et son squelette de mapping. Le fichier se trouve normalement dans `bin/controllers/controllers/`. |
| `add:view` | `php heredia add:view UtilisateurController liste` | Ajoute une méthode/page au contrôleur spécifié. Le premier argument doit être un contrôleur existant ; le second sert de nom de chemin/méthode et sera normalisé. |
| `make:api` | `php heredia make:api liste_utilisateurs` | Génère une méthode de traitement API dans le contrôleur API prévu par le projet (souvent `bin/controllers/controllers/api.php`). Il faut ensuite vérifier la route et la réponse. |

**Ordre recommandé pour créer une page :**

1. Vérifier si le contrôleur existe ; sinon créer le contrôleur avec `create:controller`.
2. Créer la vue avec `create:front` dans un répertoire existant.
3. Ajouter/adapter la méthode de page avec `add:view` et relire la méthode générée.
4. Vérifier le mapping dans `bin/controllers/controllerMap/controllerMap.php`.
5. Vérifier les noms exacts des vues, layouts et permissions puis tester la route.

### 6.5 Fichiers de requêtes, SQL/NoSQL et utilitaires

| Commande | Syntaxe | Fonction et usage |
|---|---|---|
| `create:requestfile` | `php heredia create:requestfile insert utilisateur` | Génère un fichier de requêtes dans le dossier correspondant au type (`insert`, `select`, `update`, `delete`, selon la structure configurée). Le code consulté attend les arguments `file` puis `name`. Vérifier la syntaxe et l'emplacement créés. |
| `add:sql` | `php heredia add:sql <directory> <typeRequest> <fileLocate> <functionName>` | Ajoute une fonction de requête SQL à un fichier PHP **déjà existant**. Types annoncés : `insert`, `select`, `count`, `update`, `delete`. Le nom du fichier est passé sans `.php`. |
| `add:nosql` | `php heredia add:nosql <directory> <typeRequest> <fileLocate> <functionName>` | Même principe pour une requête NoSQL. Le dossier/le fichier doivent être reconnus par la configuration et exister. Commande présente dans le dépôt consulté, pas dans toutes les listes de la documentation publique. |
| `create:chatbot` | `php heredia create:chatbot assistant` | Génère les fichiers/configurations d'un chatbot. Un contrôleur peut être fourni comme second argument facultatif ; le contrôleur par défaut indiqué par le code est `chats`. |
| `add:cmd` | `php heredia add:cmd 'add:exemple' Exemple` | Génère les trois fichiers d'une commande personnalisée : classe de commande, classe de paramètres/configuration et modèle qui exécute la logique. |
| `add:python` | `php heredia add:python salutations` | Génère un fichier Python. Pour un sous-dossier, la documentation utilise `@`, par exemple `php heredia add:python outils@salutations`. |
| `add:c` | `php heredia add:c mon_extension` | Génère le squelette d'une extension C native. La compilation exige les outils PHP/C nécessaires et des droits système adaptés. |
| `add:module` | `php heredia add:module utilisateurs Gestion_utilisateurs` | Ajoute un module pour le système de droits ; arguments `key` et `libelle`. Remplacer les espaces par des underscores. |
| `add:Right` | `php heredia add:Right utilisateurs utilisateurs@liste Voir_utilisateurs` | Ajoute un droit associé à un module et un chemin de page ; arguments `moduleKey`, `path`, `libelle`. Respecter la casse de la commande disponible localement. |
| `create:users` | `php heredia create:users identifiant mot_de_passe [groupe]` | Crée ou configure l'utilisateur/les paramètres d'accès selon la version. Arguments requis : `username`, `password` ; `userGroup` facultatif. Éviter de passer un vrai mot de passe en clair dans un terminal partagé ou dans l'historique du shell. |
| `update:users` | `php heredia update:users identifiant mot_de_passe [groupe]` | Met à jour les informations utilisateur selon le modèle de commande fourni par le framework. Commande repérée dans le dépôt consulté. |

> **Exemple prudent pour `add:sql` :** la commande exige que le répertoire logique et le fichier cible existent déjà. Ne pas utiliser un exemple arbitraire avant d'avoir examiné `bin/database/requests/` et confirmé la correspondance avec `OutputDirectory::Files($directory)`.

### 6.6 Tableau récapitulatif des noms observés

Les commandes répertoriées par la documentation et/ou le dépôt consulté sont :

`list`, `help`, `migrate`, `add:Right`, `add:c`, `add:python`, `add:cmd`, `add:module`, `add:sql`, `add:nosql`, `add:view`, `copy:table`, `create:chatbot`, `create:controller`, `create:db`, `create:front`, `create:requestfile`, `create:users`, `drop:db`, `make:api`, `make:schema`, `make:seeder`, `pip:component`, `run:server`, `server`, `update:driver`, `update:users`, `env`.

Le site et le dépôt ne sont pas parfaitement synchronisés. Utiliser la commande locale suivante pour établir l'inventaire réel :

```bash
# Source de vérité pour la version actuellement installée.
php heredia list
```

---

## 7. Vues Twig et macros d'interface

### 7.1 Création et structure d'une vue

Exemple de génération :

```bash
# Crée une vue appelée test_new_view dans le dossier main existant.
php heredia create:front main test_new_view
```

Le squelette de vue documenté ressemble à ceci :

```twig
{# Hérite du layout général choisi/configuré par le framework. #}
{% extends layouts %}

{# Importe les macros Twig utiles ; les noms de macros dépendent des fichiers du projet. #}
{% from breadcrumb import breadcrumb_field %}
{% from message import alert_msg %}
{% from forms import input_field, loader, import_field, submit %}
{% from pagination import dataTable_pagination, pagination_field %}

{% block content %}
  <h1>Exemple de page</h1>
  {# Place ici le contenu de la vue. #}
{% endblock %}
```

`@` sert de séparateur pour les dossiers/fichiers dans les chemins pris en charge par plusieurs commandes/helpers. Ne pas confondre cette convention de chemin avec une route HTTP.

### 7.2 Helpers de chemin Twig

```twig
{# Produit l'URL de la route login, selon le mapping du projet. #}
<form method="POST" action="{{ __path('login') }}">
  {# Insère le jeton CSRF associé à la session. #}
  {{ __csrf() }}
</form>

{# Produit une URL avec un paramètre de route. #}
{{ __pathId('login', {'key': value}) }}

{# Exemple de chemin avec un sous-dossier ; vérifier la convention dans le projet. #}
{{ __path('users@login') }}
```

### 7.3 Macros de formulaire documentées

Les macros sont importées depuis les widgets Twig du projet. Les noms et paramètres exacts doivent être comparés aux macros réellement présentes dans `public/layouts/widgets/`.

#### `input_field({...})`

Champs/arguments décrits : `label`, `name`, `type`, `placeholder`, `value`, `id`, `upper`, `maxlength`, `require`, `character`, `feedback`.

```twig
{# Exemple de champ texte ; adapter les paramètres aux macros de la version. #}
{{ input_field({
  label: 'Nom',
  name: 'nom',
  type: 'text',
  placeholder: 'Saisir le nom',
  value: '',
  id: 'nom',
  require: true
}) }}
```

#### `select_field({...})`

Paramètres décrits : `label`, `name`, `class`, `init`, `value`, `selected`, `datas`, `id`, `require`, `onchange`, `position`, `select2`. `datas` est une liste d'objets présentant typiquement `_id` et `label`.

```twig
{# Exemple de liste déroulante. Les clés de datas doivent correspondre à la macro. #}
{{ select_field({
  label: 'Service',
  name: 'service',
  id: 'service',
  require: true,
  datas: [
    {'_id': 1, 'label': 'Informatique'},
    {'_id': 2, 'label': 'Finances'}
  ]
}) }}
```

#### Autres macros

| Macro | Paramètres documentés / rôle |
|---|---|
| `import_field({...})` | Import de fichier : `label`, `name`, `size` (ex. `5Mo`), `accept` (ex. `.png/.jpg`), `id`, `value`, `feedback`, `placeholder`. Valider aussi le fichier côté serveur. |
| `textarea_field({...})` | Zone de texte : `label`, `name`, `class`, `value`, `maxlength`, `rows`, `feedback`, `placeholder`. |
| `checkbox_field({...})` | Case à cocher : `class`, `name`, `id`, `require`, `disabled`, `label`. |
| `radio_field({...})` | Boutons radio : `class`, `name`, `id`, `checked`, tableaux `value` et `label`. |
| `submit({...})` | Bouton d'envoi : `label`, `class`, `icon`, `color`. |
| `loader(...)` | Indicateur de chargement ; consulter sa définition pour la signature exacte. |
| `alert_msg(message, couleur)` | Affiche une alerte ; exemples de couleurs cités : `danger`, `success` (la doc écrit aussi `succes`). |
| `breadcrumb_field({...})` | Génère un fil d'Ariane à partir d'un tableau `libellé => chemin`. |
| `pagination_field(current, nbrePage, total)` | Rend les contrôles/indicateurs de pagination. |
| `dataTable_pagination(...)` | Pagination de tableaux de données ; inspecter la macro pour les arguments exacts. |
| `actions_field({...})` | Actions en lot, souvent avec `name`, `datas`, `export` et `pagination`. |

Exemple de fil d'Ariane :

```twig
{# Le dernier élément peut ne pas avoir de lien si sa valeur est null. #}
{{ breadcrumb_field({'Accueil': '/path', 'Page actuelle': null}) }}
```

Exemple d'alerte :

```twig
{# Affiche un message ; employer uniquement des contenus sûrs/échappés. #}
{{ alert_msg('Enregistrement effectué', 'success') }}
```

### 7.4 Macros de graphique

La documentation décrit une macro `charts` qui utilise un élément `canvas` et un objet de configuration. Types mentionnés : `pie`, `doughnut`, `bar`, `line`, `polarArea` et `radar`.

```twig
{# Canvas qui recevra le graphique. L'id doit être unique sur la page. #}
<canvas id="ventes_mensuelles" style="width:100%;max-width:100%"></canvas>

{# Macro de graphique ; adapter les clés exactes à la version installée. #}
{{ charts({
  id: 'ventes_mensuelles',
  label: 'Ventes mensuelles',
  orientation: 'vertical',
  position: 'bottom',
  pourcentage: false,
  datasets: [{
    chartType: 'bar',
    label: 'Ventes',
    keys: ['Janvier', 'Février', 'Mars'],
    values: [120, 135, 150],
    data: [
      {'title': 'JANVIER', 'value': 120},
      {'title': 'FÉVRIER', 'value': 135},
      {'title': 'MARS', 'value': 150}
    ],
    border: 0.5,
    radius: 0.4,
    borderColor: '#336699',
    color: ['#336699', '#6699CC', '#99CCFF']
  }]
}) }}
```

Le site mentionne aussi `echarts`, `xChart` et `scattedChart`. Il faut vérifier leurs macros avant de supposer une signature identique à `charts`.

### 7.5 Requêtes Ajax côté interface

La documentation présente les helpers `send_ajax_request` et `get_ajax_request`. Ils doivent être adaptés à la fonction et au serveur réels :

```javascript
// Envoie une requête Ajax à une URL ; sélectionner les vrais sélecteurs de la page.
send_ajax_request({
  method: 'POST',
  form: '#form',
  submit: '#button',
  msg: '#result',
  value: ['param'],
  url: 'chemin'
});

// Récupère des données et affiche le résultat dans un élément cible.
get_ajax_request({
  method: 'POST',
  msg: '#result',
  url: 'chemin',
  type: 'data_type',
  time: 'timer'
});
```

Ne pas faire confiance aux données reçues côté navigateur : vérifier la session, les droits, les champs et les valeurs dans le contrôleur côté serveur.

---

## 8. Contrôleurs, routes et layouts

### 8.1 Générer un contrôleur et une page

```bash
# Génère le squelette d'un contrôleur.
php heredia create:controller UtilisateurController

# Ajoute une méthode de page au contrôleur existant.
php heredia add:view UtilisateurController liste

# Génère une vue Twig dans un répertoire public/views déjà créé.
php heredia create:front main liste_utilisateurs
```

Le contrôleur généré est généralement placé dans `bin/controllers/controllers/`. Le mapping se trouve généralement dans `bin/controllers/controllerMap/controllerMap.php`.

Le guide décrit des méthodes de contrôleur héritant de `MainSwitchers`, avec une initialisation et un rendu du type :

```php
// Modèle indicatif : utiliser la signature réelle de la classe générée.
$this->views($html, [], false);
```

### 8.2 Mapping des contrôleurs

La page de documentation montre un tableau `controllerMap()` qui associe une clé de route à un contrôleur et à des métadonnées. Exemple **indicatif** :

```php
// Exemple de structure : ne pas copier sans adapter aux classes et constantes du projet.
return [
    'test' => [
        new testController(),
        'SwitchControllers',
        true,
        'testFolder',
        _DIR_ADMIN_TEMP_
    ],
];
```

La liste peut indiquer :

- la classe contrôleur ;
- le mécanisme de dispatch (par exemple `SwitchControllers` ou `SwitchApiControllers`) ;
- si une authentification est requise ;
- le dossier de vues ;
- un layout/template d'administration.

Les noms et l'ordre exacts doivent être repris du fichier de mapping actuel et des contrôleurs déjà fonctionnels.

### 8.3 Layouts documentés

La configuration des layouts est signalée dans `bin/epaphrodites/EpaphMozart/templatesConfig/LayoutsConfig.php`. La documentation présente notamment des méthodes comme :

- `login()` : layout de connexion, annoncé comme `layouts/template/__default_login.html.twig` ;
- `admin(?int $key = null)` : sélection de modèles d'administration selon le type d'utilisateur ;
- `forms()` : retourne le template de widgets de formulaires, annoncé comme `layouts/widgets/__widgets.forms.html.twig`.

Inspecter `LayoutsConfig.php` dans le projet avant de compter sur une clé de template précise.

---

## 9. Sécurité et contrôle d'accès

### 9.1 CSRF et méthodes HTTP

La documentation explique que les jetons CSRF sont générés par session. Exemple Twig :

```twig
{# Utiliser une méthode HTTP appropriée et le jeton CSRF fourni par le framework. #}
<form method="POST" action="{{ __path('login') }}">
  {{ __csrf() }}
  {# champs du formulaire #}
</form>
```

Des contrôles sont mentionnés dans les contrôleurs :

```php
// Exemple de vérification de la méthode HTTP ; signature à confirmer localement.
if (isValidMethod(true, 'POST')) {
    // Traiter uniquement après validation de la requête.
}

// Pour un endpoint API, vérifier aussi la validation propre à l'API.
if (isValidApiMethod()) {
    // Produire une réponse API après autorisation/validation.
}
```

Ne pas interpréter ces helpers comme un remplacement de toutes les mesures de sécurité. Garder aussi les contrôles de session et de permission, la validation côté serveur, la gestion des erreurs et les requêtes paramétrées.

### 9.2 Modules et droits

```bash
# Ajoute un module de droits. Remplacer les espaces par des underscores.
php heredia add:module utilisateurs Gestion_utilisateurs

# Ajoute un droit à un module ; le chemin peut utiliser @ comme séparateur.
# Respecter la casse exacte retournée par php heredia list.
php heredia add:Right utilisateurs utilisateurs@liste Voir_utilisateurs
```

Les listes générées sont décrites sous `bin/epaphrodites/EpaphMozart/ModulesConfig/Lists/`, avec notamment `GetModulesList.php` et `GetRightList.php`. Les noms exacts peuvent dépendre de la version.

### 9.3 Identifiants par défaut

Un guide de démarrage mentionne un identifiant administrateur initial par défaut (`admin/admin`). **Ne jamais conserver ces identifiants en production.** Changer immédiatement le mot de passe avec le mécanisme supporté par le projet, puis contrôler les droits et les comptes réellement actifs. Ne pas supposer que ces identifiants existent dans tous les projets.

---

## 10. Accès aux données avec le query builder SQL

La documentation présente une API fluide. Les exemples ci-dessous reprennent les méthodes documentées ; vérifier le pilote et la signature réelle de la version installée. Les noms de méthodes sont indiqués comme dans la documentation (`SQuery`, `IQuery`, etc.).

### 10.1 SELECT simple

```php
// Lit quelques colonnes d'une table en utilisant un paramètre lié.
$result = $this->table('user')
    ->where('group')
    ->param([$this->usersGroup])
    ->SQuery();
```

### 10.2 JOIN, tri et pagination

```php
// Exemple documenté de jointure : vérifier que les noms des colonnes sont corrects.
$result = $this->table('user')
    ->join(['actions|iduser=idactions'])
    ->where('group')
    ->limit(1, 7)
    ->offset(1, 7) // La doc indique que l'offset varie selon le pilote (notamment SQL Server/Oracle).
    ->orderBy('login', 'ASC')
    ->param([$this->usersGroup])
    ->SQuery();
```

### 10.3 COUNT et BETWEEN

```php
// Compte les lignes dont group est compris entre deux paramètres.
$result = $this->table('user')
    ->between('group')
    ->limit(1, 7)
    ->offset(1, 7)
    ->orderBy('login', 'ASC')
    ->param([1, 3])
    ->sdb(3) // Profil de connexion n°3 dans Config.ini.
    ->SQuery('count(*) as nbre');
```

### 10.4 LIKE et conditions AND

```php
// Recherche un login et filtre aussi sur l'état ; valeurs fournies par paramètres.
$result = $this->table('user')
    ->like('login')
    ->and(['state'])
    ->param(['heredia', true])
    ->sdb(7)
    ->SQuery('login, email, contact');
```

### 10.5 INSERT

```php
// Insère une ligne avec des placeholders ; ne concatène pas les valeurs reçues.
$result = $this->table('user')
    ->insert('login, email, contact')
    ->values('?, ?, ?')
    ->param(['heredia', 'heredia@example.com', '111111111'])
    ->sdb(1)
    ->IQuery();
```

### 10.6 UPDATE

```php
// Met à jour les colonnes indiquées pour la ligne ciblée par login.
$result = $this->table('user')
    ->set(['login', 'email', 'contact'])
    ->where('login')
    ->param(['heredia', 'nouveau_login', 'nouveau@example.com', '222222222'])
    ->UQuery();
```

**Précaution :** ne jamais appeler un `UPDATE` sans condition appropriée si le but est de modifier une seule ligne. Vérifier l'ordre des paramètres : valeurs de colonnes puis valeurs des conditions selon l'ordre de la clause construite.

### 10.7 DELETE

```php
// Supprime la ligne dont login correspond au paramètre.
$result = $this->table('user')
    ->where('login')
    ->param(['heredia'])
    ->DQuery();
```

**Précaution :** toujours confirmer le contenu de la clause `where` avant un `DELETE` ; tester d'abord sur une base de développement.

### 10.8 Méthodes du builder citées

| Méthode | Rôle présenté par la documentation |
|---|---|
| `table('nom')` | Sélectionne la table cible. |
| `join([...])` | Ajoute une jointure. |
| `where('colonne')` | Ajoute une condition de filtre. |
| `between('colonne')` | Ajoute une condition BETWEEN. |
| `like('colonne')` | Ajoute une recherche LIKE. |
| `and([...])` | Ajoute des conditions logiques AND. |
| `limit(...)` | Définit une limite de résultats. |
| `offset(...)` | Définit un décalage de résultats pour les pilotes pris en charge. |
| `orderBy('colonne', 'ASC')` | Trie les résultats. |
| `param([...])` | Associe les valeurs aux placeholders. |
| `sdb(n)` | Sélectionne le profil de connexion n. |
| `SQuery(...)` | Exécute une lecture / SELECT. |
| `insert(...)` | Définit les colonnes d'insertion. |
| `values(...)` | Définit les placeholders/valeurs d'insertion. |
| `IQuery()` | Exécute une insertion. |
| `set([...])` | Définit les colonnes à mettre à jour. |
| `UQuery()` | Exécute une mise à jour. |
| `DQuery()` | Exécute une suppression. |

Ne pas interpoler directement une entrée utilisateur dans une chaîne SQL. Les noms de table/colonne doivent être contrôlés eux aussi, car les identifiants SQL ne se paramètrent pas nécessairement comme les valeurs.

---

## 11. Exemples NoSQL documentés

Les exemples ci-dessous sont présentés par la documentation Epaphrodites. Les index `db(n)` doivent correspondre à une configuration réelle dans `Config.ini`.

### 11.1 MongoDB — lecture

```php
// Récupère au plus 10 documents d'une collection, en sautant le premier résultat.
$cursor = $this->db(3)
    ->selectCollection('user')
    ->find(['group' => 2], ['limit' => 10, 'skip' => 1]);
```

### 11.2 MongoDB — insertion

```php
// Insère un document dans la collection user.
$document = [
    'login' => 'heredia',
    'email' => 'heredia@example.com',
    'contact' => '111111111'
];

$result = $this->db(3)
    ->selectCollection('user')
    ->insertOne($document);
```

### 11.3 MongoDB — modification et suppression

La documentation Epaphrodites montre l'appel suivant pour la modification :

```php
// Exemple tel que présenté par le site ; NE PAS le copier sans validation.
// L'ordre des arguments et l'opérateur de mise à jour doivent être vérifiés
// contre la version du pilote MongoDB installée.
$this->db(3)->selectCollection('user')->updateMany($document, $filter);
```

Cet exemple semble ambigu au regard de l'API native MongoDB (les arguments sont normalement filtre puis document de mise à jour, avec des opérateurs tels que `$set`). **Vérifier l'API réellement exposée par Epaphrodites et la version du pilote avant de l'utiliser.** Exemple de forme native MongoDB typique, à ne prendre que si le wrapper utilise l'API native :

```php
// Modèle d'API native MongoDB : filtre en premier, puis opérateur de mise à jour.
$filter = ['login' => 'heredia'];
$update = ['$set' => ['email' => 'nouveau@example.com']];
$result = $this->db(3)->selectCollection('user')->updateMany($filter, $update);
```

Suppression documentée :

```php
// Supprime tous les documents correspondant au login précisé ; contrôler le filtre.
$result = $this->db(3)
    ->selectCollection('user')
    ->deleteMany(['login' => 'heredia']);
```

### 11.4 Redis

```php
// Lecture : récupère une plage de valeurs associée à la clé/actions.
$result = $this->key('actions')
    ->index('heredia')
    ->all()
    ->rlimit(1, 7)
    ->redisGet(2);

// Écriture : ajoute/enregistre un document dans Redis selon les règles du wrapper.
$document = ['login' => 'heredia', 'email' => 'heredia@example.com'];
$result = $this->key('user')
    ->id('iduser')
    ->index('heredia')
    ->param($document)
    ->addToRedis(3);

// Mise à jour d'une entrée existante, selon l'API du wrapper.
$result = $this->key('user')
    ->index('heredia')
    ->rset($document)
    ->updRedis(3);

// Suppression de l'entrée ciblée par l'index.
$result = $this->key('user')
    ->index('heredia')
    ->redisDel(2);
```

Les numéros passés aux opérations Redis sont les indices de connexion configurés dans le projet. Ne pas supposer qu'ils correspondent forcément aux numéros utilisés pour les bases SQL sans vérifier `Config.ini`.

---

## 12. Fichiers JSON et TOML

La documentation décrit des services pour lire, ajouter, modifier et supprimer des données de fichiers. Les chemins et signatures doivent être vérifiés dans le projet avant utilisation.

### 12.1 JSON

```php
// Initialise le service JSON ; certains contrôleurs peuvent l'obtenir via initNamespace().
$json = new \Epaphrodites\epaphrodites\env\json\Json();

// Ajoute des données au fichier indiqué.
$result = $json->path('file.json')->add([
    'driver' => 'sqlite',
    'server' => '127.0.0.1'
]);

// Lit des données/paramètres du fichier.
$result = $json->path('file.json')->get(['driver' => 'sqlite']);

// Supprime les entrées sélectionnées par la condition.
$result = $json->path('file.json')
    ->where(['driver' => 'sqlite'])
    ->delete();

// Met à jour les données correspondant à la condition.
$result = $json->path('file.json')
    ->where(['driver' => 'sqlite'])
    ->update(['server' => '127.0.0.2']);
```

### 12.2 TOML

```php
// Initialise le service TOML.
$toml = new \Epaphrodites\epaphrodites\env\toml\Toml();

// Ajoute des valeurs à la section database du fichier TOML.
$result = $toml->path('file.toml')
    ->section('database')
    ->add(['server' => '127.0.0.1', 'driver' => 'sqlite']);

// Lit la section/les valeurs demandées.
$result = $toml->path('file.toml')
    ->section('database')
    ->get(['driver' => 'sqlite']);

// Supprime la section configurée selon le comportement du service.
$result = $toml->path('file.toml')
    ->section('database')
    ->delete();

// Modifie une valeur et demande une mise à jour de la section.
$result = $toml->path('file.toml')
    ->section('database')
    ->set(['server' => '127.0.0.2'])
    ->update(['driver' => 'sqlite']);
```

La documentation mentionne aussi la possibilité d'accéder à certains de ces services via `static::initNamespace()['json']` et `static::initNamespace()['toml']`, selon le contexte applicatif.

---

## 13. API HTTP

### 13.1 Générer une méthode API

```bash
# Génère une méthode API portant ce nom dans le contrôleur API prévu.
php heredia make:api liste_utilisateurs
```

Le guide illustre une réponse JSON à partir du contrôleur, avec une forme équivalente à :

```php
// Exemple indicatif : utiliser la classe/propriété Response effectivement générée.
$this->Response->JsonResponse($code, $Result);
```

Vérifier `isValidApiMethod()` avant de traiter la requête si cette méthode fait partie du squelette disponible. Valider les entrées, les droits d'accès et les codes HTTP ; ne pas retourner d'erreurs internes ou de secrets.

---

## 14. Chatbot

La commande documentée est :

```bash
# Génère les fichiers de configuration d'un chatbot.
php heredia create:chatbot assistance
```

Le contrôleur peut être fourni en argument facultatif si cette option est prise en charge par la version. Le code consulté utilise le contrôleur `chats` par défaut et crée des fichiers JSON de configuration dans une arborescence liée à `modelOne/customize/`.

La documentation décrit plusieurs modes internes :

- modèle un, associé à des fichiers/configurations JSON ;
- modèle deux, appuyé sur des composants Python ;
- appels illustratifs `herediaBotModeleOne(...)` et `chatBotModeleTwoProcess(...)` depuis des services initialisés par le framework.

Ces méthodes sont spécifiques au projet/version. Avant de les utiliser, examiner le contrôleur généré, les fichiers JSON et les méthodes disponibles dans `initNamespace()`.

---

## 15. Python

### 15.1 Créer un fichier Python

```bash
# Génère un composant Python.
php heredia add:python salutations

# Exemple avec un sous-dossier, selon la convention @ documentée.
php heredia add:python outils@salutations

# Installe les bibliothèques Python déclarées pour le projet.
php heredia pip:component
```

Le site situe les fichiers Python générés sous `bin/epaphrodites/python/`. Il cite aussi un fichier `bin/epaphrodites/python/config/config.json`, avec une configuration de la forme :

```json
{
  "hello_world": {
    "script": "hello_world.py",
    "function": "func_hello_world"
  }
}
```

Exécution illustrative depuis PHP :

```php
// Exécute la fonction Python référencée par la clé hello_world, sans argument.
$python = new \Epaphrodites\epaphrodites\translate\PythonCodesTranslate();
$result = $python->executePython('hello_world', []);

// Dans certains contextes, le service peut être accessible depuis initConfig().
// $result = static::initConfig()['python']->executePython('hello_world', []);
```

La fonction référencée doit exister dans le script Python. Traiter les entrées et sorties comme non fiables et éviter d'exécuter des fichiers arbitraires fournis par un utilisateur.

---

## 16. Extensions natives C

```bash
# Génère la structure de fichiers d'une extension C nommée mon_extension.
php heredia add:c mon_extension
```

Le guide décrit des fichiers de configuration/compilation sous `bin/epaphrodites/cbuild/config/<nom>/` et du code C sous `bin/epaphrodites/cFunctions/`. Dans le dossier qui contient le script `kouadio`, les instructions publiées comprennent des commandes de ce type :

```bash
# Rend le script de compilation exécutable.
chmod +x kouadio

# Exemple documenté pour compiler et installer une extension nommée mon_extension.
# Vérifier le répertoire courant, les prérequis et le contenu du script avant sudo.
sudo ./kouadio mon_extension -g

# Exemple documenté pour insérer/charger l'extension selon le script du projet.
sudo ./kouadio mon_extension -i

# Affiche les extensions PHP chargées.
php -m

# Affiche les informations de configuration PHP.
php -i

# Vérifie les outils de développement PHP.
phpize --version
php-config --version
```

`sudo` exécute le script avec des privilèges élevés : n'utiliser que si le script local a été inspecté et si les opérations sont comprises. Les options `-g` et `-i` viennent de la documentation du script `kouadio` et peuvent dépendre de la version.

---

## 17. E-mail

La documentation indique un fichier `bin/config/email.ini` avec une section `[email]` qui peut contenir : `SERVER`, `USER`, `PASSWORD`, `PORT`, `HIDE_EMAIL` et `TITLE`.

Exemple de structure fictive :

```ini
[email]
SERVER = smtp.example.com
USER = utilisateur@example.com
PASSWORD = remplacer_ce_secret
PORT = 587
HIDE_EMAIL = true
TITLE = Mon application
```

Le site indique aussi une constante de méthode d'envoi dans `SetDirectory.php`, avec `php` comme valeur par défaut et `python` comme autre option mentionnée :

```php
// Exemple : choisir la méthode documentée, après vérification de la version.
define('__EMAIL_METHOD__', 'php');
```

Appel de service illustré dans la documentation :

```php
// Exemple indicatif : contacts, entête, contenu et fichier/pièce jointe selon le service.
$sent = $this->initNamespace()['mail']->sendEmail(
    $contacts,
    $msgHeader,
    $msgContent,
    $file
);
```

Vérifier les paramètres SMTP, gérer les échecs d'envoi, ne pas journaliser les mots de passe et ne pas construire les destinataires à partir d'entrées non validées.

---

## 18. Import/export Excel

La documentation montre les méthodes suivantes. Leur disponibilité et leurs paramètres exacts dépendent du service chargé dans la version installée.

```php
// Importe un fichier Excel uploadé par l'utilisateur.
$result = $this->initNamespace()['import']->importExcelFiles(
    static::getFileName('__file__'),
    '__file__'
);

// Variante présentée pour les fichiers/imports volumineux avec un composant Python.
$result = $this->initNamespace()['import']->importLargeExcelFiles(
    static::getFileName('__file__'),
    '__file__'
);

// Exporte les en-têtes et le contenu vers un fichier Excel.
$result = $this->initNamespace()['export']->exportExcelFiles(
    $header,
    $content,
    $title,
    $type
);
```

Ne pas supposer que le champ `__file__` est universel : le nom doit correspondre au formulaire uploadé. Limiter le type/taille de fichier, vérifier les erreurs et ne pas ouvrir automatiquement des fichiers arbitraires côté serveur.

---

## 19. Déploiement web (rappels du guide)

- Sur Apache, activer `mod_rewrite` si les routes de l'application s'appuient sur la réécriture d'URL.
- L'extrait Nginx de la documentation utilise un fallback vers `index.php`, par exemple :
  ```nginx
  # Exemple à adapter au chemin racine et à la configuration PHP-FPM de l'hébergement.
  location / {
      try_files $uri $uri/ /index.php?$query_string;
  }
  ```
- Bloquer l'accès HTTP direct aux dossiers internes tels que `/bin/`, aux fichiers cachés et aux fichiers de configuration.
- Utiliser `_PRODUCTION_ = true` pour la configuration de production appropriée.
- Ne pas déployer avec des comptes ou mots de passe par défaut ; utiliser HTTPS, des secrets côté serveur et des droits limités pour la base.
- Configurer correctement le processus PHP, les extensions nécessaires, les journaux, les permissions de fichiers et les sauvegardes.

Cet extrait est un rappel de principe, pas un fichier de configuration complet prêt à déployer. La configuration réelle dépend de l'hébergeur et du chemin du projet.

---

## 20. Procédures pratiques recommandées

### 20.1 Créer une base et une table

```bash
# 1. Vérifier les commandes disponibles et leur syntaxe.
php heredia list
php heredia create:db --help

# 2. Créer la base sur le profil 1 (uniquement si elle n'existe pas déjà).
php heredia create:db mon_application 1

# 3. Générer la migration de table.
php heredia make:schema create_utilisateur_table

# 4. Examiner le code généré sous bin/database/gearShift/schema/.
# 5. Après validation et sauvegarde appropriée, appliquer le schéma.
php heredia migrate -u
```

La configuration utilisée au moment de la création doit permettre à Epaphrodites de se connecter au serveur SQL. En cas d'échec, inspecter `Config.ini`, le pilote PHP installé et le profil choisi avant de répéter la commande.

### 20.2 Créer une page web de bout en bout

```bash
# 1. Vérifie le nom des commandes locales.
php heredia list

# 2. Crée le contrôleur.
php heredia create:controller ProduitController

# 3. Ajoute une méthode de page au contrôleur existant.
php heredia add:view ProduitController liste

# 4. Génère le fichier Twig dans un dossier existant.
php heredia create:front main liste_produits
```

Ensuite :

- remplir la vue dans `public/views/main/` ;
- relire la méthode générée dans `bin/controllers/controllers/` ;
- vérifier le mapping dans `controllerMap.php` ;
- vérifier que le layout attendu est disponible ;
- inclure `__csrf()` pour les formulaires POST ;
- valider les entrées côté serveur et contrôler les droits ;
- tester la route en développement avant toute publication.

### 20.3 Ajouter une requête SQL

1. Repérer le dossier de requêtes et un fichier PHP correspondant à une opération similaire.
2. Vérifier que le nom de dossier peut être résolu par `OutputDirectory::Files($directory)`.
3. Vérifier le nom exact du fichier, sans extension, puis la méthode dans ce fichier.
4. Exécuter `php heredia add:sql --help` si disponible.
5. Appeler `add:sql` avec les quatre arguments documentés : `directory`, `typeRequest`, `fileLocate`, `functionName`.
6. Examiner le code inséré et tester avec une base non-production.

### 20.4 Changer ou supprimer un schéma

1. Sauvegarder la base.
2. Générer la migration `make:schema` adaptée.
3. Examiner les fichiers générés et confirmer la connexion ciblée (`db(n)`).
4. Exécuter `migrate -u` uniquement après validation. Pour le retour arrière `migrate -d`, confirmer explicitement les effets : la commande peut supprimer des tables/colonnes.
5. Vérifier la structure réelle de la base après l'opération.

---

## 21. Erreurs fréquentes et diagnostic

| Symptôme | Vérifications recommandées |
|---|---|
| `command not found` / commande inconnue | Être dans la racine du projet, vérifier la présence de `heredia`, exécuter `php heredia list`, respecter la casse de la commande. |
| Vue impossible à créer | Vérifier que le répertoire cible existe dans `public/views/`, que la syntaxe `directory viewName` est correcte et que le fichier n'existe pas déjà. |
| Contrôleur inconnu pour `add:view` | Créer le contrôleur d'abord et vérifier que son fichier PHP est présent au chemin attendu. |
| Migration refusée | Vérifier le format de nom `create_<table>_table`, `add_<colonne>_to_<table>_table` ou `drop_<table>_table`, puis examiner `makeUpGearShift.php`/`makeDownGearShift.php`. |
| Connexion à la base impossible | Vérifier `[n_CONFIGURATION]`, `DRIVER`, `DATABASE`, `HOST`, `PORT`, identifiants, extensions PHP/PDO et autorisations du compte. |
| `add:sql` ou `add:nosql` échoue | Vérifier le répertoire logique, le fichier PHP cible préexistant, le nom du fichier sans `.php`, le type de requête et la fonction. |
| Formulaire rejeté | Vérifier méthode HTTP, session active, `{{ __csrf() }}`, validation serveur, mapping de la route et permissions. |
| Python non exécuté | Vérifier Python 3, le composant installé, `config.json`, le nom de script/fonction et les erreurs retournées par la passerelle. |
| Extension C impossible à compiler | Vérifier le bon répertoire, les permissions et outils `phpize`, `php-config`, compilateur, ainsi que la compatibilité PHP de l'extension. |
| Différence entre exemples et version locale | Le site et le dépôt ne sont pas parfaitement synchronisés ; privilégier `php heredia list`, `--help` et le code généré par la version installée. |

---

## 22. Conseils pour entraîner une IA sur Epaphrodites

Utiliser ce document comme **base de référence**, et fournir au modèle un accès aux fichiers du projet si possible. Le meilleur comportement attendu est le suivant :

### Avant d'agir

- demander ou inspecter la version du framework ;
- lire `composer.json`, `SetDirectory.php`, `Config.ini` et la CLI ;
- repérer des exemples fonctionnels déjà présents ;
- annoncer les fichiers qui seront créés/modifiés et leur rôle ;
- proposer un plan minimal et vérifier les opérations destructives.

### Pour générer du code

- utiliser les générateurs Epaphrodites quand ils sont disponibles ;
- conserver les conventions de nommage et les namespaces du projet ;
- reprendre un contrôleur, une vue, une migration ou un fichier de requêtes existant comme modèle ;
- ne pas inventer les méthodes d'API d'un service sans vérifier sa classe ;
- garder les secrets dans la configuration d'environnement, jamais dans le code ou les prompts d'entraînement ;
- générer des requêtes paramétrées et vérifier les permissions serveur.

### Après l'action

- relire le diff et les fichiers générés ;
- exécuter les commandes de validation disponibles ;
- tester une route et une requête avec des données non sensibles ;
- signaler clairement les étapes manuelles ou les informations encore incertaines ;
- ne jamais prétendre qu'une commande a réussi si son résultat n'a pas été vérifié.

### Format de réponse idéal pour l'IA

Pour chaque tâche Epaphrodites, l'IA devrait restituer :

1. **Objectif** : ce qui va être créé ou modifié.
2. **Commande exacte** : avec le répertoire depuis lequel l'exécuter.
3. **Arguments** : sens de chaque argument et valeurs à remplacer.
4. **Fichiers touchés** : chemin et rôle.
5. **Code produit** : seulement si nécessaire, avec les adaptations au projet.
6. **Sécurité** : permissions, validation, secrets et risques destructifs.
7. **Contrôle après exécution** : commande ou test pour confirmer le résultat.
8. **Incertitudes** : distinguer ce qui vient de la documentation et ce qui doit être vérifié dans la version locale.

---

## 23. Sources officielles et périmètre vérifié

### Documentation Epaphrodites

- Accueil : <https://epaphrodite.org/>
- Installation / démarrage : <https://epaphrodite.org/see/start-installation/>
- Structure des dossiers : <https://epaphrodite.org/see/structure-of-directory/>
- Widgets et macros front-end : <https://epaphrodite.org/see/front-widgets/>
- Bases et concepts : <https://epaphrodite.org/see/the-basics/>
- Documentation approfondie : <https://epaphrodite.org/see/dipping-deeper/>
- Base de données : <https://epaphrodite.org/see/database/>
- Sécurité : <https://epaphrodite.org/see/security/>
- Documentation en français : <https://epaphrodite.org/see/how-to-make-in-french/>

### Code source consulté pour vérifier la CLI

- Dépôt public : <https://github.com/epaphrodites/epaphrodites>
- Registre des commandes : `bin/Console/ConsoleKernel.php`
- Configurations des arguments : répertoire `bin/epaphrodites/Console/Setting/`
- Implémentations : répertoire `bin/epaphrodites/Console/Models/`

### Limites à connaître

- Le contenu du site, la documentation affichée et le code du dépôt ne sont pas complètement synchronisés.
- Certains exemples de code publiés semblent contenir des coquilles ou des signatures ambigües, notamment dans certains exemples de migration et de mise à jour MongoDB.
- Ce document regroupe les éléments confirmés et marque explicitement les cas à contrôler. **La commande `php heredia list`, les options `--help` et le code de la version installée sont prioritaires avant d'exécuter une instruction en production.**

---

*Fin du document.*
