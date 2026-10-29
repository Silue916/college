<?php

declare(strict_types=1);

namespace Epaphrodites\services;

use DateTimeImmutable;
use Epaphrodites\database\query\Builders;
use InvalidArgumentException;
use PDO;
use RuntimeException;
use Throwable;

final class ContactFormService extends Builders
{
    public function __construct(private ?PDO $connection = null)
    {
    }

    /**
     * @param array<string, mixed> $values
     * @return array<string, string>
     */
    public static function validate(array $values): array
    {
        $errors = [];
        $name = self::stringValue($values, 'name');
        $phone = self::stringValue($values, 'phone');
        $email = self::stringValue($values, 'email');
        $message = self::stringValue($values, 'message');

        if ($name === '' || mb_strlen($name) > 100) {
            $errors['name'] = 'Indiquez votre nom (100 caractères maximum).';
        }
        if ($phone === '' || mb_strlen($phone) > 30) {
            $errors['phone'] = 'Indiquez un numéro de téléphone valide.';
        }
        if (
            $email === ''
            || mb_strlen($email) > 254
            || filter_var($email, FILTER_VALIDATE_EMAIL) === false
        ) {
            $errors['email'] = 'Indiquez une adresse e-mail valide.';
        }
        if ($message === '' || mb_strlen($message) > 5000) {
            $errors['message'] = 'Votre message est obligatoire et limité à 5 000 caractères.';
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $values
     * @param callable(array{name: string, phone: string, email: string, message: string}): bool $send
     * @return 'sent'|'ignored'|'rate_limited'
     */
    public function submit(
        array $values,
        string $ipAddress,
        callable $send,
        ?DateTimeImmutable $now = null
    ): string {
        if (trim(self::stringValue($values, 'website')) !== '') {
            return 'ignored';
        }

        $errors = self::validate($values);
        if ($errors !== []) {
            throw new InvalidArgumentException('Le formulaire de contact contient des erreurs.');
        }
        if (filter_var($ipAddress, FILTER_VALIDATE_IP) === false) {
            throw new InvalidArgumentException('Adresse IP du formulaire invalide.');
        }

        $contact = [
            'name' => trim(self::stringValue($values, 'name')),
            'phone' => trim(self::stringValue($values, 'phone')),
            'email' => trim(self::stringValue($values, 'email')),
            'message' => trim(self::stringValue($values, 'message')),
        ];
        $now ??= new DateTimeImmutable();
        $timestamp = $now->format('Y-m-d H:i:s');
        $cutoff = $now->modify('-1 hour')->format('Y-m-d H:i:s');
        $connection = $this->connection();
        $driver = $connection->getAttribute(PDO::ATTR_DRIVER_NAME);

        $connection->beginTransaction();
        try {
            if ($driver === 'mysql') {
                $lock = $connection->prepare(
                    'INSERT INTO contact_rate_limit_locks (ip_address, locked_at)
                     VALUES (?, ?)
                     ON DUPLICATE KEY UPDATE locked_at = VALUES(locked_at)'
                );
                $lock->execute([$ipAddress, $timestamp]);
                $lockRow = $connection->prepare(
                    'SELECT ip_address FROM contact_rate_limit_locks
                     WHERE ip_address = ? FOR UPDATE'
                );
                $lockRow->execute([$ipAddress]);
                $lockRow->fetchColumn();
            } elseif ($driver === 'sqlite') {
                $lock = $connection->prepare(
                    'INSERT OR IGNORE INTO contact_rate_limit_locks (ip_address, locked_at)
                     VALUES (?, ?)'
                );
                $lock->execute([$ipAddress, $timestamp]);
                $updateLock = $connection->prepare(
                    'UPDATE contact_rate_limit_locks SET locked_at = ? WHERE ip_address = ?'
                );
                $updateLock->execute([$timestamp, $ipAddress]);
            } else {
                throw new RuntimeException(
                    'La limitation du formulaire de contact prend en charge MySQL et SQLite.'
                );
            }

            $removeOldAttempts = $connection->prepare(
                'DELETE FROM contact_rate_limits WHERE submitted_at <= ?'
            );
            $removeOldAttempts->execute([$cutoff]);
            $removeOldLocks = $connection->prepare(
                'DELETE FROM contact_rate_limit_locks
                 WHERE locked_at <= ? AND ip_address <> ?'
            );
            $removeOldLocks->execute([$cutoff, $ipAddress]);

            $count = $connection->prepare(
                'SELECT COUNT(*) FROM contact_rate_limits
                 WHERE ip_address = ? AND submitted_at > ?'
            );
            $count->execute([$ipAddress, $cutoff]);
            if ((int) $count->fetchColumn() >= 3) {
                $connection->commit();
                return 'rate_limited';
            }

            $insert = $connection->prepare(
                'INSERT INTO contact_rate_limits (ip_address, submitted_at) VALUES (?, ?)'
            );
            $insert->execute([$ipAddress, $timestamp]);
            $connection->commit();
        } catch (Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            throw $exception;
        }

        if ($send($contact) !== true) {
            throw new RuntimeException('L’envoi du message de contact a échoué.');
        }

        return 'sent';
    }

    private function connection(): PDO
    {
        $connection = $this->connection ?? $this->db(1);
        if (!$connection instanceof PDO) {
            throw new RuntimeException('Le formulaire de contact nécessite une connexion SQL.');
        }

        return $connection;
    }

    /**
     * @param array<string, mixed> $values
     */
    private static function stringValue(array $values, string $key): string
    {
        $value = $values[$key] ?? '';

        return is_string($value) ? $value : '';
    }
}
