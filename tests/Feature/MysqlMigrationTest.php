<?php

namespace tests\Feature;

use Epaphrodites\database\gearShift\makeGearShift;
use Epaphrodites\database\seeders\databaseSeeding;
use Epaphrodites\services\AuthenticationService;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

class MysqlMigrationTest extends TestCase
{
    #[Group('mysql-migration')]
    public function testMigrationsAndSeedersRunOnAnIsolatedMysqlTestDatabase(): void
    {
        $dsn = getenv('MYSQL_TEST_DSN');
        if ($dsn === false || $dsn === '') {
            self::markTestSkipped('MYSQL_TEST_DSN is unset; MySQL integration is run in CI.');
        }

        if (!str_contains(strtolower($dsn), 'dbname=epaphrodites_test')) {
            self::fail('MYSQL_TEST_DSN must target the isolated epaphrodites_test database.');
        }

        require_once dirname(__DIR__, 2) . '/bin/config/SetDirectory.php';

        $connection = new PDO(
            $dsn,
            getenv('MYSQL_TEST_USER') ?: '',
            getenv('MYSQL_TEST_PASSWORD') ?: '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $previousDriver = getenv('1DB_DRIVER');
        putenv('1DB_DRIVER=mysql');

        try {
            $connection->exec(
                'CREATE TABLE IF NOT EXISTS roles (
                    id INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    code VARCHAR(30) NOT NULL UNIQUE,
                    libelle VARCHAR(50) NOT NULL
                )'
            );

            foreach ((new makeGearShift())->up() as $migration) {
                foreach ($migration['request'] as $statement) {
                    $connection->exec($statement);
                }
            }

            (new databaseSeeding($connection, [
                'admin_email' => 'admin@example.test',
                'admin_password' => 'TestAdmin123',
                'app_url' => 'https://school.example.test',
            ]))->sqlRun();

            self::assertSame(
                7,
                (int) $connection->query('SELECT COUNT(*) FROM niveaux')->fetchColumn()
            );
            self::assertSame(
                3,
                (int) $connection->query('SELECT COUNT(*) FROM periodes')->fetchColumn()
            );
            self::assertSame(
                1,
                (int) $connection->query(
                    "SELECT COUNT(*) FROM information_schema.tables
                     WHERE table_schema = DATABASE() AND table_name = 'dossiers_preinscription'"
                )->fetchColumn()
            );
            self::assertSame(
                7,
                (int) $connection->query(
                    "SELECT group_id FROM roles WHERE code = 'eleve'"
                )->fetchColumn()
            );
            self::assertNotNull(
                (new AuthenticationService($connection))->authenticate(
                    'admin@example.test',
                    'TestAdmin123'
                )
            );
        } finally {
            putenv($previousDriver === false
                ? '1DB_DRIVER'
                : '1DB_DRIVER=' . $previousDriver);
        }
    }
}
