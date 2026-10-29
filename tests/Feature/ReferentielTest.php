<?php

namespace tests\Feature;

use DateTimeImmutable;
use Epaphrodites\database\gearShift\makeGearShift;
use Epaphrodites\database\seeders\databaseSeeding;
use Epaphrodites\services\ReferentielService;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ReferentielTest extends TestCase
{
    private PDO $connection;
    private ReferentielService $service;
    private string|false $previousDriver;

    protected function setUp(): void
    {
        require_once dirname(__DIR__, 2) . '/bin/config/SetDirectory.php';

        $this->previousDriver = getenv('1DB_DRIVER');
        putenv('1DB_DRIVER=sqlite');

        $this->connection = new PDO('sqlite::memory:');
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        foreach ((new makeGearShift())->up() as $migration) {
            foreach ($migration['request'] as $statement) {
                $this->connection->exec($statement);
            }
        }

        putenv($this->previousDriver === false
            ? '1DB_DRIVER'
            : '1DB_DRIVER=' . $this->previousDriver);

        $this->service = new ReferentielService($this->connection);
        (new databaseSeeding($this->connection, [
            'admin_email' => 'admin@example.test',
            'admin_password' => 'TestAdmin123',
            'app_url' => 'https://school.example.test',
        ]))->sqlRun();
    }

    public function testSqliteMigrationsAndReferenceSeederAreCompatibleAndRepeatable(): void
    {
        self::assertSame(7, (int) $this->connection->query('SELECT COUNT(*) FROM niveaux')->fetchColumn());
        self::assertSame(5, (int) $this->connection->query('SELECT COUNT(*) FROM series')->fetchColumn());
        self::assertSame(13, (int) $this->connection->query('SELECT COUNT(*) FROM matieres')->fetchColumn());
        self::assertSame(6, (int) $this->connection->query('SELECT COUNT(*) FROM roles')->fetchColumn());
        self::assertSame(3, (int) $this->connection->query('SELECT COUNT(*) FROM periodes')->fetchColumn());
        $roleGroups = $this->connection->query(
            "SELECT group_id FROM roles WHERE code != 'admin' ORDER BY group_id"
        )->fetchAll(PDO::FETCH_COLUMN);
        self::assertSame([4, 5, 6, 7, 8], array_map('intval', $roleGroups));

        (new databaseSeeding($this->connection, [
            'admin_email' => 'admin@example.test',
            'admin_password' => 'TestAdmin123',
            'app_url' => 'https://school.example.test',
        ]))->sqlRun();

        self::assertSame(7, (int) $this->connection->query('SELECT COUNT(*) FROM niveaux')->fetchColumn());
        self::assertSame(3, (int) $this->connection->query('SELECT COUNT(*) FROM periodes')->fetchColumn());
        self::assertSame('2026-2027', $this->service->getAnneeActive()['libelle']);
    }

    public function testSeederActivatesSeedYearOnlyWhenNoYearIsActive(): void
    {
        $this->connection->exec(
            'UPDATE annees_scolaires SET est_active = NULL WHERE est_active = 1'
        );
        (new databaseSeeding($this->connection, [
            'admin_email' => 'admin@example.test',
            'admin_password' => 'TestAdmin123',
            'app_url' => 'https://school.example.test',
        ]))->sqlRun();

        self::assertSame('2026-2027', $this->service->getAnneeActive()['libelle']);
    }

    public function testSeederDoesNotReplaceAnAlreadyActiveYear(): void
    {
        $this->connection->exec(
            "UPDATE annees_scolaires SET est_active = NULL WHERE libelle = '2026-2027'"
        );
        $this->connection->exec(
            "INSERT INTO annees_scolaires (libelle, date_debut, date_fin, type_periode, est_active)
             VALUES ('2027-2028', '2027-09-01', '2028-07-31', 'trimestre', 1)"
        );

        (new databaseSeeding($this->connection, [
            'admin_email' => 'admin@example.test',
            'admin_password' => 'TestAdmin123',
            'app_url' => 'https://school.example.test',
        ]))->sqlRun();

        self::assertSame('2027-2028', $this->service->getAnneeActive()['libelle']);
    }

    public function testActivatingAYearDeactivatesThePreviouslyActiveYear(): void
    {
        $insert = $this->connection->prepare(
            'INSERT INTO annees_scolaires (id, libelle, date_debut, date_fin, type_periode)
             VALUES (?, ?, ?, ?, ?)'
        );
        $insert->execute([0, '2027-2028', '2027-09-01', '2028-07-31', 'trimestre']);

        $this->service->activateYear(0);

        self::assertSame('2027-2028', $this->service->getAnneeActive()['libelle']);
        self::assertSame(
            1,
            (int) $this->connection->query(
                'SELECT COUNT(*) FROM annees_scolaires WHERE est_active = 1'
            )->fetchColumn()
        );
    }

    public function testDatabasePreventsTwoActiveSchoolYears(): void
    {
        $insert = $this->connection->prepare(
            'INSERT INTO annees_scolaires (libelle, date_debut, date_fin, type_periode, est_active)
             VALUES (?, ?, ?, ?, 1)'
        );

        $this->expectException(PDOException::class);
        $insert->execute(['2027-2028', '2027-09-01', '2028-07-31', 'trimestre']);
    }

    public function testActivatingAMissingYearPreservesTheCurrentActiveYear(): void
    {
        try {
            $this->service->activateYear(-1);
            self::fail('L’activation d’une année inexistante aurait dû échouer');
        } catch (RuntimeException $exception) {
            self::assertSame('Année scolaire introuvable', $exception->getMessage());
        }

        self::assertSame('2026-2027', $this->service->getAnneeActive()['libelle']);
    }

    public function testSecondaryCycleClassRequiresASeries(): void
    {
        $this->expectException(RuntimeException::class);
        ReferentielService::validateClassSeries(2, null);
    }

    public function testFirstCycleClassCannotHaveASeries(): void
    {
        $this->expectException(RuntimeException::class);
        ReferentielService::validateClassSeries(1, 1);
    }

    public function testValidClassSeriesCombinationsAreAccepted(): void
    {
        $this->expectNotToPerformAssertions();

        ReferentielService::validateClassSeries(1, null);
        ReferentielService::validateClassSeries(2, 1);
    }

    public function testCurrentPeriodIsSelectedByItsDateRange(): void
    {
        $period = $this->service->getPeriodeCourante(new DateTimeImmutable('2026-10-15'));

        self::assertSame(1, (int) $period['numero']);
    }

    public function testCoefficientIsSelectedForItsLevelAndSeries(): void
    {
        $insert = $this->connection->prepare(
            'INSERT INTO coefficients (niveau_id, serie_id, matiere_id, coefficient)
             VALUES (?, ?, ?, ?)'
        );
        $insert->execute([1, null, 1, 3]);
        $insert->execute([5, 1, 1, 5]);

        self::assertSame(3, $this->service->getCoefficient(1, null, 1));
        self::assertSame(5, $this->service->getCoefficient(5, 1, 1));
    }

    public function testMissingCoefficientRaisesAnExplicitException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Aucun coefficient défini');

        $this->service->getCoefficient(1, null, 1);
    }

    protected function tearDown(): void
    {
        putenv($this->previousDriver === false
            ? '1DB_DRIVER'
            : '1DB_DRIVER=' . $this->previousDriver);

        parent::tearDown();
    }
}
