<?php

declare(strict_types=1);

namespace Epaphrodites\services;

use DateTimeImmutable;
use Epaphrodites\database\query\Builders;
use PDO;
use RuntimeException;
use Throwable;

final class PreinscriptionService extends Builders
{
    private const MAX_FILE_SIZE = 2 * 1024 * 1024;
    private const UPLOAD_FIELDS = [
        'extrait_acte' => 'extrait_acte_path',
        'bulletin' => 'bulletin_path',
        'photo_identite' => 'photo_identite_path',
    ];

    public function __construct(private ?PDO $connection = null)
    {
    }

    /**
     * @return list<array{id: int, code: string, cycle: int}>
     */
    public function getNiveaux(): array
    {
        $rows = $this->connection()->query(
            'SELECT id, code, cycle FROM niveaux ORDER BY ordre'
        )->fetchAll(PDO::FETCH_ASSOC);

        return array_map(
            static fn (array $row): array => [
                'id' => (int) $row['id'],
                'code' => (string) $row['code'],
                'cycle' => (int) $row['cycle'],
            ],
            $rows
        );
    }

    /**
     * @return list<array{id: int, code: string, libelle: string}>
     */
    public function getSeries(): array
    {
        $rows = $this->connection()->query(
            'SELECT id, code, libelle FROM series ORDER BY code'
        )->fetchAll(PDO::FETCH_ASSOC);

        return array_map(
            static fn (array $row): array => [
                'id' => (int) $row['id'],
                'code' => (string) $row['code'],
                'libelle' => (string) $row['libelle'],
            ],
            $rows
        );
    }

