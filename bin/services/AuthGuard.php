<?php

namespace Epaphrodites\services;

class AuthGuard
{
    public static function requireRole(array $allowed): void
    {
        if (empty($_SESSION[_AUTH_TYPE_]) || !in_array($_SESSION[_AUTH_TYPE_], $allowed, true)) {
            http_response_code(403);
            exit('Accès refusé');
        }
    }

    public static function requireAuth(): void
    {
        if (empty($_SESSION[_AUTH_ID_])) {
            header('Location: /' . _LOGIN_);
            exit;
        }
    }
}
