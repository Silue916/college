<?php

namespace Epaphrodites\database\gearShift\schema;

trait makeUpGearShift{

    private function primaryKeyOptions(): array
    {
        return $this->driver(1) === 'sqlite'
            ? ['PRIMARY KEY']
            : ['PRIMARY KEY', 'AUTO_INCREMENT'];
    }

    public function createRolesTable()
    {
        return $this->createTable('roles', function ($table) {
            $table->addColumn('id', 'INTEGER', $this->primaryKeyOptions());
            $table->addColumn('code', 'VARCHAR(30)', ['NOT NULL', 'UNIQUE']);
            $table->addColumn('libelle', 'VARCHAR(50)', ['NOT NULL']);
            $table->addColumn('group_id', 'INTEGER', ['NOT NULL', 'UNIQUE']);
            $table->db(1);
        });
    }

    public function createUsersAccountTable()
    {
        return $this->createTable('usersaccount', function ($table) {
                $table->addColumn('_id', 'INTEGER', $this->primaryKeyOptions());
                $table->addColumn('login', 'VARCHAR(254)' , ['NOT NULL']);
                $table->addColumn('password', 'VARCHAR(255)' , ['NOT NULL']);
                $table->addColumn('namesurname', 'VARCHAR(150)' , ['DEFAULT NULL']);
                $table->addColumn('contact', 'VARCHAR(30)' , ['DEFAULT NULL']);
                $table->addColumn('email', 'VARCHAR(254)' , ['DEFAULT NULL']);
                $table->addColumn('ip', 'VARCHAR(45)' , ['DEFAULT NULL']);
                $table->addColumn('usersgroup', 'INTEGER' , ['NOT NULL', 'DEFAULT 1']);
                $table->addColumn('state', 'INTEGER' , ['NOT NULL', 'DEFAULT 1']);
                $table->addColumn('otp', 'INTEGER', ['NOT NULL', 'DEFAULT 0']);
                $table->addIndex('login');
                $table->db(1);
        });
    }

    public function createUserSecurityTable()
    {
        return $this->createTable('user_security', function ($table) {
            $table->addColumn('user_id', 'INTEGER', ['PRIMARY KEY']);
            $table->addColumn('failed_attempts', 'INTEGER', ['NOT NULL', 'DEFAULT 0']);
            $table->addColumn('locked_until', 'DATETIME', ['DEFAULT NULL']);
            $table->addColumn('must_change_password', 'INTEGER', ['NOT NULL', 'DEFAULT 1']);
            $table->db(1);
        });
    }

    public function createPasswordResetTokensTable()
    {
        return $this->createTable('password_reset_tokens', function ($table) {
            $table->addColumn('id', 'INTEGER', $this->primaryKeyOptions());
            $table->addColumn('user_id', 'INTEGER', ['NOT NULL']);
            $table->addColumn('token_hash', 'CHAR(64)', ['NOT NULL', 'UNIQUE']);
            $table->addColumn('expires_at', 'DATETIME', ['NOT NULL']);
            $table->addColumn('used_at', 'DATETIME', ['DEFAULT NULL']);
            $table->db(1);
        });
    }


    public function createAnneesScolairesTable()
    {
        return $this->createTable('annees_scolaires', function ($table) {
            $table->addColumn('id', 'INTEGER', $this->primaryKeyOptions());
            $table->addColumn('libelle', 'VARCHAR(20)', ['NOT NULL', 'UNIQUE']);
            $table->addColumn('date_debut', 'DATE', ['NOT NULL']);
            $table->addColumn('date_fin', 'DATE', ['NOT NULL']);
            $table->addColumn('type_periode', 'VARCHAR(20)', ['NOT NULL', "DEFAULT 'trimestre'"]);
            $table->addColumn('est_active', 'INTEGER', ['UNIQUE', 'DEFAULT NULL']);
            $table->db(1);
        });
    }

    public function createPeriodesTable()
    {
        return $this->createTable('periodes', function ($table) {
            $table->addColumn('id', 'INTEGER', $this->primaryKeyOptions());
            $table->addColumn('annee_id', 'INTEGER', ['NOT NULL']);
            $table->addColumn('numero', 'INTEGER', ['NOT NULL']);
            $table->addColumn('date_debut', 'DATE', ['NOT NULL']);
            $table->addColumn('date_fin', 'DATE', ['NOT NULL']);
            $table->addColumn('est_cloturee', 'INTEGER', ['NOT NULL', 'DEFAULT 0']);
            $table->db(1);
        });
    }

