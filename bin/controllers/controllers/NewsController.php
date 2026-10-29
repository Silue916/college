<?php

declare(strict_types=1);

namespace Epaphrodites\controllers\controllers;

use Epaphrodites\controllers\switchers\MainSwitchers;
use Epaphrodites\services\AuthGuard;
use Epaphrodites\services\NewsService;
use Epaphrodites\services\PublicSiteConfig;
use InvalidArgumentException;
use RuntimeException;

final class NewsController extends MainSwitchers
{
    public function manageNews(string $html): void
    {
        AuthGuard::requireRole(['admin']);
        $service = new NewsService();
        $formError = '';
        $uploadedImage = null;

        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            try {
                $action = $this->postString('action');
                if ($action === 'delete') {
                    $image = $service->delete($this->postId());
                    self::removeStoredImage($image);
                    header('Location: /news/manage_news/', true, 303);
                    return;
                }

                $title = $this->postString('title');
                $content = $this->postString('content');
                $publishedAt = $this->postString('published_at');
                NewsService::validateArticle($title, $content, $publishedAt);
                $id = $action === 'update' ? $this->postId() : null;
                $uploadedImage = self::storeImage();

                if ($action === 'create') {
                    $service->create($title, $content, $publishedAt, $uploadedImage);
                } elseif ($action === 'update') {
                    $oldImage = $service->update(
                        $id ?? 0,
                        $title,
                        $content,
                        $publishedAt,
                        $uploadedImage,
                        $uploadedImage === null && $this->postString('remove_image') === '1'
                    );
                    if ($uploadedImage !== null || $this->postString('remove_image') === '1') {
                        self::removeStoredImage($oldImage);
                    }
                } else {
                    throw new InvalidArgumentException('Action d’actualité invalide.');
                }

                header('Location: /news/manage_news/', true, 303);
                return;
            } catch (InvalidArgumentException $exception) {
                if ($uploadedImage !== null) {
                    self::removeStoredImage($uploadedImage);
                }
                $formError = $exception->getMessage();
            }
        }

        $this->views($html, array_merge(PublicSiteConfig::viewData(), [
            'news_items' => $service->listAll(),
            'form_error' => $formError,
            'current_datetime' => date('Y-m-d\TH:i'),
        ]), false);
    }

    private function postString(string $key): string
    {
        $value = $_POST[$key] ?? '';

        return is_string($value) ? trim($value) : '';
    }

    private function postId(): int
    {
        $value = $this->postString('id');
        if ($value === '' || !ctype_digit($value) || (int) $value < 1) {
            throw new InvalidArgumentException('Identifiant d’actualité invalide.');
        }

        return (int) $value;
    }

    private static function storeImage(): ?string
    {
        $file = $_FILES['image'] ?? null;
        if ($file === null || !is_array($file)) {
            return null;
        }

        $error = $file['error'] ?? null;
        if (!is_int($error)) {
            throw new InvalidArgumentException('Le formulaire de téléversement est invalide.');
        }
        if ($error === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($error !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('Le téléversement de l’image a échoué.');
        }

        $temporaryPath = $file['tmp_name'] ?? null;
        if (!is_string($temporaryPath) || !is_uploaded_file($temporaryPath)) {
            throw new InvalidArgumentException('Le fichier image téléversé est invalide.');
        }

        $size = filesize($temporaryPath);
        if ($size === false || $size > 2 * 1024 * 1024) {
            throw new InvalidArgumentException('L’image doit peser au maximum 2 Mo.');
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($temporaryPath);
        $extension = match ($mime) {
            'image/jpeg' => '.jpg',
            'image/png' => '.png',
            'image/webp' => '.webp',
            default => null,
        };
        if ($extension === null || getimagesize($temporaryPath) === false) {
            throw new InvalidArgumentException('Choisissez une image JPG, PNG ou WebP valide.');
        }

        $directory = _ROOT_ . '/static/uploads/news';
        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
            throw new RuntimeException('Impossible de préparer le stockage des images d’actualités.');
        }

        $filename = bin2hex(random_bytes(16)) . $extension;
        if (!move_uploaded_file($temporaryPath, $directory . '/' . $filename)) {
            throw new RuntimeException('Impossible d’enregistrer l’image de l’actualité.');
        }

        return '/static/uploads/news/' . $filename;
    }

    private static function removeStoredImage(?string $path): void
    {
        if (
            $path === null
            || preg_match('#^/static/uploads/news/[a-f0-9]{32}\.(jpg|png|webp)$#', $path) !== 1
        ) {
            return;
        }

        $absolutePath = _ROOT_ . $path;
        if (is_file($absolutePath) && !unlink($absolutePath)) {
            throw new RuntimeException('Impossible de supprimer l’image remplacée.');
        }
    }
}
