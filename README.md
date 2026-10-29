# Gestion scolaire (Epaphrodites)

## Prérequis
- PHP 8.2+ avec extensions pdo_mysql, pdo_sqlite, gd, intl, mbstring, zip
- Composer
- MySQL 8 / MariaDB 10.6+

## Installation
```bash
composer install
cp bin/config/Config.ini.example bin/config/Config.ini
# adapter les valeurs
```

## Lancement local
```bash
php heredia run:server --port=8000
```

## Tests
```bash
vendor/bin/phpunit
vendor/bin/phpstan analyse
```

## Conventions
- Branches : `feature/<tache>`, `fix/<issue>`
- Commits : messages concis en français
