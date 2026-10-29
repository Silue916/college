<?php

namespace tests\Feature;

use DateTimeImmutable;
use Epaphrodites\database\gearShift\makeGearShift;
use Epaphrodites\database\seeders\databaseSeeding;
use Epaphrodites\epaphrodites\CsrfToken\token_csrf;
use Epaphrodites\services\AuthenticationService;
use Epaphrodites\services\AuthGuard;
use PDO;
use PHPUnit\Framework\TestCase;

class AuthenticationTest extends TestCase
{
    private PDO $connection;
    private AuthenticationService $authentication;
    private string|false $previousDriver;
    private array $previousPost;
    private array $previousSession;
    private string|false $previousRequestMethod;

    protected function setUp(): void
    {
        require_once dirname(__DIR__, 2) . '/bin/config/SetDirectory.php';

        $this->previousDriver = getenv('1DB_DRIVER');
        $this->previousPost = $_POST;
        $this->previousSession = $_SESSION ?? [];
        $this->previousRequestMethod = $_SERVER['REQUEST_METHOD'] ?? false;
        putenv('1DB_DRIVER=sqlite');

        $this->connection = new PDO('sqlite::memory:');
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        foreach ((new makeGearShift())->up() as $migration) {
            foreach ($migration['request'] as $statement) {
                $this->connection->exec($statement);
            }
        }

        $configuration = [
            'admin_email' => 'admin@example.test',
            'admin_password' => 'TestAdmin123',
            'app_url' => 'https://school.example.test',
        ];
        (new databaseSeeding($this->connection, $configuration))->sqlRun();
        $this->authentication = new AuthenticationService($this->connection);

        $insert = $this->connection->prepare(
            'INSERT INTO usersaccount (login, password, email, usersgroup, state, otp)
             VALUES (?, ?, ?, ?, 1, 0)'
        );
        $insert->execute([
            'ELEVE-001',
            password_hash('StudentPass123', PASSWORD_DEFAULT),
            'student@example.test',
            7,
        ]);
    }

    public function testConfiguredAdministratorUsesAHashAndMustChangePassword(): void
    {
        $user = $this->authentication->authenticate('admin@example.test', 'TestAdmin123');

        self::assertNotNull($user);
        self::assertSame('admin', $user['role']);
        self::assertTrue($user['must_change_password']);
        self::assertTrue(password_verify(
            'TestAdmin123',
            (string) $this->connection->query(
                "SELECT password FROM usersaccount WHERE login = 'admin@example.test'"
            )->fetchColumn()
        ));
        self::assertSame(
            'bcrypt',
            password_get_info((string) $this->connection->query(
                "SELECT password FROM usersaccount WHERE login = 'admin@example.test'"
            )->fetchColumn())['algoName']
        );
    }

    public function testAuthenticationWorksWithLegacyRolesTableWithoutGroupId(): void
    {
        $this->removeRoleGroupColumn();

        $user = $this->authentication->authenticate('admin@example.test', 'TestAdmin123');

        self::assertNotNull($user);
        self::assertSame('admin', $user['role']);
    }

    public function testSeederAddsMissingRoleGroupColumnToLegacySchema(): void
    {
        $this->removeRoleGroupColumn();

        (new databaseSeeding($this->connection, [
            'admin_email' => 'admin@example.test',
            'admin_password' => 'TestAdmin123',
            'app_url' => 'https://school.example.test',
        ]))->sqlRun();

        $columns = $this->connection->query('PRAGMA table_info(roles)')->fetchAll(PDO::FETCH_ASSOC);
        self::assertContains('group_id', array_column($columns, 'name'));
        self::assertSame(
            1,
            (int) $this->connection->query(
                "SELECT group_id FROM roles WHERE code = 'admin'"
            )->fetchColumn()
        );
    }

