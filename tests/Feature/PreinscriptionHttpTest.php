<?php

declare(strict_types=1);

namespace tests\Feature;

use PDO;
use PHPUnit\Framework\TestCase;

class PreinscriptionHttpTest extends TestCase
{
    public function testValidSubmissionCreatesPrivateFilesAndShowsConfirmation(): void
    {
        $connection = $this->mysqlConnection();
        if ($connection === null) {
            self::markTestSkipped('PUBLIC_SITE_MYSQL_DSN is set only in CI.');
        }

        $files = $this->createPdfFiles();
        $response = '';
        $number = '';
        $storedFiles = [];
        $privateDirectory = dirname(dirname(__DIR__, 2)) . '/epaphrodites-private/preinscriptions';
        try {
            $response = $this->postForm($connection, [], $files);
            self::assertMatchesRegularExpression('/PRE-[0-9]{4}-[0-9]{5,}/', $response);
            preg_match('/PRE-[0-9]{4}-[0-9]{5,}/', $response, $matches);
            $number = $matches[0];
            $statement = $connection->prepare(
                'SELECT statut, telephone, extrait_acte_path, bulletin_path, photo_identite_path
                 FROM dossiers_preinscription WHERE numero_dossier = ?'
            );
            $statement->execute([$number]);
            $dossier = $statement->fetch(PDO::FETCH_ASSOC);
            self::assertIsArray($dossier);
            self::assertSame('soumis', $dossier['statut']);
            self::assertSame('0102030405', $dossier['telephone']);

            $storedFiles = [
                $dossier['extrait_acte_path'],
                $dossier['bulletin_path'],
                $dossier['photo_identite_path'],
            ];
            foreach ($storedFiles as $privateFile) {
                self::assertMatchesRegularExpression('/^[a-f0-9]{48}\.pdf$/', (string) $privateFile);
                self::assertFileExists($privateDirectory . '/' . $privateFile);
            }
            self::assertFalse(
                str_starts_with(
                    (string) realpath($privateDirectory),
                    (string) realpath(dirname(__DIR__, 2)) . DIRECTORY_SEPARATOR
                ),
                'Uploaded documents must be outside the project root.'
            );

            $publicUrl = rtrim(getenv('HOMEPAGE_URL') ?: 'http://127.0.0.1:8000', '/')
                . '/../epaphrodites-private/preinscriptions/' . rawurlencode((string) $storedFiles[0]);
            self::assertContains(
                $this->getStatusCode($publicUrl),
                [302, 404],
                'Private uploads must not be served from a public URL.'
            );
        } finally {
            if ($number !== '') {
                $remove = $connection->prepare(
                    'DELETE FROM dossiers_preinscription WHERE numero_dossier = ?'
                );
                $remove->execute([$number]);
                foreach ($storedFiles as $filename) {
                    if (is_string($filename)) {
                        $path = $privateDirectory . '/' . $filename;
                        if (is_file($path)) {
                            unlink($path);
                        }
                    }
                }
            }
            $this->removeTemporaryFiles($files);
        }
    }

    public function testSubmissionShowsFieldErrorsForInvalidPhoneAndMissingSeries(): void
    {
        $connection = $this->mysqlConnection();
        if ($connection === null) {
            self::markTestSkipped('PUBLIC_SITE_MYSQL_DSN is set only in CI.');
        }
        $files = $this->createPdfFiles();
        try {
            $response = $this->postForm($connection, [
                'telephone' => '0012345678',
                'serie_id' => '',
            ], $files);

            self::assertStringContainsString('Saisissez un numéro ivoirien valide de 10 chiffres.', $response);
            self::assertStringContainsString('Sélectionnez une série valide pour le niveau demandé.', $response);
        } finally {
            $this->removeTemporaryFiles($files);
        }
    }

    public function testSubmissionRejectsMismatchedAndOversizedFilesUnderTheirFields(): void
    {
        $connection = $this->mysqlConnection();
        if ($connection === null) {
            self::markTestSkipped('PUBLIC_SITE_MYSQL_DSN is set only in CI.');
        }
        $files = $this->createPdfFiles();
        $fakePdf = tempnam(sys_get_temp_dir(), 'preinscription-upload-');
        $oversized = tempnam(sys_get_temp_dir(), 'preinscription-large-');
        self::assertIsString($fakePdf);
        self::assertIsString($oversized);
        file_put_contents($fakePdf, 'not a pdf document');
        file_put_contents($oversized, str_repeat('A', 2 * 1024 * 1024 + 1));
        $files['extrait_acte'] = new \CURLFile($fakePdf, 'application/pdf', 'document.pdf');
        $files['bulletin'] = new \CURLFile($oversized, 'application/pdf', 'bulletin.pdf');

        try {
            $response = $this->postForm($connection, [], $files);

            self::assertStringContainsString('Le fichier doit être un PDF, JPG ou PNG valide.', $response);
            self::assertStringContainsString('Le fichier ne doit pas dépasser 2 Mo.', $response);
        } finally {
            $this->removeTemporaryFiles($files);
        }
    }

