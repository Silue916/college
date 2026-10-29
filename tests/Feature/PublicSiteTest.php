<?php

declare(strict_types=1);

namespace tests\Feature;

use DateTimeImmutable;
use Epaphrodites\database\gearShift\makeGearShift;
use Epaphrodites\services\ContactFormService;
use Epaphrodites\services\NewsService;
use Epaphrodites\services\PreinscriptionService;
use PDO;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

class PublicSiteTest extends TestCase
{
    private PDO $connection;
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

        $this->connection->exec(
            "INSERT INTO annees_scolaires
             (libelle, date_debut, date_fin, type_periode, est_active)
             VALUES ('2026-2027', '2026-09-01', '2027-07-31', 'trimestre', 1)"
        );
        foreach ([
            ['2NDE', 2, 5],
            ['1ERE', 2, 6],
            ['6E', 1, 1],
        ] as [$code, $cycle, $ordre]) {
            $insert = $this->connection->prepare(
                'INSERT INTO niveaux (code, cycle, ordre) VALUES (?, ?, ?)'
            );
            $insert->execute([$code, $cycle, $ordre]);
        }
        foreach ([['A', 'Série A'], ['C', 'Série C'], ['D', 'Série D']] as [$code, $label]) {
            $insert = $this->connection->prepare(
                'INSERT INTO series (code, libelle) VALUES (?, ?)'
            );
            $insert->execute([$code, $label]);
        }
    }

    public function testContactHoneypotDoesNotSendOrConsumeRateLimit(): void
    {
        $sendCount = 0;
        $result = (new ContactFormService($this->connection))->submit(
            ['website' => 'spam.example'],
            '192.0.2.10',
            static function (array $contact) use (&$sendCount): bool {
                $sendCount++;
                return true;
            }
        );

        self::assertSame('ignored', $result);
        self::assertSame(0, $sendCount);
        self::assertSame(
            0,
            (int) $this->connection->query('SELECT COUNT(*) FROM contact_rate_limits')->fetchColumn()
        );
    }

    public function testFourthContactSubmissionFromOneIpIsRateLimitedForAnHour(): void
    {
        $service = new ContactFormService($this->connection);
        $sendCount = 0;
        $send = static function (array $contact) use (&$sendCount): bool {
            $sendCount++;
            return true;
        };
        $values = [
            'name' => 'Awa Exemple',
            'phone' => '+225 0102030405',
            'email' => 'awa@example.test',
            'message' => 'Demande de renseignements',
        ];
        $now = new DateTimeImmutable('2026-10-29 12:00:00');

        for ($attempt = 0; $attempt < 3; $attempt++) {
            self::assertSame(
                'sent',
                $service->submit($values, '192.0.2.10', $send, $now->modify("+$attempt minutes"))
            );
        }

        self::assertSame(
            'rate_limited',
            $service->submit($values, '192.0.2.10', $send, $now->modify('+10 minutes'))
        );
        self::assertSame(
            'sent',
            $service->submit($values, '192.0.2.11', $send, $now->modify('+10 minutes'))
        );
        self::assertSame(4, $sendCount);
    }

    public function testMySqlContactRateLimitUsesTheIsolatedIntegrationDatabase(): void
    {
        $dsn = getenv('PUBLIC_SITE_MYSQL_DSN');
        if ($dsn === false || $dsn === '') {
            self::markTestSkipped('PUBLIC_SITE_MYSQL_DSN is set only in CI.');
        }
        if (!str_contains(strtolower($dsn), 'dbname=epaphrodites_test')) {
            self::fail('PUBLIC_SITE_MYSQL_DSN must target epaphrodites_test.');
        }

        $connection = new PDO(
            $dsn,
            getenv('PUBLIC_SITE_MYSQL_USER') ?: '',
            getenv('PUBLIC_SITE_MYSQL_PASSWORD') ?: '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $ipAddress = '198.51.100.' . random_int(1, 254);
        $removeAttempts = $connection->prepare(
            'DELETE FROM contact_rate_limits WHERE ip_address = ?'
        );
        $removeAttempts->execute([$ipAddress]);
        $removeLock = $connection->prepare(
            'DELETE FROM contact_rate_limit_locks WHERE ip_address = ?'
        );
        $removeLock->execute([$ipAddress]);

        try {
            $service = new ContactFormService($connection);
            $values = [
                'name' => 'Awa Exemple',
                'phone' => '+225 0102030405',
                'email' => 'awa@example.test',
                'message' => 'Demande de renseignements',
            ];
            $now = new DateTimeImmutable('2026-10-29 12:00:00');
            $send = static fn (array $contact): bool => true;

            for ($attempt = 0; $attempt < 3; $attempt++) {
                self::assertSame(
                    'sent',
                    $service->submit($values, $ipAddress, $send, $now->modify("+$attempt minutes"))
                );
            }
            self::assertSame(
                'rate_limited',
                $service->submit($values, $ipAddress, $send, $now->modify('+10 minutes'))
            );
        } finally {
            $removeAttempts->execute([$ipAddress]);
            $removeLock->execute([$ipAddress]);
        }
    }

    public function testFutureNewsIsNotVisibleUntilItsPublicationDate(): void
    {
        $service = new NewsService($this->connection);
        $service->create(
            'Actualité publiée',
            'Contenu visible',
            '2026-10-29 11:00:00'
        );
        $futureId = $service->create(
            'Actualité future',
            'Contenu non visible',
            '2026-10-29 13:00:00'
        );
        $now = new DateTimeImmutable('2026-10-29 12:00:00');

        $published = $service->listPublished($now);

        self::assertCount(1, $published);
        self::assertSame('Actualité publiée', $published[0]['title']);
        self::assertNull($service->findPublished($futureId, $now));
    }

    public function testAdministratorCanCreateUpdateAndDeleteNews(): void
    {
        $service = new NewsService($this->connection);
        $id = $service->create(
            'Actualité initiale',
            'Texte initial',
            '2026-10-29 11:00:00'
        );

        self::assertNull($service->update(
            $id,
            'Actualité modifiée',
            'Nouveau texte',
            '2026-10-29 11:30:00'
        ));
        self::assertSame(
            'Actualité modifiée',
            $service->findPublished($id, new DateTimeImmutable('2026-10-29 12:00:00'))['title']
        );
        self::assertNull($service->delete($id));
        self::assertSame([], $service->listAll());
    }

    public function testNewsTextIsEscapedWhenRenderedInTwig(): void
    {
        $loader = new FilesystemLoader(dirname(__DIR__, 2) . '/public');
        $twig = new Environment($loader, ['autoescape' => 'html']);
        $twig->addFunction(new TwigFunction(
            '__css',
            static fn (string $asset): string => '/static/css/' . $asset . '.css'
        ));

        $html = $twig->render('views/main/actualite_ep.html', [
            'layouts' => 'layouts/template/__default.main.html.twig',
            'site' => [
                'name' => 'Établissement test',
                'logo' => '/static/img/logo.png',
                'address' => 'Abidjan',
                'phone' => '+225 0102030405',
                'phone_link' => 'tel:+2250102030405',
                'contact_email' => '',
                'social_links' => [],
            ],
            'article' => [
                'id' => 1,
                'title' => '<script>alert(1)</script>',
                'content' => '<script>alert(2)</script>',
                'image_path' => null,
                'published_at' => '2026-10-29 11:00:00',
            ],
        ]);

        self::assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        self::assertStringContainsString('&lt;script&gt;alert(2)&lt;/script&gt;', $html);
        self::assertStringNotContainsString('<script>alert(', $html);
    }

    public function testPreinscriptionRejectsInvalidPhoneAndMissingSecondCycleSeries(): void
    {
        $files = $this->validPdfFiles();
        try {
            $service = new PreinscriptionService($this->connection);
            $values = $this->validPreinscriptionValues();
            $values['telephone'] = '0012345678';
            $values['serie_id'] = '';

            $errors = $service->validate(
                $values,
                $files,
                new DateTimeImmutable('2026-09-01')
            );

            self::assertArrayHasKey('telephone', $errors);
            self::assertArrayHasKey('serie_id', $errors);
            self::assertStringContainsString('10 chiffres', $errors['telephone']);
            self::assertStringContainsString('série valide', $errors['serie_id']);
        } finally {
            $this->removeTemporaryFiles($files);
        }
    }

    public function testPreinscriptionAcceptsIvorianPhoneWithCountryCodeAndValidSeries(): void
    {
        $files = $this->validPdfFiles();
        try {
            $service = new PreinscriptionService($this->connection);
            $errors = $service->validate(
                $this->validPreinscriptionValues(),
                $files,
                new DateTimeImmutable('2026-09-01')
            );

            self::assertSame([], $errors);
        } finally {
            $this->removeTemporaryFiles($files);
        }
    }

    public function testPreinscriptionRejectsRenamedExecutableAndFilesLargerThanTwoMegabytes(): void
    {
        $files = $this->validPdfFiles();
        $fakePdf = tempnam(sys_get_temp_dir(), 'preinscription-fake-pdf-');
        $oversized = tempnam(sys_get_temp_dir(), 'preinscription-oversized-');
        self::assertIsString($fakePdf);
        self::assertIsString($oversized);
        file_put_contents($fakePdf, 'not a pdf executable payload');
        file_put_contents($oversized, str_repeat('A', 2 * 1024 * 1024 + 1));
        $files['extrait_acte']['tmp_name'] = $fakePdf;
        $files['bulletin']['tmp_name'] = $oversized;
        $files['bulletin']['size'] = filesize($oversized);

        try {
            $errors = (new PreinscriptionService($this->connection))->validate(
                $this->validPreinscriptionValues(),
                $files,
                new DateTimeImmutable('2026-09-01')
            );

            self::assertArrayHasKey('extrait_acte', $errors);
            self::assertArrayHasKey('bulletin', $errors);
            self::assertStringContainsString('PDF, JPG ou PNG', $errors['extrait_acte']);
            self::assertStringContainsString('2 Mo', $errors['bulletin']);
        } finally {
            $this->removeTemporaryFiles($files);
        }
    }

    public function testPreinscriptionAgeIsCheckedAtTheStartOfTheActiveSchoolYear(): void
    {
        $files = $this->validPdfFiles();
        try {
            $values = $this->validPreinscriptionValues();
            $values['date_naissance'] = '2020-03-01';
            $errors = (new PreinscriptionService($this->connection))->validate(
                $values,
                $files,
                new DateTimeImmutable('2026-09-01')
            );

            self::assertArrayHasKey('date_naissance', $errors);
            self::assertStringContainsString('entre 9 et 25 ans', $errors['date_naissance']);
        } finally {
            $this->removeTemporaryFiles($files);
        }
    }

    public function testTrackingRequiresBothTheDossierNumberAndBirthDate(): void
    {
        $insert = $this->connection->prepare(
            "INSERT INTO dossiers_preinscription
             (numero_dossier, nom, prenoms, date_naissance, lieu_naissance, sexe, nationalite,
              statut_affectation, etablissement_origine, niveau_id, responsable_nom, lien_parente,
              telephone, profession, commune, extrait_acte_path, bulletin_path, photo_identite_path,
              statut, created_at, updated_at)
             VALUES (?, 'Test', 'Élève', '2010-03-15', 'Abidjan', 'F', 'Ivoirienne',
              'non_affecte', 'École test', 1, 'Parent test', 'Mère', '0102030405',
              'Profession', 'Cocody', 'a.pdf', 'b.pdf', 'c.pdf', 'soumis', ?, ?)"
        );
        $insert->execute([
            'PRE-2026-00001',
            '2026-10-29 12:00:00',
            '2026-10-29 12:00:00',
        ]);
        $service = new PreinscriptionService($this->connection);

        self::assertSame('soumis', $service->findStatusForTracking('PRE-2026-00001', '2010-03-15'));
        self::assertNull($service->findStatusForTracking('PRE-2026-00001', '2010-03-16'));
        self::assertNull($service->findStatusForTracking('PRE-2026-00002', '2010-03-15'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validPreinscriptionValues(): array
    {
        $niveau = $this->connection->query(
            "SELECT id FROM niveaux WHERE code = '2NDE'"
        )->fetchColumn();
        $serie = $this->connection->query(
            "SELECT id FROM series WHERE code = 'A'"
        )->fetchColumn();

        return [
            'nom' => 'Exemple',
            'prenoms' => 'Awa',
            'date_naissance' => '2010-03-15',
            'lieu_naissance' => 'Abidjan',
            'sexe' => 'F',
            'nationalite' => 'Ivoirienne',
            'matricule_mena' => '',
            'statut_affectation' => 'non_affecte',
            'etablissement_origine' => 'École exemple',
            'niveau_id' => (string) $niveau,
            'serie_id' => (string) $serie,
            'responsable_nom' => 'Parent exemple',
            'lien_parente' => 'Mère',
            'telephone' => '+225 01 02 03 04 05',
            'email' => '',
            'profession' => 'Commerçante',
            'commune' => 'Cocody',
        ];
    }

    /**
     * @return array<string, array{error: int, tmp_name: string, size: int, name: string}>
     */
    private function validPdfFiles(): array
    {
        $files = [];
        foreach (['extrait_acte', 'bulletin', 'photo_identite'] as $field) {
            $path = tempnam(sys_get_temp_dir(), 'preinscription-pdf-');
            self::assertIsString($path);
            file_put_contents($path, "%PDF-1.4\n% test document\n");
            $files[$field] = [
                'error' => UPLOAD_ERR_OK,
                'tmp_name' => $path,
                'size' => (int) filesize($path),
                'name' => $field . '.pdf',
            ];
        }

        return $files;
    }

    /**
     * @param array<string, mixed> $files
     */
    private function removeTemporaryFiles(array $files): void
    {
        foreach ($files as $file) {
            if (is_array($file) && is_string($file['tmp_name'] ?? null) && is_file($file['tmp_name'])) {
                unlink($file['tmp_name']);
            }
        }
    }

    protected function tearDown(): void
    {
        putenv($this->previousDriver === false
            ? '1DB_DRIVER'
            : '1DB_DRIVER=' . $this->previousDriver);

        parent::tearDown();
    }
}