    public function testFiveFailuresLockTheAccountAgainstTheCorrectPasswordForFifteenMinutes(): void
    {
        $now = new DateTimeImmutable('2026-10-29 12:00:00');
        for ($attempt = 0; $attempt < 5; $attempt++) {
            self::assertNull(
                $this->authentication->authenticate(
                    'student@example.test',
                    'incorrect',
                    $now
                )
            );
        }

        self::assertNull(
            $this->authentication->authenticate(
                'ELEVE-001',
                'StudentPass123',
                $now->modify('+1 minute')
            )
        );
        self::assertNotNull(
            $this->authentication->authenticate(
                'ELEVE-001',
                'StudentPass123',
                $now->modify('+15 minutes')
            )
        );
    }

    public function testExpiredPasswordResetDoesNotChangeThePassword(): void
    {
        $token = '';
        $now = new DateTimeImmutable('2026-10-29 12:00:00');
        $this->authentication->requestPasswordReset(
            'admin@example.test',
            static function (string $email, string $sentToken) use (&$token): bool {
                self::assertSame('admin@example.test', $email);
                $token = $sentToken;
                return true;
            },
            $now
        );
        $oldHash = (string) $this->connection->query(
            "SELECT password FROM usersaccount WHERE login = 'admin@example.test'"
        )->fetchColumn();

        self::assertFalse(
            $this->authentication->resetPassword(
                $token,
                'ChangedPass123',
                $now->modify('+1 hour 1 second')
            )
        );
        self::assertSame(
            $oldHash,
            $this->connection->query(
                "SELECT password FROM usersaccount WHERE login = 'admin@example.test'"
            )->fetchColumn()
        );
    }

    public function testPasswordResetTokenCanOnlyBeUsedOnce(): void
    {
        $token = '';
        $now = new DateTimeImmutable('2026-10-29 12:00:00');
        $this->authentication->requestPasswordReset(
            'admin@example.test',
            static function (string $email, string $sentToken) use (&$token): bool {
                $token = $sentToken;
                return true;
            },
            $now
        );

        self::assertTrue(
            $this->authentication->resetPassword(
                $token,
                'ChangedPass123',
                $now->modify('+10 minutes')
            )
        );
        self::assertFalse(
            $this->authentication->resetPassword(
                $token,
                'AnotherPass123',
                $now->modify('+11 minutes')
            )
        );
        self::assertTrue(password_verify(
            'ChangedPass123',
            (string) $this->connection->query(
                "SELECT password FROM usersaccount WHERE login = 'admin@example.test'"
            )->fetchColumn()
        ));
    }

    public function testPasswordPolicyRequiresEightCharactersALetterAndANumber(): void
    {
        self::assertFalse(AuthenticationService::isValidPassword('short1'));
        self::assertFalse(AuthenticationService::isValidPassword('lettersOnly'));
        self::assertFalse(AuthenticationService::isValidPassword('12345678'));
        self::assertTrue(AuthenticationService::isValidPassword('Motdepasse1'));
    }

    public function testStudentRoleIsNotAllowedOnAdministrationRoutes(): void
    {
        $_SESSION[AuthGuard::ROLE_SESSION_KEY] = 'eleve';

        self::assertFalse(AuthGuard::roleIsAllowed(['admin', 'scolarite']));
    }

    public function testStudentReceives403OnTheAdministrationStudentsRoute(): void
    {
        $sessionId = session_create_id();
        $sessionName = session_name();
        $sessionFile = rtrim(session_save_path() ?: sys_get_temp_dir(), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . 'sess_' . $sessionId;

        try {
            session_name('session');
            session_id($sessionId);
            session_start();
            $_SESSION = [
                _AUTH_ID_ => '42',
                _AUTH_LOGIN_ => 'student@example.test',
                _AUTH_TYPE_ => '7',
                AuthGuard::ROLE_SESSION_KEY => 'eleve',
                AuthGuard::LAST_ACTIVITY_SESSION_KEY => time(),
            ];
            session_write_close();
            session_name($sessionName);

            $baseUrl = rtrim(getenv('HOMEPAGE_URL') ?: 'http://127.0.0.1:8000', '/');
            $curl = curl_init($baseUrl . '/students/manage_students/');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_TIMEOUT, 5);
            curl_setopt($curl, CURLOPT_HTTPHEADER, ['Cookie: session=' . $sessionId]);
            curl_exec($curl);

            self::assertSame(403, curl_getinfo($curl, CURLINFO_HTTP_CODE));
            curl_close($curl);

            $curl = curl_init($baseUrl . '/news/manage_news/');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_TIMEOUT, 5);
            curl_setopt($curl, CURLOPT_HTTPHEADER, ['Cookie: session=' . $sessionId]);
            curl_exec($curl);

            self::assertSame(403, curl_getinfo($curl, CURLINFO_HTTP_CODE));
            curl_close($curl);

            $curl = curl_init($baseUrl . '/dashboard/super_admin/');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_TIMEOUT, 5);
            curl_setopt($curl, CURLOPT_HTTPHEADER, ['Cookie: session=' . $sessionId]);
            curl_exec($curl);

