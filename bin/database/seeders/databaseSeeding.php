<?php

namespace Epaphrodites\database\seeders;

use Epaphrodites\database\query\Builders;

class databaseSeeding extends Builders{

    /**
     * Seed the application's database (sql/nosql).
     */    
    public function noSqlRun(): void
    {
         $document =
          [
            'login' => 'admin',
            'password' => static::initConfig()['guard']->CryptPassword('admin'),
            'namesurname' => NULL,
            'contact' => NULL,
            'email' => NULL,
            'state'=> 1,
            'group'=> 1,
          ];
      
          $this->db(1)->selectCollection('usersaccount')
                        ->insertOne($document);        
     }

    /**
     * Seed the application's database.
     */    
     public function sqlRun(): void
     {
        $niveaux = ['6E','5E','4E','3E','2NDE','1ERE','TLE'];
        foreach ($niveaux as $i => $code) {
            $this->table('niveaux')
                ->insert('code, cycle, ordre')
                ->values('?, ?, ?')
                ->param([$code, $i < 4 ? 1 : 2, $i + 1])
                ->sdb(1)
                ->IQuery();
        }

        $series = [['A','Série A'],['C','Série C'],['A1','Série A1'],['A2','Série A2'],['D','Série D']];
        foreach ($series as $s) {
            $this->table('series')
                ->insert('code, libelle')
                ->values('?, ?')
                ->param($s)
                ->sdb(1)
                ->IQuery();
        }

        $matieres = ['Français','Mathématiques','Anglais','Histoire-Géographie','SVT','Physique-Chimie','EDHC','EPS','Philosophie','Espagnol','Allemand','Arts plastiques','Musique'];
        foreach ($matieres as $m) {
            $this->table('matieres')
                ->insert('code, libelle')
                ->values('?, ?')
                ->param([strtoupper(substr($m,0,4)), $m])
                ->sdb(1)
                ->IQuery();
        }

        $this->table('annees_scolaires')
            ->insert('libelle, date_debut, date_fin, type_periode, est_active')
            ->values('?, ?, ?, ?, ?')
            ->param(['2026-2027','2026-09-01','2027-07-31','trimestre',1])
            ->sdb(1)
            ->IQuery();
     }    
}