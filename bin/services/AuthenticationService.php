<?php

declare(strict_types=1);

namespace Epaphrodites\services;

use Closure;
use DateTimeImmutable;
use Epaphrodites\database\query\Builders;
use PDO;
use RuntimeException;
use Throwable;

final class AuthenticationService extends Builders
{
    private ?PDO $connection;

    public function __construct(?PDO $connection = null)
    {
        $this->connection = $connection;
    }

    /**
     * @return array{
     *     id: int,
     *     login: string,
     *     namesurname: string,
     *     contact: string,
     *     email: string,
     *     usersgroup: int,
     *     role: string|null,
     *     must_change_password: bool
     * }|null
     */
    public function authenticate(
        string $identifier,
        string $password,
        ?DateTimeImmutable $now = null
    ): ?array {
        $identifier = trim($identifier);
        if ($identifier === '' || $password === '') {
            return null;
        }

        $query = $this->connection()->prepare(
            'SELECT u._id, u.login, u.password, u.namesurname, u.contact, u.email,
                    u.usersgroup, u.state
             FROM usersaccount AS u
             WHERE u.login = ? OR u.email = ?
             LIMIT 2'
        );
        $query->execute([$identifier, $identifier]);
        $users = $query->fetchAll(PDO::FETCH_ASSOC);

        if (count($users) !== 1) {
            return null;
        }

        $user = $users[0];
        if ((int) $user['state'] !== 1) {
            return null;
        }

        $now ??= new DateTimeImmutable();
        $connection = $this->connection();
        $connection->beginTransaction();

        try {
            $userId = (int) $user['_id'];
            $this->ensureSecurityRecord($connection, $userId);
            $securityQuery = $connection->prepare(
                'SELECT failed_attempts, locked_until, must_change_password
                 FROM user_security WHERE user_id = ?'
            );
            $securityQuery->execute([$userId]);
            $security = $securityQuery->fetch(PDO::FETCH_ASSOC);

            if ($security === false) {
                throw new RuntimeException('Les paramètres de sécurité du compte sont introuvables.');
            }

            $lockedUntil = $security['locked_until'];
            if (
                is_string($lockedUntil)
                && $lockedUntil !== ''
                && new DateTimeImmutable($lockedUntil) > $now
            ) {
                $connection->commit();
                return null;
            }

            if (!password_verify($password, (string) $user['password'])) {
                $attempts = (int) $security['failed_attempts'] + 1;
                $lockUntil = $attempts >= 5
                    ? $now->modify('+15 minutes')->format('Y-m-d H:i:s')
                    : null;
                $update = $connection->prepare(
                    'UPDATE user_security
                     SET failed_attempts = ?, locked_until = ?
                     WHERE user_id = ?'
                );
                $update->execute([$attempts, $lockUntil, $userId]);
                $connection->commit();
                return null;
            }

            $resetAttempts = $connection->prepare(
                'UPDATE user_security
                 SET failed_attempts = 0, locked_until = NULL
                 WHERE user_id = ?'
            );
            $resetAttempts->execute([$userId]);
            $connection->commit();

            $groupId = (int) $user['usersgroup'];
            $role = match ($groupId) {
                1, 2 => 'admin',
                4 => 'scolarite',
                5 => 'comptabilite',
                6 => 'enseignant',
                7 => 'eleve',
                8 => 'parent',
                default => null,
            };

            return [
                'id' => $userId,
                'login' => (string) $user['login'],
                'namesurname' => (string) ($user['namesurname'] ?? ''),
                'contact' => (string) ($user['contact'] ?? ''),
                'email' => (string) ($user['email'] ?? ''),
                'usersgroup' => $groupId,
                'role' => $role,
                'must_change_password' => (int) $security['must_change_password'] === 1,
            ];
        } catch (Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            throw $exception;
        }
    }

