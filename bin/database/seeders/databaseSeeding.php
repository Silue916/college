<?php

namespace Epaphrodites\database\seeders;

use Epaphrodites\database\query\Builders;
use Epaphrodites\services\AuthenticationConfig;
use Epaphrodites\services\AuthenticationService;
use InvalidArgumentException;
use PDO;
use RuntimeException;
use Throwable;

class databaseSeeding extends Builders{
    private ?PDO $connection;
    /** @var array{admin_email: string, admin_password: string, app_url: string}|null */
    private ?array $authenticationConfiguration;

    /**
     * @param array{admin_email: string, admin_password: string, app_url: string}|null $authenticationConfiguration
     */
    public function __construct(
        ?PDO $connection = null,
        ?array $authenticationConfiguration = null
    ) {
        $this->connection = $connection;
        $this->authenticationConfiguration = $authenticationConfiguration;
    }

    /**
     * Seed the application's database (sql/nosql).
     */
    public function noSqlRun(): void
    {
         $admin = $this->adminConfiguration();
         $hash = password_hash($admin['admin_password'], PASSWORD_DEFAULT);

         $document = [
            'login' => $admin['admin_email'],
            'password' => $hash,
            'namesurname' => 'Administrateur',
            'contact' => NULL,
            'email' => $admin['admin_email'],
            'state'=> 1,
            'usersgroup'=> 1,
          ];

          $this->db(1)->selectCollection('usersaccount')
                        ->insertOne($document);
     }

    /**
     * Seed the application's database.
     */
     public function sqlRun(): void
     {
        $connection = $this->connection ?? $this->db(1);
        if (!$connection instanceof PDO) {
            throw new RuntimeException('Le seeder SQL nécessite une connexion SQL');
        }
        $admin = $this->adminConfiguration();

        $this->ensureRoleGroupColumn($connection);
        $connection->beginTransaction();
        try {
            $niveaux = ['6E','5E','4E','3E','2NDE','1ERE','TLE'];
            foreach ($niveaux as $i => $code) {
                $this->insertIfMissing($connection, 'niveaux', [
                    'code' => $code,
                    'cycle' => $i < 4 ? 1 : 2,
                    'ordre' => $i + 1,
                ], ['code']);
            }

            $series = [
                ['A','Série A'],
                ['C','Série C'],
                ['A1','Série A1'],
                ['A2','Série A2'],
                ['D','Série D'],
            ];
            foreach ($series as [$code, $label]) {
                $this->insertIfMissing($connection, 'series', [
                    'code' => $code,
                    'libelle' => $label,
                ], ['code']);
            }

            $matieres = [
                ['FR','Français'],
                ['MAT','Mathématiques'],
                ['ANG','Anglais'],
                ['HG','Histoire-Géographie'],
                ['SVT','SVT'],
                ['PC','Physique-Chimie'],
                ['EDHC','EDHC'],
                ['EPS','EPS'],
                ['PHILO','Philosophie'],
                ['ESP','Espagnol'],
                ['ALL','Allemand'],
                ['ART','Arts plastiques'],
                ['MUS','Musique'],
            ];
            foreach ($matieres as [$code, $label]) {
                $this->insertIfMissing($connection, 'matieres', [
                    'code' => $code,
                    'libelle' => $label,
                ], ['code']);
            }

            $roles = [
                ['admin', 'Administrateur', 1],
                ['scolarite', 'Scolarité', 4],
                ['comptabilite', 'Comptabilité', 5],
                ['enseignant', 'Enseignant', 6],
                ['eleve', 'Élève', 7],
                ['parent', 'Parent', 8],
            ];
            foreach ($roles as [$code, $label, $groupId]) {
                $this->insertIfMissing($connection, 'roles', [
                    'code' => $code,
                    'libelle' => $label,
                    'group_id' => $groupId,
                ], ['code']);
                $updateRole = $connection->prepare(
                    'UPDATE roles SET libelle = ?, group_id = ? WHERE code = ?'
                );
                $updateRole->execute([$label, $groupId, $code]);
            }

            $activeYear = $connection->query(
                'SELECT id FROM annees_scolaires WHERE est_active = 1 LIMIT 1'
            )->fetchColumn();
            $this->insertIfMissing($connection, 'annees_scolaires', [
                'libelle' => '2026-2027',
                'date_debut' => '2026-09-01',
                'date_fin' => '2027-07-31',
                'type_periode' => 'trimestre',
                'est_active' => $activeYear === false ? 1 : null,
            ], ['libelle']);

            $year = $connection->prepare(
                'SELECT id FROM annees_scolaires WHERE libelle = ?'
            );
            $year->execute(['2026-2027']);
            $yearId = $year->fetchColumn();
            if ($yearId === false) {
                throw new RuntimeException('Impossible de retrouver l’année scolaire 2026-2027');
            }

            $periods = [
                [1, '2026-09-01', '2026-11-30'],
                [2, '2026-12-01', '2027-02-28'],
                [3, '2027-03-01', '2027-07-31'],
            ];
            foreach ($periods as [$number, $start, $end]) {
                $this->insertIfMissing($connection, 'periodes', [
                    'annee_id' => (int) $yearId,
                    'numero' => $number,
                    'date_debut' => $start,
                    'date_fin' => $end,
                    'est_cloturee' => 0,
                ], ['annee_id', 'numero']);
            }

            $this->seedAdministrator($connection, $admin);
            $activeYear = $connection->query(
                'SELECT id FROM annees_scolaires WHERE est_active = 1 LIMIT 1'
            )->fetchColumn();
            if ($activeYear === false) {
                $activateYear = $connection->prepare(
                    'UPDATE annees_scolaires SET est_active = 1 WHERE id = ?'
                );
                $activateYear->execute([(int) $yearId]);
            }

            $connection->commit();
        } catch (Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            throw $exception;
        }
    }

