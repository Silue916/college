<?php

namespace Epaphrodites\services;

use Epaphrodites\database\query\Builders;
use RuntimeException;

final class StudentsService extends Builders
{
    private const STUDENT_COLUMNS = 'id, matricule, nom, prenom, date_naissance, sexe, classe_id, telephone, email, adresse, est_archive';

    public function listStudents(bool $archived, int $offset, int $limit): array
    {
        $result = $this->table('eleves')
            ->where('est_archive')
            ->param([$archived ? 1 : 0])
            ->sdb(1)
            ->orderBy('id', 'DESC')
            ->limit($offset, $limit)
            ->SQuery(self::STUDENT_COLUMNS);

        if (!is_array($result)) {
            throw new RuntimeException('Unable to load students.');
        }

        return $result;
    }

    public function countStudents(bool $archived): int
    {
        $result = $this->table('eleves')
            ->where('est_archive')
            ->param([$archived ? 1 : 0])
            ->sdb(1)
            ->SQuery('COUNT(*) AS total');

        if (!is_array($result) || !isset($result[0]['total'])) {
            throw new RuntimeException('Unable to count students.');
        }

        return (int) $result[0]['total'];
    }

    public function getClasses(): array
    {
        $result = $this->table('classes')
            ->orderBy('nom', 'ASC')
            ->sdb(1)
            ->SQuery('id, nom');

        if (!is_array($result)) {
            throw new RuntimeException('Unable to load classes.');
        }

        return $result;
    }

    public function getStudent(int $id): ?array
    {
        $result = $this->table('eleves')
            ->where('id')
            ->param([$id])
            ->sdb(1)
            ->SQuery(self::STUDENT_COLUMNS);

        if (!is_array($result)) {
            throw new RuntimeException('Unable to load the student.');
        }

        return isset($result[0]) && is_array($result[0]) ? $result[0] : null;
    }

    public function classExists(int $classId): bool
    {
        $result = $this->table('classes')
            ->where('id')
            ->param([$classId])
            ->sdb(1)
            ->SQuery('id');

        if (!is_array($result)) {
            throw new RuntimeException('Unable to verify the selected class.');
        }

        return $result !== [];
    }

    public function isMatriculeAvailable(string $matricule, ?int $exceptId = null): bool
    {
        $result = $this->table('eleves')
            ->where('matricule')
            ->param([$matricule])
            ->sdb(1)
            ->SQuery('id');

        if (!is_array($result)) {
            throw new RuntimeException('Unable to verify the matricule.');
        }

        foreach ($result as $student) {
            if ((int) ($student['id'] ?? 0) !== $exceptId) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string, int|string|null> $student
     */
    public function saveStudent(array $student, ?int $id = null): bool
    {
        if ($id === null) {
            $result = $this->table('eleves')
                ->insert('matricule, nom, prenom, date_naissance, sexe, classe_id, telephone, email, adresse, est_archive')
                ->values('?, ?, ?, ?, ?, ?, ?, ?, ?, ?')
                ->param([
                    $student['matricule'],
                    $student['nom'],
                    $student['prenom'],
                    $student['date_naissance'],
                    $student['sexe'],
                    $student['classe_id'],
                    $student['telephone'],
                    $student['email'],
                    $student['adresse'],
                    0,
                ])
                ->sdb(1)
                ->IQuery();
        } else {
            $result = $this->table('eleves')
                ->set([
                    'matricule',
                    'nom',
                    'prenom',
                    'date_naissance',
                    'sexe',
                    'classe_id',
                    'telephone',
                    'email',
                    'adresse',
                ])
                ->where('id')
                ->param([
                    $student['matricule'],
                    $student['nom'],
                    $student['prenom'],
                    $student['date_naissance'],
                    $student['sexe'],
                    $student['classe_id'],
                    $student['telephone'],
                    $student['email'],
                    $student['adresse'],
                    $id,
                ])
                ->sdb(1)
                ->UQuery();
        }

        return $result !== '';
    }

    public function setStudentArchived(int $id, bool $archived): bool
    {
        $result = $this->table('eleves')
            ->set(['est_archive'])
            ->where('id')
            ->param([$archived ? 1 : 0, $id])
            ->sdb(1)
            ->UQuery();

        return $result !== '';
    }
}
