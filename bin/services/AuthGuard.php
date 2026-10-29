<?php

namespace Epaphrodites\services;

class AuthGuard
{
    public const ROLE_SESSION_KEY = 'epaphrodites_role';
    public const LAST_ACTIVITY_SESSION_KEY = 'epaphrodites_last_activity';
    public const PASSWORD_CHANGE_SESSION_KEY = 'epaphrodites_must_change_password';
    private const IDLE_TIMEOUT_SECONDS = 1800;

    public static function requireRole(array $allowed): void
    {
        if (!self::roleIsAllowed($allowed)) {
            http_response_code(403);
            exit('Accès refusé');
        }
    }

    public static function requireAuth(bool $allowForcedPasswordChange = false): void
    {
        if (empty($_SESSION[_AUTH_ID_])) {
            self::redirectToLogin();
        }

        $now = time();
        $lastActivity = $_SESSION[self::LAST_ACTIVITY_SESSION_KEY] ?? null;
        if (is_int($lastActivity) && self::sessionExpired($lastActivity, $now)) {
            $_SESSION = [];
            session_destroy();
            self::redirectToLogin();
        }

        $_SESSION[self::LAST_ACTIVITY_SESSION_KEY] = $now;

        if (
            !$allowForcedPasswordChange
            && !empty($_SESSION[self::PASSWORD_CHANGE_SESSION_KEY])
        ) {
            header('Location: /first_password_change/');
            exit;
        }
    }

    public static function roleIsAllowed(array $allowed): bool
    {
        $role = $_SESSION[self::ROLE_SESSION_KEY] ?? null;

        return is_string($role) && in_array($role, $allowed, true);
    }

    public static function sessionExpired(int $lastActivity, int $now): bool
    {
        return $now - $lastActivity >= self::IDLE_TIMEOUT_SECONDS;
    }

    private static function redirectToLogin(): void
    {
        header('Location: /' . _LOGIN_);
        exit;
    }
}
