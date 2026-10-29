<?php

namespace Epaphrodites\database\gearShift\schema;

trait makeUpGearShift{

    public function createRolesTable()
    {
        return $this->createTable('roles', function ($table) {
            $table->addColumn('id', 'INTEGER', ['PRIMARY KEY', 'AUTO_INCREMENT']);
            $table->addColumn('code', 'VARCHAR(30)', ['NOT NULL']);
            $table->addColumn('libelle', 'VARCHAR(50)', ['NOT NULL']);
            $table->db(1);
        });
    }

    public function createUsersAccountTable()
    {
        return $this->createTable('usersaccount', function ($table) {
                $table->addColumn('_id', 'INTEGER', ['PRIMARY KEY', 'AUTO_INCREMENT']);
                $table->addColumn('login', 'TEXT' , ['NOT NULL']);
                $table->addColumn('password', 'TEXT' , ['NOT NULL']);
                $table->addColumn('namesurname', 'TEXT' , ['DEFAULT NULL']);
                $table->addColumn('contact', 'TEXT' , ['DEFAULT NULL']);
                $table->addColumn('email', 'TEXT' , ['DEFAULT NULL']);
                $table->addColumn('ip', 'TEXT' , ['DEFAULT NULL']);
                $table->addColumn('usersgroup', 'INTEGER' , ['NOT NULL', 'DEFAULT 1']);
                $table->addColumn('state', 'INTEGER' , ['NOT NULL', 'DEFAULT 1']);
                $table->addIndex('login');
                $table->db(1);
        });
    }


    public function createAnneesScolairesTable()
    {
        return $this->createTable('annees_scolaires', function ($table) {
            $table->addColumn('id', 'INTEGER', ['PRIMARY KEY', 'AUTO_INCREMENT']);
            $table->addColumn('libelle', 'VARCHAR(20)', ['NOT NULL']);
            $table->addColumn('date_debut', 'DATE', ['NOT NULL']);
            $table->addColumn('date_fin', 'DATE', ['NOT NULL']);
            $table->addColumn('type_periode', 'VARCHAR(20)', ['NOT NULL', "DEFAULT 'trimestre'"]);
            $table->addColumn('est_active', 'INTEGER', ['NOT NULL', 'DEFAULT 0']);
            $table->db(1);
        });
    }

    public function createPeriodesTable()
    {
        return $this->createTable('periodes', function ($table) {
            $table->addColumn('id', 'INTEGER', ['PRIMARY KEY', 'AUTO_INCREMENT']);
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
            $table->addColumn('id', 'INTEGER', ['PRIMARY KEY', 'AUTO_INCREMENT']);
            $table->addColumn('code', 'VARCHAR(10)', ['NOT NULL']);
            $table->addColumn('cycle', 'INTEGER', ['NOT NULL']);
            $table->addColumn('ordre', 'INTEGER', ['NOT NULL']);
            $table->db(1);
        });
    }

    public function createSeriesTable()
    {
        return $this->createTable('series', function ($table) {
            $table->addColumn('id', 'INTEGER', ['PRIMARY KEY', 'AUTO_INCREMENT']);
            $table->addColumn('code', 'VARCHAR(10)', ['NOT NULL']);
            $table->addColumn('libelle', 'VARCHAR(50)', ['NOT NULL']);
            $table->db(1);
        });
    }

    public function createClassesTable()
    {
        return $this->createTable('classes', function ($table) {
            $table->addColumn('id', 'INTEGER', ['PRIMARY KEY', 'AUTO_INCREMENT']);
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
            $table->addColumn('id', 'INTEGER', ['PRIMARY KEY', 'AUTO_INCREMENT']);
            $table->addColumn('code', 'VARCHAR(20)', ['NOT NULL']);
            $table->addColumn('libelle', 'VARCHAR(100)', ['NOT NULL']);
            $table->db(1);
        });
    }

    public function createCoefficientsTable()
    {
        return $this->createTable('coefficients', function ($table) {
            $table->addColumn('id', 'INTEGER', ['PRIMARY KEY', 'AUTO_INCREMENT']);
            $table->addColumn('niveau_id', 'INTEGER', ['NOT NULL']);
            $table->addColumn('serie_id', 'INTEGER', ['DEFAULT NULL']);
            $table->addColumn('matiere_id', 'INTEGER', ['NOT NULL']);
            $table->addColumn('coefficient', 'INTEGER', ['NOT NULL']);
            $table->db(1);
        });
    }
}

