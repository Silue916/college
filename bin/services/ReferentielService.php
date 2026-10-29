<?php

namespace Epaphrodites\services;

use DateTimeImmutable;
use Epaphrodites\database\query\Builders;
use PDO;
use RuntimeException;
use Throwable;

class ReferentielService extends Builders
{
    private ?PDO $connection;

    public function __construct(?PDO $connection = null)
    {
        $this->connection = $connection;
    }

    public function getAnneeActive(): array
    {
        $statement = $this->connection()->query(
            'SELECT * FROM annees_scolaires WHERE est_active = 1'
        );
        $years = $statement->fetchAll(PDO::FETCH_ASSOC);

        if (count($years) !== 1) {
            throw new RuntimeException(
                count($years) === 0
                    ? 'Aucune année scolaire active'
                    : 'Plusieurs années scolaires sont actives'
            );
        }

        return $years[0];
    }

    public function getPeriodeCourante(?DateTimeImmutable $date = null): array
    {
        $annee = $this->getAnneeActive();
        $today = ($date ?? new DateTimeImmutable())->format('Y-m-d');
        $statement = $this->connection()->prepare(
            'SELECT * FROM periodes
             WHERE annee_id = ? AND date_debut <= ? AND date_fin >= ?
             ORDER BY numero
             LIMIT 1'
        );
        $statement->execute([$annee['id'], $today, $today]);
        $period = $statement->fetch(PDO::FETCH_ASSOC);

        if ($period === false) {
            throw new RuntimeException('Aucune période scolaire en cours');
        }

        return $period;
    }

    public function getCoefficient(int $niveau, ?int $serie, int $matiere): int
    {
        $statement = $this->connection()->prepare(
            'SELECT coefficient FROM coefficients
             WHERE niveau_id = ? AND serie_unique_key = ? AND matiere_id = ?'
        );
        $statement->execute([$niveau, $serie ?? 0, $matiere]);
        $coefficient = $statement->fetchColumn();

        if ($coefficient === false) {
            throw new RuntimeException('Aucun coefficient défini');
        }

        return (int) $coefficient;
    }

    public function activateYear(int $yearId): void
    {
        $connection = $this->connection();
        $connection->beginTransaction();

        try {
            $exists = $connection->prepare(
                'SELECT 1 FROM annees_scolaires WHERE id = ?'
            );
            $exists->execute([$yearId]);

            if ($exists->fetchColumn() === false) {
                throw new RuntimeException('Année scolaire introuvable');
            }

            $connection->exec(
                'UPDATE annees_scolaires SET est_active = NULL WHERE est_active = 1'
            );
            $activate = $connection->prepare(
                'UPDATE annees_scolaires SET est_active = 1 WHERE id = ?'
            );
            $activate->execute([$yearId]);
            if ($activate->rowCount() !== 1) {
                throw new RuntimeException('Année scolaire introuvable');
            }

            $connection->commit();
        } catch (Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            throw $exception;
        }
    }

    public static function validateClassSeries(int $cycle, ?int $serieId): void
    {
        if (!in_array($cycle, [1, 2], true)) {
            throw new RuntimeException('Cycle scolaire invalide');
        }

        if (($cycle === 1 && $serieId !== null) || ($cycle === 2 && ($serieId === null || $serieId < 1))) {
            throw new RuntimeException(
                $cycle === 1
                    ? 'Une classe du premier cycle ne peut pas avoir de série'
                    : 'Une classe du second cycle doit avoir une série'
            );
        }
    }

    private function connection(): PDO
    {
        $connection = $this->connection ?? $this->db(1);

        if (!$connection instanceof PDO) {
            throw new RuntimeException('La connexion au référentiel doit être SQL');
        }

        return $connection;
    }
}