    /**
     * @param array{admin_email: string, admin_password: string, app_url: string} $admin
     */
    private function seedAdministrator(PDO $connection, array $admin): void
    {
        $existing = $connection->prepare(
            'SELECT _id FROM usersaccount WHERE login = ? OR email = ? LIMIT 1'
        );
        $existing->execute([$admin['admin_email'], $admin['admin_email']]);
        $existingId = $existing->fetchColumn();

        if ($existingId !== false) {
            $userId = (int) $existingId;
        } else {
            $hash = password_hash($admin['admin_password'], PASSWORD_DEFAULT);

            $insert = $connection->prepare(
                'INSERT INTO usersaccount
                 (login, password, namesurname, email, usersgroup, state, otp)
                 VALUES (?, ?, ?, ?, 1, 1, 0)'
            );
            $insert->execute([
                $admin['admin_email'],
                $hash,
                'Administrateur',
                $admin['admin_email'],
            ]);
            $userId = (int) $connection->lastInsertId();
        }

        $driver = $connection->getAttribute(PDO::ATTR_DRIVER_NAME);
        $statement = $driver === 'sqlite'
            ? 'INSERT OR IGNORE INTO user_security (user_id) VALUES (?)'
            : 'INSERT IGNORE INTO user_security (user_id) VALUES (?)';
        $connection->prepare($statement)->execute([$userId]);
    }

    /**
     * @return array{admin_email: string, admin_password: string, app_url: string}
     */
    private function adminConfiguration(): array
    {
        $configuration = $this->authenticationConfiguration ?? AuthenticationConfig::local();

        if (
            filter_var($configuration['admin_email'], FILTER_VALIDATE_EMAIL) === false
            || !AuthenticationService::isValidPassword($configuration['admin_password'])
        ) {
            throw new RuntimeException(
                'Config.ini doit contenir un e-mail administrateur valide et un mot de passe conforme.'
            );
        }

        return $configuration;
    }

    private function ensureRoleGroupColumn(PDO $connection): void
    {
        $driver = $connection->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $columns = $connection->query('PRAGMA table_info(roles)')->fetchAll(PDO::FETCH_ASSOC);
            $hasGroupColumn = false;
            foreach ($columns as $column) {
                if ($column['name'] === 'group_id') {
                    $hasGroupColumn = true;
                    break;
                }
            }
        } elseif ($driver === 'mysql') {
            $query = $connection->prepare(
                'SELECT 1
                 FROM information_schema.columns
                 WHERE table_schema = DATABASE()
                   AND table_name = ?
                   AND column_name = ?
                 LIMIT 1'
            );
            $query->execute(['roles', 'group_id']);
            $hasGroupColumn = $query->fetchColumn() !== false;
        } elseif ($driver === 'pgsql') {
            $query = $connection->prepare(
                'SELECT 1
                 FROM information_schema.columns
                 WHERE table_schema = current_schema()
                   AND table_name = ?
                   AND column_name = ?
                 LIMIT 1'
            );
            $query->execute(['roles', 'group_id']);
            $hasGroupColumn = $query->fetchColumn() !== false;
        } elseif ($driver === 'sqlsrv') {
            $hasGroupColumn = $connection->query(
                "SELECT COL_LENGTH('roles', 'group_id')"
            )->fetchColumn() !== null;
        } elseif (in_array($driver, ['oci', 'oracle'], true)) {
            $query = $connection->prepare(
                'SELECT 1 FROM USER_TAB_COLUMNS
                 WHERE TABLE_NAME = ? AND COLUMN_NAME = ?'
            );
            $query->execute(['ROLES', 'GROUP_ID']);
            $hasGroupColumn = $query->fetchColumn() !== false;
        } else {
            throw new RuntimeException(
                "La mise à niveau du schéma des rôles n'est pas prise en charge pour {$driver}."
            );
        }

        if (!$hasGroupColumn) {
            $alter = in_array($driver, ['oci', 'oracle'], true)
                ? 'ALTER TABLE roles ADD (group_id INTEGER NULL)'
                : ($driver === 'sqlsrv'
                    ? 'ALTER TABLE roles ADD group_id INTEGER NULL'
                    : 'ALTER TABLE roles ADD COLUMN group_id INTEGER NULL');
            $connection->exec($alter);
        }
    }

    /**
     * @param array<string, int|string|null> $values
     * @param list<string> $uniqueColumns
     */
    private function insertIfMissing(
        PDO $connection,
        string $table,
        array $values,
        array $uniqueColumns
    ): void {
        $identifiers = array_merge([$table], array_keys($values), $uniqueColumns);
        foreach ($identifiers as $identifier) {
            if (preg_match('/^[a-z_]+$/', $identifier) !== 1) {
                throw new InvalidArgumentException('Nom SQL invalide dans le seeder');
            }
        }

        $conditions = implode(' AND ', array_map(
            static fn (string $column): string => "{$column} = ?",
            $uniqueColumns
        ));
        $find = $connection->prepare("SELECT 1 FROM {$table} WHERE {$conditions} LIMIT 1");
        $find->execute(array_map(
            static fn (string $column): int|string|null => $values[$column],
            $uniqueColumns
        ));
        if ($find->fetchColumn() !== false) {
            return;
        }

        $columns = implode(', ', array_keys($values));
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $insert = $connection->prepare("INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})");
        $insert->execute(array_values($values));
    }
}