    public function createNiveauxTable()
    {
        return $this->createTable('niveaux', function ($table) {
            $table->addColumn('id', 'INTEGER', $this->primaryKeyOptions());
            $table->addColumn('code', 'VARCHAR(10)', ['NOT NULL', 'UNIQUE']);
            $table->addColumn('cycle', 'INTEGER', ['NOT NULL']);
            $table->addColumn('ordre', 'INTEGER', ['NOT NULL']);
            $table->db(1);
        });
    }

    public function createSeriesTable()
    {
        return $this->createTable('series', function ($table) {
            $table->addColumn('id', 'INTEGER', $this->primaryKeyOptions());
            $table->addColumn('code', 'VARCHAR(10)', ['NOT NULL', 'UNIQUE']);
            $table->addColumn('libelle', 'VARCHAR(50)', ['NOT NULL']);
            $table->db(1);
        });
    }

    public function createClassesTable()
    {
        return $this->createTable('classes', function ($table) {
            $table->addColumn('id', 'INTEGER', $this->primaryKeyOptions());
            $table->addColumn('annee_id', 'INTEGER', ['NOT NULL']);
            $table->addColumn('niveau_id', 'INTEGER', ['NOT NULL']);
            $table->addColumn('serie_id', 'INTEGER', ['DEFAULT NULL']);
            $table->addColumn('nom', 'VARCHAR(50)', ['NOT NULL']);
            $table->addColumn('effectif_max', 'INTEGER', ['NOT NULL', 'DEFAULT 45']);
            $table->db(1);
        });
    }

    public function createMatieresTable()
    {
        return $this->createTable('matieres', function ($table) {
            $table->addColumn('id', 'INTEGER', $this->primaryKeyOptions());
            $table->addColumn('code', 'VARCHAR(20)', ['NOT NULL', 'UNIQUE']);
            $table->addColumn('libelle', 'VARCHAR(100)', ['NOT NULL']);
            $table->db(1);
        });
    }

    public function createCoefficientsTable()
    {
        $migration = $this->createTable('coefficients', function ($table) {
            $table->addColumn('id', 'INTEGER', $this->primaryKeyOptions());
            $table->addColumn('niveau_id', 'INTEGER', ['NOT NULL']);
            $table->addColumn('serie_id', 'INTEGER', ['DEFAULT NULL']);
            $table->addColumn(
                'serie_unique_key',
                'INTEGER GENERATED ALWAYS AS (IFNULL(serie_id, 0)) STORED'
            );
            $table->addColumn('matiere_id', 'INTEGER', ['NOT NULL']);
            $table->addColumn('coefficient', 'INTEGER', ['NOT NULL', 'CHECK (coefficient BETWEEN 1 AND 10)']);
            $table->db(1);
        });

        $migration['request'][] = 'CREATE UNIQUE INDEX uq_coefficients_niveau_serie_matiere ON coefficients (niveau_id, serie_unique_key, matiere_id)';
        return $migration;
    }

    public function createElevesTable()
    {
        return $this->createTable('eleves', function ($table) {
            $table->addColumn('id', 'INTEGER', $this->primaryKeyOptions());
            $table->addColumn('matricule', 'VARCHAR(30)', ['NOT NULL', 'UNIQUE']);
            $table->addColumn('nom', 'VARCHAR(100)', ['NOT NULL']);
            $table->addColumn('prenom', 'VARCHAR(100)', ['NOT NULL']);
            $table->addColumn('date_naissance', 'DATE', ['DEFAULT NULL']);
            $table->addColumn('sexe', 'VARCHAR(20)', ['DEFAULT NULL']);
            $table->addColumn('classe_id', 'INTEGER', ['NOT NULL']);
            $table->addColumn('telephone', 'VARCHAR(30)', ['DEFAULT NULL']);
            $table->addColumn('email', 'VARCHAR(254)', ['DEFAULT NULL']);
            $table->addColumn('adresse', 'TEXT', ['DEFAULT NULL']);
            $table->addColumn('est_archive', 'INTEGER', ['NOT NULL', 'DEFAULT 0']);
            $table->db(1);
        });
    }
}