    public function requestPasswordReset(
        string $email,
        callable $send,
        ?DateTimeImmutable $now = null
    ): void {
        $query = $this->connection()->prepare(
            'SELECT _id, email FROM usersaccount WHERE LOWER(email) = LOWER(?) LIMIT 2'
        );
        $query->execute([trim($email)]);
        $users = $query->fetchAll(PDO::FETCH_ASSOC);

        if (count($users) !== 1 || !is_string($users[0]['email'])) {
            return;
        }

        $now ??= new DateTimeImmutable();
        $userId = (int) $users[0]['_id'];
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = $now->modify('+1 hour')->format('Y-m-d H:i:s');
        $connection = $this->connection();
        $connection->beginTransaction();

        try {
            $this->ensureSecurityRecord($connection, $userId);
            $invalidate = $connection->prepare(
                'UPDATE password_reset_tokens
                 SET used_at = ?
                 WHERE user_id = ? AND used_at IS NULL'
            );
            $invalidate->execute([$now->format('Y-m-d H:i:s'), $userId]);

            $insert = $connection->prepare(
                'INSERT INTO password_reset_tokens (user_id, token_hash, expires_at)
                 VALUES (?, ?, ?)'
            );
            $insert->execute([$userId, $tokenHash, $expiresAt]);
            $connection->commit();
        } catch (Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            throw $exception;
        }

        $deliver = Closure::fromCallable($send);
        if ($deliver($users[0]['email'], $token) !== true) {
            throw new RuntimeException('L’e-mail de réinitialisation n’a pas pu être envoyé.');
        }
    }

    public function resetPassword(
        string $token,
        string $newPassword,
        ?DateTimeImmutable $now = null
    ): bool {
        if (!self::isValidPassword($newPassword) || $token === '') {
            return false;
        }

        $now ??= new DateTimeImmutable();
        $connection = $this->connection();
        $connection->beginTransaction();

        try {
            $lookup = $connection->prepare(
                'SELECT id, user_id FROM password_reset_tokens
                 WHERE token_hash = ? AND used_at IS NULL AND expires_at > ?'
            );
            $lookup->execute([
                hash('sha256', $token),
                $now->format('Y-m-d H:i:s'),
            ]);
            $reset = $lookup->fetch(PDO::FETCH_ASSOC);

            if ($reset === false) {
                $connection->commit();
                return false;
            }

            $markUsed = $connection->prepare(
                'UPDATE password_reset_tokens SET used_at = ?
                 WHERE id = ? AND used_at IS NULL AND expires_at > ?'
            );
            $markUsed->execute([
                $now->format('Y-m-d H:i:s'),
                (int) $reset['id'],
                $now->format('Y-m-d H:i:s'),
            ]);

            if ($markUsed->rowCount() !== 1) {
                $connection->commit();
                return false;
            }

            $this->updatePassword($connection, (int) $reset['user_id'], $newPassword);
            $connection->commit();
            return true;
        } catch (Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            throw $exception;
        }
    }

    public function changePassword(int $userId, string $newPassword): void
    {
        if (!self::isValidPassword($newPassword)) {
            throw new RuntimeException(
                'Le mot de passe doit contenir au moins 8 caractères, une lettre et un chiffre.'
            );
        }

        $connection = $this->connection();
        $connection->beginTransaction();

        try {
            $this->updatePassword($connection, $userId, $newPassword);
            $connection->commit();
        } catch (Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            throw $exception;
        }
    }

    public static function isValidPassword(string $password): bool
    {
        return mb_strlen($password) >= 8
            && preg_match('/\p{L}/u', $password) === 1
            && preg_match('/[0-9]/', $password) === 1;
    }

    private function ensureSecurityRecord(PDO $connection, int $userId): void
    {
        $driver = $connection->getAttribute(PDO::ATTR_DRIVER_NAME);
        $statement = $driver === 'sqlite'
            ? 'INSERT OR IGNORE INTO user_security (user_id) VALUES (?)'
            : 'INSERT IGNORE INTO user_security (user_id) VALUES (?)';
        $connection->prepare($statement)->execute([$userId]);
    }

    private function updatePassword(PDO $connection, int $userId, string $newPassword): void
    {
        if (!self::isValidPassword($newPassword)) {
            throw new RuntimeException(
                'Le mot de passe doit contenir au moins 8 caractères, une lettre et un chiffre.'
            );
        }

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);

        $updatePassword = $connection->prepare(
            'UPDATE usersaccount SET password = ? WHERE _id = ?'
        );
        $updatePassword->execute([$hash, $userId]);
        if ($updatePassword->rowCount() !== 1) {
            throw new RuntimeException('Le compte utilisateur est introuvable.');
        }

        $this->ensureSecurityRecord($connection, $userId);
        $updateSecurity = $connection->prepare(
            'UPDATE user_security
             SET must_change_password = 0, failed_attempts = 0, locked_until = NULL
             WHERE user_id = ?'
        );
        $updateSecurity->execute([$userId]);
    }

    private function connection(): PDO
    {
        $connection = $this->connection ?? $this->db(1);
        if (!$connection instanceof PDO) {
            throw new RuntimeException('La connexion d’authentification doit être SQL.');
        }

        return $connection;
    }
}
