<?php

namespace tests\Feature;

use DateTimeImmutable;
use Epaphrodites\services\NewsService;
use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class HomepageTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function publicPages(): iterable
    {
        yield 'home' => ['/', 'Bienvenue'];
        yield 'home alias' => ['/accueil/', 'Bienvenue'];
        yield 'school' => ['/etablissement/', 'Notre établissement'];
        yield 'admissions' => ['/admission/', 'Conditions d’admission'];
        yield 'fees' => ['/frais/', 'Frais de scolarité'];
        yield 'news' => ['/actualites/', 'Actualités'];
        yield 'contact' => ['/contact/', 'Contactez-nous'];
        yield 'login' => ['/view/login/', 'CONNEXION'];
        yield 'pre-registration' => ['/preinscription/', 'Préinscription en ligne'];
        yield 'pre-registration tracking' => ['/suivi_preinscription/', 'Suivre mon dossier'];
    }

    #[DataProvider('publicPages')]
    public function testPublicPagesReturn200(string $path, string $heading): void
    {
        $baseUrl = rtrim(getenv('HOMEPAGE_URL') ?: 'http://127.0.0.1:8000', '/');
        $url = $baseUrl . $path;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        $this->assertSame(200, $code, $error);
        $this->assertIsString($response);
        $this->assertStringContainsString($heading, $response);
        $this->assertStringNotContainsString('ERROR 404', $response);
        $this->assertStringContainsString('site-menu-toggle', $response);
        $this->assertStringContainsString('<a class="nav-cta" href="/view/login/">Mon espace</a>', $response);
        $this->assertStringContainsString('<a href="/preinscription/">Préinscription</a>', $response);
    }

    public function testUnknownPublicRouteUsesTheCustom404Page(): void
    {
        $baseUrl = rtrim(getenv('HOMEPAGE_URL') ?: 'http://127.0.0.1:8000', '/');
        $ch = curl_init($baseUrl . '/route-inconnue-pour-test/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        self::assertSame(404, $code, $error);
        self::assertIsString($response);
        self::assertStringContainsString('Cette page est introuvable', $response);
    }

    public function testPreviousConnexionRouteIsNoLongerAvailable(): void
    {
        $baseUrl = rtrim(getenv('HOMEPAGE_URL') ?: 'http://127.0.0.1:8000', '/');
        $ch = curl_init($baseUrl . '/connexion/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        self::assertSame(404, $code, $error);
        self::assertIsString($response);
        self::assertStringContainsString('Cette page est introuvable', $response);
    }

    public function testPublishedNewsDetailIsReachableOnItsPublicRoute(): void
    {
        $dsn = getenv('PUBLIC_SITE_MYSQL_DSN');
        if ($dsn === false || $dsn === '') {
            self::markTestSkipped('PUBLIC_SITE_MYSQL_DSN is set only in CI.');
        }
        if (!str_contains(strtolower($dsn), 'dbname=epaphrodites_test')) {
            self::fail('PUBLIC_SITE_MYSQL_DSN must target epaphrodites_test.');
        }

        $connection = new PDO(
            $dsn,
            getenv('PUBLIC_SITE_MYSQL_USER') ?: '',
            getenv('PUBLIC_SITE_MYSQL_PASSWORD') ?: '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $news = new NewsService($connection);
        $id = $news->create(
            'Actualité détail de test',
            'Contenu publié pour le test fonctionnel.',
            (new DateTimeImmutable())->modify('-1 minute')->format('Y-m-d H:i:s')
        );

        try {
            $baseUrl = rtrim(getenv('HOMEPAGE_URL') ?: 'http://127.0.0.1:8000', '/');
            $ch = curl_init($baseUrl . '/actualite/?id=' . $id);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            $response = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            self::assertSame(200, $code, $error);
            self::assertIsString($response);
            self::assertStringContainsString('Actualité détail de test', $response);
            self::assertStringContainsString('Contenu publié pour le test fonctionnel.', $response);
        } finally {
            $news->delete($id);
        }
    }
}
