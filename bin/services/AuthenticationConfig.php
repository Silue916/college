<?php

declare(strict_types=1);

namespace Epaphrodites\services;

use RuntimeException;

final class AuthenticationConfig
{
    /**
     * @return array{admin_email: string, admin_password: string, app_url: string}
     */
    public static function read(string $path): array
    {
        $configuration = parse_ini_file($path, true, INI_SCANNER_TYPED);
        $authentication = is_array($configuration)
            ? ($configuration['AUTHENTICATION'] ?? null)
            : null;

        if (!is_array($authentication)) {
            throw new RuntimeException('La section AUTHENTICATION est absente de Config.ini.');
        }

        $email = $authentication['ADMIN_EMAIL'] ?? '';
        $password = $authentication['ADMIN_PASSWORD'] ?? '';
        $appUrl = $authentication['APP_URL'] ?? '';

        if (!is_string($email) || !is_string($password) || !is_string($appUrl)) {
            throw new RuntimeException('La configuration d’authentification est invalide.');
        }

        return [
            'admin_email' => trim($email),
            'admin_password' => $password,
            'app_url' => rtrim(trim($appUrl), '/'),
        ];
    }

    public static function local(): array
    {
        return self::read(dirname(__DIR__) . '/config/Config.ini');
    }
}
