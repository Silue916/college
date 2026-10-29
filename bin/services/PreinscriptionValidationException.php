<?php

declare(strict_types=1);

namespace Epaphrodites\services;

use InvalidArgumentException;

final class PreinscriptionValidationException extends InvalidArgumentException
{
    /**
     * @param array<string, string> $errors
     */
    public function __construct(private array $errors)
    {
        parent::__construct('Le formulaire de préinscription contient des erreurs.');
    }

    /**
     * @return array<string, string>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
