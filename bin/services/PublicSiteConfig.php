<?php

declare(strict_types=1);

namespace Epaphrodites\services;

use RuntimeException;

final class PublicSiteConfig
{
    /**
     * @return array{
     *     name: string,
     *     logo: string,
     *     address: string,
     *     phone: string,
     *     phone_link: string,
     *     contact_email: string,
     *     social_links: list<array{label: string, url: string}>
     * }
     */
    public static function local(): array
    {
        $configuration = parse_ini_file(
            dirname(__DIR__) . '/config/Config.ini',
            true,
            INI_SCANNER_TYPED
        );
        if ($configuration === false) {
            throw new RuntimeException('Impossible de lire la configuration du site.');
        }

        $site = $configuration['SITE'] ?? [];
        if (!is_array($site)) {
            throw new RuntimeException('La section SITE de Config.ini est invalide.');
        }

        $phone = self::stringValue($site, 'PHONE', '+225 00 00 00 00 00');
        if (!str_starts_with($phone, '+225')) {
            throw new RuntimeException('SITE.PHONE doit utiliser l’indicatif +225.');
        }

        $phoneLink = preg_replace('/[^+0-9]/', '', $phone);
        if (!is_string($phoneLink) || preg_match('/^\+225[0-9]{10}$/', $phoneLink) !== 1) {
            throw new RuntimeException('SITE.PHONE doit contenir un numéro ivoirien valide.');
        }

        $logo = self::stringValue($site, 'LOGO', '/static/img/logo.png');
        if (
            preg_match('~^[A-Za-z0-9._/-]+$~', $logo) !== 1
            || str_contains($logo, '..')
            || str_starts_with($logo, '//')
        ) {
            throw new RuntimeException('SITE.LOGO doit désigner un fichier local sûr.');
        }
        if (!str_starts_with($logo, '/')) {
            $logo = '/' . $logo;
        }

        $socialLinks = [];
        foreach ([
            'FACEBOOK' => 'Facebook',
            'INSTAGRAM' => 'Instagram',
            'LINKEDIN' => 'LinkedIn',
            'YOUTUBE' => 'YouTube',
        ] as $key => $label) {
            $url = self::stringValue($site, $key, '');
            if ($url === '') {
                continue;
            }

            if (
                filter_var($url, FILTER_VALIDATE_URL) === false
                || !in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)
            ) {
                throw new RuntimeException("SITE.$key doit être une URL HTTP ou HTTPS valide.");
            }

            $socialLinks[] = ['label' => $label, 'url' => $url];
        }

        return [
            'name' => self::stringValue($site, 'NAME', 'Établissement scolaire'),
            'logo' => $logo,
            'address' => self::stringValue($site, 'ADDRESS', 'Adresse à renseigner'),
            'phone' => $phone,
            'phone_link' => 'tel:' . $phoneLink,
            'contact_email' => self::stringValue($site, 'CONTACT_EMAIL', ''),
            'social_links' => $socialLinks,
        ];
    }

    /**
     * @return array{site: array<string, mixed>}
     */
    public static function viewData(): array
    {
        return ['site' => self::local()];
    }

    /**
     * @param array<string, mixed> $section
     */
    private static function stringValue(array $section, string $key, string $default): string
    {
        $value = $section[$key] ?? $default;
        if (!is_string($value)) {
            throw new RuntimeException("SITE.$key doit être une chaîne de caractères.");
        }

        return trim($value);
    }
}
