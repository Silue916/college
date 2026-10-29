<?php

declare(strict_types=1);

namespace Epaphrodites\services;

use DateTimeImmutable;
use Epaphrodites\database\query\Builders;
use InvalidArgumentException;
use PDO;
use RuntimeException;

final class NewsService extends Builders
{
    public function __construct(private ?PDO $connection = null)
    {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listPublished(?DateTimeImmutable $now = null): array
    {
        $statement = $this->connection()->prepare(
            'SELECT id, title, content, image_path, published_at
             FROM actualites
             WHERE published_at <= ?
             ORDER BY published_at DESC, id DESC'
        );
        $statement->execute([$this->dateTime($now)]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findPublished(int $id, ?DateTimeImmutable $now = null): ?array
    {
        $statement = $this->connection()->prepare(
            'SELECT id, title, content, image_path, published_at
             FROM actualites
             WHERE id = ? AND published_at <= ?'
        );
        $statement->execute([$id, $this->dateTime($now)]);
        $article = $statement->fetch(PDO::FETCH_ASSOC);

        return $article === false ? null : $article;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listAll(): array
    {
        $statement = $this->connection()->query(
            'SELECT id, title, content, image_path, published_at
             FROM actualites
             ORDER BY published_at DESC, id DESC'
        );

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(
        string $title,
        string $content,
        string $publishedAt,
        ?string $imagePath = null
    ): int {
        self::validateArticle($title, $content, $publishedAt);
        $now = date('Y-m-d H:i:s');
        $statement = $this->connection()->prepare(
            'INSERT INTO actualites (title, content, image_path, published_at, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $statement->execute([
            trim($title),
            trim($content),
            $imagePath,
            self::normalizeDateTime($publishedAt),
            $now,
            $now,
        ]);

        return (int) $this->connection()->lastInsertId();
    }

    public function update(
        int $id,
        string $title,
        string $content,
        string $publishedAt,
        ?string $imagePath = null,
        bool $removeImage = false
    ): ?string {
        self::validateArticle($title, $content, $publishedAt);

        $existing = $this->connection()->prepare(
            'SELECT image_path FROM actualites WHERE id = ?'
        );
        $existing->execute([$id]);
        $oldImage = $existing->fetchColumn();
        if ($oldImage === false) {
            throw new InvalidArgumentException('Cette actualité est introuvable.');
        }

        if ($removeImage || $imagePath !== null) {
            $statement = $this->connection()->prepare(
                'UPDATE actualites
                 SET title = ?, content = ?, image_path = ?, published_at = ?, updated_at = ?
                 WHERE id = ?'
            );
            $statement->execute([
                trim($title),
                trim($content),
                $removeImage ? null : $imagePath,
                self::normalizeDateTime($publishedAt),
                date('Y-m-d H:i:s'),
                $id,
            ]);
        } else {
            $statement = $this->connection()->prepare(
                'UPDATE actualites
                 SET title = ?, content = ?, published_at = ?, updated_at = ?
                 WHERE id = ?'
            );
            $statement->execute([
                trim($title),
                trim($content),
                self::normalizeDateTime($publishedAt),
                date('Y-m-d H:i:s'),
                $id,
            ]);
        }

        return is_string($oldImage) ? $oldImage : null;
    }

    public function delete(int $id): ?string
    {
        $statement = $this->connection()->prepare(
            'SELECT image_path FROM actualites WHERE id = ?'
        );
        $statement->execute([$id]);
        $image = $statement->fetchColumn();
        if ($image === false) {
            throw new InvalidArgumentException('Cette actualité est introuvable.');
        }

        $delete = $this->connection()->prepare('DELETE FROM actualites WHERE id = ?');
        $delete->execute([$id]);

        return is_string($image) ? $image : null;
    }

    public static function validateArticle(
        string $title,
        string $content,
        string $publishedAt
    ): void {
        if (trim($title) === '' || mb_strlen(trim($title)) > 200) {
            throw new InvalidArgumentException('Le titre est obligatoire et limité à 200 caractères.');
        }
        if (trim($content) === '' || mb_strlen(trim($content)) > 20000) {
            throw new InvalidArgumentException('Le contenu est obligatoire et limité à 20 000 caractères.');
        }

        self::normalizeDateTime($publishedAt);
    }

    private function connection(): PDO
    {
        $connection = $this->connection ?? $this->db(1);
        if (!$connection instanceof PDO) {
            throw new RuntimeException('Le service des actualités nécessite une connexion SQL.');
        }

        return $connection;
    }

    private function dateTime(?DateTimeImmutable $date): string
    {
        return ($date ?? new DateTimeImmutable())->format('Y-m-d H:i:s');
    }

    private static function normalizeDateTime(string $date): string
    {
        if (trim($date) === '') {
            throw new InvalidArgumentException('La date de publication est obligatoire.');
        }

        try {
            return (new DateTimeImmutable($date))->format('Y-m-d H:i:s');
        } catch (\Exception $exception) {
            throw new InvalidArgumentException('La date de publication est invalide.', 0, $exception);
        }
    }
}