            self::assertSame(403, curl_getinfo($curl, CURLINFO_HTTP_CODE));
            curl_close($curl);
        } finally {
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }
            session_name($sessionName);
            if (is_file($sessionFile)) {
                unlink($sessionFile);
            }
        }
    }

    public function testAdministratorCanOpenTheNewsManagementPage(): void
    {
        $sessionId = session_create_id();
        $sessionName = session_name();
        $sessionFile = rtrim(session_save_path() ?: sys_get_temp_dir(), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . 'sess_' . $sessionId;

        try {
            session_name('session');
            session_id($sessionId);
            session_start();
            $_SESSION = [
                _AUTH_ID_ => '1',
                _AUTH_LOGIN_ => 'admin@example.test',
                _AUTH_NAME_ => 'Administrateur Test',
                _AUTH_EMAIL_ => 'admin@example.test',
                _AUTH_CONTACT_ => '+2250102030405',
                _AUTH_TYPE_ => '1',
                AuthGuard::ROLE_SESSION_KEY => 'admin',
                AuthGuard::LAST_ACTIVITY_SESSION_KEY => time(),
            ];
            session_write_close();
            session_name($sessionName);

            $baseUrl = rtrim(getenv('HOMEPAGE_URL') ?: 'http://127.0.0.1:8000', '/');
            $curl = curl_init($baseUrl . '/news/manage_news/');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_TIMEOUT, 5);
            curl_setopt($curl, CURLOPT_HTTPHEADER, ['Cookie: session=' . $sessionId]);
            $response = curl_exec($curl);

            self::assertSame(200, curl_getinfo($curl, CURLINFO_HTTP_CODE));
            self::assertIsString($response);
            self::assertStringContainsString('Gestion des actualités', $response);
            curl_close($curl);
        } finally {
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }
            session_name($sessionName);
            if (is_file($sessionFile)) {
                unlink($sessionFile);
            }
        }
    }

    public function testSessionExpiresAfterThirtyMinutesOfInactivity(): void
    {
        self::assertFalse(AuthGuard::sessionExpired(1000, 2799));
        self::assertTrue(AuthGuard::sessionExpired(1000, 2800));
    }

    public function testPostWithoutACsrfTokenIsRejected(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [];

        self::assertFalse((new token_csrf())->tocsrf());
    }

    protected function tearDown(): void
    {
        putenv($this->previousDriver === false
            ? '1DB_DRIVER'
            : '1DB_DRIVER=' . $this->previousDriver);
        $_POST = $this->previousPost;
        $_SESSION = $this->previousSession;

        if ($this->previousRequestMethod === false) {
            unset($_SERVER['REQUEST_METHOD']);
        } else {
            $_SERVER['REQUEST_METHOD'] = $this->previousRequestMethod;
        }

        parent::tearDown();
    }

    private function removeRoleGroupColumn(): void
    {
        $this->connection->exec('ALTER TABLE roles RENAME TO roles_with_group_id');
        $this->connection->exec(
            'CREATE TABLE roles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                code VARCHAR(30) NOT NULL UNIQUE,
                libelle VARCHAR(50) NOT NULL
            )'
        );
        $this->connection->exec(
            'INSERT INTO roles (id, code, libelle)
             SELECT id, code, libelle FROM roles_with_group_id'
        );
        $this->connection->exec('DROP TABLE roles_with_group_id');
    }
}