    public function testTrackingShowsStatusOnlyWhenNumberAndBirthDateBothMatch(): void
    {
        $connection = $this->mysqlConnection();
        if ($connection === null) {
            self::markTestSkipped('PUBLIC_SITE_MYSQL_DSN is set only in CI.');
        }
        $number = 'PRE-2099-' . random_int(10000, 99999);
        $insert = $connection->prepare(
            "INSERT INTO dossiers_preinscription
             (numero_dossier, nom, prenoms, date_naissance, lieu_naissance, sexe, nationalite,
              statut_affectation, etablissement_origine, niveau_id, responsable_nom, lien_parente,
              telephone, profession, commune, extrait_acte_path, bulletin_path, photo_identite_path,
              statut, created_at, updated_at)
             VALUES (?, 'Test', 'Élève', '2010-03-15', 'Abidjan', 'F', 'Ivoirienne',
              'non_affecte', 'École test', 1, 'Parent test', 'Mère', '0102030405',
              'Profession', 'Cocody', 'a.pdf', 'b.pdf', 'c.pdf', 'accepte', NOW(), NOW())"
        );
        $insert->execute([$number]);

        try {
            $correct = $this->postTracking($number, '2010-03-15');
            self::assertStringContainsString('Statut :', $correct);
            self::assertStringContainsString('accepte', $correct);

            $wrongDate = $this->postTracking($number, '2010-03-16');
            self::assertStringContainsString('Aucun dossier ne correspond aux informations saisies.', $wrongDate);
            self::assertStringNotContainsString('Statut :', $wrongDate);

            $wrongNumber = $this->postTracking('PRE-2099-00000', '2010-03-15');
            self::assertStringContainsString('Aucun dossier ne correspond aux informations saisies.', $wrongNumber);
            self::assertStringNotContainsString('Statut :', $wrongNumber);
        } finally {
            $delete = $connection->prepare(
                'DELETE FROM dossiers_preinscription WHERE numero_dossier = ?'
            );
            $delete->execute([$number]);
        }
    }

    /**
     * @param array<string, string> $overrides
     * @param array<string, \CURLFile> $files
     */
    private function postForm(PDO $connection, array $overrides, array $files): string
    {
        $level = $connection->query("SELECT id FROM niveaux WHERE code = '2NDE'")->fetchColumn();
        $series = $connection->query("SELECT id FROM series WHERE code = 'A'")->fetchColumn();
        self::assertNotFalse($level);
        self::assertNotFalse($series);
        $values = array_merge([
            'nom' => 'Exemple',
            'prenoms' => 'Awa',
            'date_naissance' => '2010-03-15',
            'lieu_naissance' => 'Abidjan',
            'sexe' => 'F',
            'nationalite' => 'Ivoirienne',
            'matricule_mena' => '',
            'statut_affectation' => 'non_affecte',
            'etablissement_origine' => 'École exemple',
            'niveau_id' => (string) $level,
            'serie_id' => (string) $series,
            'responsable_nom' => 'Parent exemple',
            'lien_parente' => 'Mère',
            'telephone' => '+225 01 02 03 04 05',
            'email' => '',
            'profession' => 'Commerçante',
            'commune' => 'Cocody',
        ], $overrides, $files);

        return $this->postWithCsrf('/preinscription/', $values);
    }

    private function postTracking(string $number, string $birthDate): string
    {
        return $this->postWithCsrf('/suivi_preinscription/', [
            'numero_dossier' => $number,
            'date_naissance' => $birthDate,
        ]);
    }

    /**
     * @param array<string, string|\CURLFile> $fields
     */
    private function postWithCsrf(string $path, array $fields): string
    {
        $baseUrl = rtrim(getenv('HOMEPAGE_URL') ?: 'http://127.0.0.1:8000', '/');
        $cookieJar = tempnam(sys_get_temp_dir(), 'preinscription-cookie-');
        self::assertIsString($cookieJar);
        $handle = curl_init($baseUrl . $path);
        self::assertIsObject($handle);
        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_COOKIEJAR => $cookieJar,
            CURLOPT_COOKIEFILE => $cookieJar,
        ]);
        $form = curl_exec($handle);
        self::assertSame(200, curl_getinfo($handle, CURLINFO_HTTP_CODE), curl_error($handle));
        self::assertIsString($form);
        self::assertSame(
            1,
            preg_match('/name=[\'"]token_csrf[\'"] value=[\'"]([^\'"]+)[\'"]/', $form, $matches),
            'The public form must contain its CSRF token.'
        );
        $fields['token_csrf'] = $matches[1];
        curl_setopt_array($handle, [
            CURLOPT_URL => $baseUrl . $path,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $fields,
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        $response = curl_exec($handle);
        $status = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $error = curl_error($handle);
        curl_close($handle);
        unlink($cookieJar);

        self::assertSame(200, $status, $error);
        self::assertIsString($response);

        return $response;
    }

    private function getStatusCode(string $url): int
    {
        $handle = curl_init($url);
        self::assertIsObject($handle);
        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);
        curl_exec($handle);
        $status = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);

        return $status;
    }

    /**
     * @return array<string, \CURLFile>
     */
    private function createPdfFiles(): array
    {
        $files = [];
        foreach (['extrait_acte', 'bulletin', 'photo_identite'] as $field) {
            $path = tempnam(sys_get_temp_dir(), 'preinscription-upload-');
            self::assertIsString($path);
            file_put_contents($path, "%PDF-1.4\n% Test document\n");
            $files[$field] = new \CURLFile($path, 'application/pdf', $field . '.pdf');
        }

        return $files;
    }

    /**
     * @param array<string, \CURLFile> $files
     */
    private function removeTemporaryFiles(array $files): void
    {
        foreach ($files as $file) {
            $path = $file->getFilename();
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    private function mysqlConnection(): ?PDO
    {
        $dsn = getenv('PUBLIC_SITE_MYSQL_DSN');
        if ($dsn === false || $dsn === '') {
            return null;
        }
        if (!str_contains(strtolower($dsn), 'dbname=epaphrodites_test')) {
            self::fail('PUBLIC_SITE_MYSQL_DSN must target epaphrodites_test.');
        }

        return new PDO(
            $dsn,
            getenv('PUBLIC_SITE_MYSQL_USER') ?: '',
            getenv('PUBLIC_SITE_MYSQL_PASSWORD') ?: '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }
}
