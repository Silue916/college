<?php

namespace Epaphrodites\services;

use Epaphrodites\database\query\Builders;
use RuntimeException;

class ReferentielService extends Builders
{
    public function getAnneeActive(): array
    {
        $result = $this->table('annees_scolaires')
            ->where('est_active')
            ->param([1])
            ->sdb(1)
            ->SQuery();

        if (empty($result)) {
            throw new RuntimeException('Aucune année scolaire active');
        }
        return is_array($result) ? $result[0] : $result;
    }

    public function getPeriodeCourante(): array
    {
        $annee = $this->getAnneeActive();
        $result = $this->table('periodes')
            ->where('annee_id')
            ->param([$annee['id']])
            ->sdb(1)
            ->SQuery();

        if (empty($result)) {
            throw new RuntimeException('Aucune période pour l\'année active');
        }
        return is_array($result) ? $result[0] : $result;
    }

    public function getCoefficient(int $niveau, ?int $serie, int $matiere): int
    {
        $query = $this->table('coefficients')
            ->where('niveau_id')
            ->and(['matiere_id'])
            ->param([$niveau, $matiere]);

        if ($serie === null) {
            $query = $query->and(['serie_id IS NULL']);
        } else {
            $query = $query->and(['serie_id']);
            $query = $this->table('coefficients')
                ->where('niveau_id')
                ->and(['matiere_id', 'serie_id'])
                ->param([$niveau, $matiere, $serie]);
        }

        $result = $query->sdb(1)->SQuery('coefficient');

        if (empty($result)) {
            throw new RuntimeException('Aucun coefficient défini');
        }
        $row = is_array($result) ? $result[0] : $result;
        return (int) ($row['coefficient'] ?? $row);
    }
}