    /**
     * @param array<string, mixed> $values
     * @param array<string, mixed> $files
     * @return array<string, string>
     */
    public function validate(
        array $values,
        array $files,
        ?DateTimeImmutable $schoolStart = null
    ): array {
        $errors = [];
        $textFields = [
            'nom' => ['Nom', 100],
            'prenoms' => ['Prénoms', 150],
            'lieu_naissance' => ['Lieu de naissance', 120],
            'sexe' => ['Sexe', 10],
            'nationalite' => ['Nationalité', 80],
            'matricule_mena' => ['Matricule MENA', 50],
            'statut_affectation' => ['Statut d’affectation', 20],
            'etablissement_origine' => ['Établissement d’origine', 150],
            'responsable_nom' => ['Nom du responsable légal', 150],
            'lien_parente' => ['Lien de parenté', 80],
            'profession' => ['Profession', 120],
            'commune' => ['Commune de résidence', 100],
        ];

        foreach ($textFields as $field => [$label, $maximum]) {
            $value = self::stringValue($values, $field);
            if (($field !== 'matricule_mena' && trim($value) === '') || mb_strlen($value) > $maximum) {
                $errors[$field] = trim($value) === ''
                    ? "$label est obligatoire."
                    : "$label ne peut pas dépasser $maximum caractères.";
            }
        }

        $birthDate = self::parseDate(self::stringValue($values, 'date_naissance'));
        if ($birthDate === null) {
            $errors['date_naissance'] = 'Indiquez une date de naissance valide.';
        } else {
            $schoolStart ??= $this->activeSchoolStart();
            $age = $birthDate->diff($schoolStart)->y;
            if ($birthDate > $schoolStart || $age < 9 || $age > 25) {
                $errors['date_naissance'] = 'L’élève doit avoir entre 9 et 25 ans à la rentrée.';
            }
        }

        if (!in_array(self::stringValue($values, 'sexe'), ['F', 'M'], true)) {
            $errors['sexe'] = 'Sélectionnez le sexe de l’élève.';
        }
        if (!in_array(self::stringValue($values, 'statut_affectation'), ['affecte', 'non_affecte'], true)) {
            $errors['statut_affectation'] = 'Sélectionnez le statut d’affectation.';
        }

        $phone = self::normalizePhone(self::stringValue($values, 'telephone'));
        if ($phone === null) {
            $errors['telephone'] = 'Saisissez un numéro ivoirien valide de 10 chiffres.';
        }

        $email = trim(self::stringValue($values, 'email'));
        if ($email !== '' && (
            mb_strlen($email) > 254
            || filter_var($email, FILTER_VALIDATE_EMAIL) === false
        )) {
            $errors['email'] = 'Indiquez une adresse e-mail valide.';
        }

        $niveauId = self::positiveInteger(self::stringValue($values, 'niveau_id'));
        $niveau = $niveauId === null ? null : $this->findNiveau($niveauId);
        if ($niveau === null) {
            $errors['niveau_id'] = 'Sélectionnez un niveau valide.';
        } else {
            $serieCode = self::stringValue($values, 'serie_id') === ''
                ? null
                : $this->findSerieCode(self::positiveInteger(self::stringValue($values, 'serie_id')));
            if ((int) $niveau['cycle'] === 1 && self::stringValue($values, 'serie_id') !== '') {
                $errors['serie_id'] = 'Aucune série n’est requise pour ce niveau.';
            } elseif ((int) $niveau['cycle'] === 2) {
                $allowedSeries = match ($niveau['code']) {
                    '2NDE' => ['A', 'C'],
                    '1ERE', 'TLE' => ['A1', 'A2', 'C', 'D'],
                    default => [],
                };
                if ($serieCode === null || !in_array($serieCode, $allowedSeries, true)) {
                    $errors['serie_id'] = 'Sélectionnez une série valide pour le niveau demandé.';
                }
            }
        }

        foreach (self::UPLOAD_FIELDS as $field => $_column) {
            $file = $files[$field] ?? null;
            $fileError = self::validateUpload($file);
            if ($fileError !== null) {
                $errors[$field] = $fileError;
            }
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $values
     * @param array<string, mixed> $files
     * @return array{numero_dossier: string, statut: string}
     */
    public function submit(array $values, array $files): array
    {
        $errors = $this->validate($values, $files);
        if ($errors !== []) {
            throw new PreinscriptionValidationException($errors);
        }

        $connection = $this->connection();
        $storedFiles = [];
        $number = null;
        try {
            foreach (self::UPLOAD_FIELDS as $field => $_column) {
                $file = $files[$field];
                $temporaryPath = $file['tmp_name'];
                if (!is_string($temporaryPath) || !is_uploaded_file($temporaryPath)) {
                    throw new RuntimeException('Une pièce jointe reçue n’est pas un téléversement valide.');
                }

                $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($temporaryPath);
                if (!is_string($mime)) {
                    throw new RuntimeException('Impossible de déterminer le type MIME d’une pièce jointe.');
                }
                $storedFiles[$field] = $this->storeFile($temporaryPath, $mime);
            }

            $timestamp = (new DateTimeImmutable())->format('Y-m-d H:i:s');
            $connection->beginTransaction();
            $insert = $connection->prepare(
                'INSERT INTO dossiers_preinscription (
                    nom, prenoms, date_naissance, lieu_naissance, sexe, nationalite,
                    matricule_mena, statut_affectation, etablissement_origine, niveau_id,
                    serie_id, responsable_nom, lien_parente, telephone, email, profession,
                    commune, extrait_acte_path, bulletin_path, photo_identite_path,
                    statut, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $insert->execute([
                trim(self::stringValue($values, 'nom')),
                trim(self::stringValue($values, 'prenoms')),
                self::stringValue($values, 'date_naissance'),
                trim(self::stringValue($values, 'lieu_naissance')),
                self::stringValue($values, 'sexe'),
                trim(self::stringValue($values, 'nationalite')),
                self::nullableString($values, 'matricule_mena'),
                self::stringValue($values, 'statut_affectation'),
                trim(self::stringValue($values, 'etablissement_origine')),
                (int) self::stringValue($values, 'niveau_id'),
                self::nullableInteger(self::stringValue($values, 'serie_id')),
                trim(self::stringValue($values, 'responsable_nom')),
                trim(self::stringValue($values, 'lien_parente')),
                (string) self::normalizePhone(self::stringValue($values, 'telephone')),
                self::nullableString($values, 'email'),
                trim(self::stringValue($values, 'profession')),
                trim(self::stringValue($values, 'commune')),
                $storedFiles['extrait_acte'],
                $storedFiles['bulletin'],
                $storedFiles['photo_identite'],
                'soumis',
                $timestamp,
                $timestamp,
            ]);

            $id = (int) $connection->lastInsertId();
            if ($id < 1) {
                throw new RuntimeException('Impossible de générer le numéro du dossier.');
            }
            $number = sprintf('PRE-%s-%05d', date('Y'), $id);
            $update = $connection->prepare(
                'UPDATE dossiers_preinscription SET numero_dossier = ? WHERE id = ?'
            );
            $update->execute([$number, $id]);
            if ($update->rowCount() !== 1) {
                throw new RuntimeException('Impossible d’attribuer un numéro au dossier.');
            }

            $connection->commit();
        } catch (Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            foreach ($storedFiles as $storedFile) {
                $this->removeStoredFile($storedFile);
            }

            throw $exception;
        }

        return ['numero_dossier' => $number, 'statut' => 'soumis'];
    }

    /**
     * @return array{numero_dossier: string, statut: string}|null
     */
    public function findForConfirmation(string $number): ?array
    {
        $statement = $this->connection()->prepare(
            'SELECT numero_dossier, statut FROM dossiers_preinscription WHERE numero_dossier = ?'
        );
        $statement->execute([$number]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row === false
            ? null
            : ['numero_dossier' => (string) $row['numero_dossier'], 'statut' => (string) $row['statut']];
    }

    public function findStatusForTracking(string $number, string $birthDate): ?string
    {
        $date = self::parseDate($birthDate);
        if ($date === null || preg_match('/^PRE-[0-9]{4}-[0-9]{5,}$/', $number) !== 1) {
            return null;
        }

        $statement = $this->connection()->prepare(
            'SELECT statut
             FROM dossiers_preinscription
             WHERE numero_dossier = ? AND date_naissance = ?'
        );
        $statement->execute([$number, $date->format('Y-m-d')]);
        $status = $statement->fetchColumn();

        return is_string($status) ? $status : null;
    }

    /**
     * @param mixed $file
     */
    private static function validateUpload(mixed $file): ?string
    {
        if (!is_array($file) || !is_int($file['error'] ?? null)) {
            return 'Le fichier est obligatoire.';
        }
        if ($file['error'] === UPLOAD_ERR_NO_FILE) {
            return 'Cette pièce jointe est obligatoire.';
        }
        if (in_array($file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            return 'Le fichier ne doit pas dépasser 2 Mo.';
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return 'Le téléversement du fichier a échoué.';
        }

        $path = $file['tmp_name'] ?? null;
        if (!is_string($path) || !is_file($path) || !is_readable($path)) {
            return 'Le fichier téléversé est invalide.';
        }
        $size = filesize($path);
        if ($size === false || $size > self::MAX_FILE_SIZE) {
            return 'Le fichier ne doit pas dépasser 2 Mo.';
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        if (!is_string($mime) || !in_array($mime, ['application/pdf', 'image/jpeg', 'image/png'], true)) {
            return 'Le fichier doit être un PDF, JPG ou PNG valide.';
        }
        if (str_starts_with($mime, 'image/') && getimagesize($path) === false) {
            return 'Le fichier image est invalide.';
        }

        return null;
    }

    private function storeFile(string $temporaryPath, string $mime): string
    {
        $directory = dirname(_ROOT_, 2) . '/epaphrodites-private/preinscriptions';
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new RuntimeException('Impossible de préparer le stockage privé des pièces jointes.');
        }
        if (!is_writable($directory)) {
            throw new RuntimeException('Le stockage privé des pièces jointes n’est pas accessible en écriture.');
        }

        $extension = match ($mime) {
            'application/pdf' => '.pdf',
            'image/jpeg' => '.jpg',
            'image/png' => '.png',
            default => throw new RuntimeException('Le type MIME de la pièce jointe est invalide.'),
        };
        $filename = bin2hex(random_bytes(24)) . $extension;
        $destination = $directory . '/' . $filename;
        if (!move_uploaded_file($temporaryPath, $destination)) {
            throw new RuntimeException('Impossible d’enregistrer une pièce jointe.');
        }
        if (!chmod($destination, 0600)) {
            unlink($destination);
            throw new RuntimeException('Impossible de protéger une pièce jointe enregistrée.');
        }

        return $filename;
    }

    private function removeStoredFile(string $filename): void
    {
        if (preg_match('/^[a-f0-9]{48}\.(pdf|jpg|png)$/', $filename) !== 1) {
            return;
        }

        $path = dirname(_ROOT_, 2) . '/epaphrodites-private/preinscriptions/' . $filename;
        if (is_file($path) && !unlink($path)) {
            throw new RuntimeException('Impossible de nettoyer une pièce jointe après échec.');
        }
    }

    private function activeSchoolStart(): DateTimeImmutable
    {
        $year = (new ReferentielService($this->connection()))->getAnneeActive();
        $date = self::parseDate((string) ($year['date_debut'] ?? ''));
        if ($date === null) {
            throw new RuntimeException('La date de rentrée de l’année active est invalide.');
        }

        return $date;
    }

    /**
     * @return array{cycle: int, code: string}|null
     */
    private function findNiveau(int $id): ?array
    {
        $statement = $this->connection()->prepare('SELECT cycle, code FROM niveaux WHERE id = ?');
        $statement->execute([$id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row === false
            ? null
            : ['cycle' => (int) $row['cycle'], 'code' => (string) $row['code']];
    }

    private function findSerieCode(?int $id): ?string
    {
        if ($id === null) {
            return null;
        }

        $statement = $this->connection()->prepare('SELECT code FROM series WHERE id = ?');
        $statement->execute([$id]);
        $code = $statement->fetchColumn();

        return is_string($code) ? $code : null;
    }

    private function connection(): PDO
    {
        $connection = $this->connection ?? $this->db(1);
        if (!$connection instanceof PDO) {
            throw new RuntimeException('Le service de préinscription nécessite une connexion SQL.');
        }

        return $connection;
    }

    private static function normalizePhone(string $phone): ?string
    {
        $phone = preg_replace('/[\s.-]+/', '', trim($phone));
        if (!is_string($phone)) {
            return null;
        }
        if (str_starts_with($phone, '+225')) {
            $phone = substr($phone, 4);
        }

        return preg_match('/^(?:01|05|07|21|25|27)[0-9]{8}$/', $phone) === 1
            ? $phone
            : null;
    }

    private static function parseDate(string $date): ?DateTimeImmutable
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        $errors = DateTimeImmutable::getLastErrors();

        return $parsed !== false
            && $parsed->format('Y-m-d') === $date
            && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
                ? $parsed
                : null;
    }

    private static function positiveInteger(string $value): ?int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT);

        return is_int($id) && $id > 0 ? $id : null;
    }

    private static function nullableInteger(string $value): ?int
    {
        return $value === '' ? null : self::positiveInteger($value);
    }

    /**
     * @param array<string, mixed> $values
     */
    private static function stringValue(array $values, string $key): string
    {
        $value = $values[$key] ?? '';

        return is_string($value) ? trim($value) : '';
    }

    /**
     * @param array<string, mixed> $values
     */
    private static function nullableString(array $values, string $key): ?string
    {
        $value = self::stringValue($values, $key);

        return $value === '' ? null : $value;
    }
}
