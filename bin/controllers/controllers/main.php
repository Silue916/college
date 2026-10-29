<?php

namespace Epaphrodites\controllers\controllers;

use Epaphrodites\controllers\switchers\MainSwitchers;
use Epaphrodites\services\AuthenticationConfig;
use Epaphrodites\services\AuthenticationService;
use Epaphrodites\services\AuthGuard;
use RuntimeException;

final class main extends MainSwitchers
{
    private object $visit;
    private object $session;
    private string $ans = '';
    private string $alert = '';
    
    public function __construct()
    {
        $this->initializeObjects();
    }

    /**
     * @return void
     */
    private function initializeObjects(): void
    {
        $this->visit = $this->getObject(static::$initNamespace, 'visit');
        $this->session = $this->getFunctionObject(static::initNamespace(), 'session');
    }

    /**
     * Index page
     * 
     * @param string $html
     * @return void
     */
    public final function index(
        string $html
    ):void
    {

        $this->views($html, []);
    }
    
    /**
     * Authentification page ( login )
     * 
     * @param string $html
     * @return void
     */
    public final function login(
        string $html
    ): void
    {

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $identifier = $_POST['identifier'] ?? '';
            $password = $_POST['password'] ?? '';
            $user = is_string($identifier) && is_string($password)
                ? (new AuthenticationService())->authenticate($identifier, $password)
                : null;

            if ($user === null) {
                $this->ans = 'Identifiant ou mot de passe incorrect.';
                $this->alert = 'alert-danger';
            } elseif ($user['role'] === null) {
                $this->ans = 'Ce compte n’a pas de rôle scolaire attribué.';
                $this->alert = 'alert-danger';
            } else {
                static::initConfig()['auth']->StartUsersSession(
                    $user['id'],
                    $user['login'],
                    $user['namesurname'],
                    $user['contact'],
                    $user['email'],
                    $user['usersgroup'],
                    null,
                    0
                );
                $_SESSION[AuthGuard::ROLE_SESSION_KEY] = $user['role'];
                $_SESSION[AuthGuard::LAST_ACTIVITY_SESSION_KEY] = time();
                $_SESSION[AuthGuard::PASSWORD_CHANGE_SESSION_KEY] = $user['must_change_password'];

                if ($user['must_change_password']) {
                    header('Location: /first_password_change/', true, 303);
                }

                return;
            }
        }

        $this->views( $html,
            [
                'class' => $this->alert,
                'reponse' => $this->ans
            ]
        );
    }

    public final function connexion(string $html): void
    {
        $this->login($html);
    }

    public final function forgotPassword(string $html): void
    {
        $message = '';
        $alert = '';

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $email = $_POST['email'] ?? '';
            if (is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
                $configuration = AuthenticationConfig::local();
                $appUrl = $configuration['app_url'];
                if (filter_var($appUrl, FILTER_VALIDATE_URL) === false) {
                    throw new RuntimeException('APP_URL doit contenir une URL absolue valide.');
                }

                (new AuthenticationService())->requestPasswordReset(
                    $email,
                    static function (string $recipient, string $token) use ($appUrl): bool {
                        $url = $appUrl . '/reset_password/?token=' . rawurlencode($token);
                        $body = '<p>Pour réinitialiser votre mot de passe, ouvrez ce lien valable une heure :</p>'
                            . '<p><a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">Réinitialiser mon mot de passe</a></p>';

                        return static::initNamespace()['mail']->sendEmail(
                            [$recipient],
                            'Réinitialisation du mot de passe',
                            $body
                        ) === true;
                    }
                );
            }

            $message = 'Si cette adresse est associée à un compte, un lien de réinitialisation vient d’être envoyé.';
            $alert = 'alert-info';
        }

        $this->views($html, ['reponse' => $message, 'alert' => $alert]);
    }

    public final function resetPassword(string $html): void
    {
        $message = '';
        $alert = '';
        $token = $_GET['token'] ?? ($_POST['token'] ?? '');

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $password = $_POST['password'] ?? '';
            $confirmation = $_POST['password_confirmation'] ?? '';

            if (!is_string($token) || !is_string($password) || $password !== $confirmation) {
                $message = 'Les mots de passe ne correspondent pas.';
                $alert = 'alert-danger';
            } elseif (!AuthenticationService::isValidPassword($password)) {
                $message = 'Le mot de passe doit contenir au moins 8 caractères, une lettre et un chiffre.';
                $alert = 'alert-danger';
            } elseif ((new AuthenticationService())->resetPassword($token, $password)) {
                $message = 'Votre mot de passe a été réinitialisé. Vous pouvez vous connecter.';
                $alert = 'alert-success';
            } else {
                $message = 'Ce lien est invalide, expiré ou déjà utilisé.';
                $alert = 'alert-danger';
            }
        }

        $this->views($html, [
            'token' => is_string($token) ? $token : '',
            'reponse' => $message,
            'alert' => $alert,
        ]);
    }

    public final function firstPasswordChange(string $html): void
    {
        AuthGuard::requireAuth(true);
        $message = '';
        $alert = '';

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $password = $_POST['password'] ?? '';
            $confirmation = $_POST['password_confirmation'] ?? '';

            if (
                !is_string($password)
                || !is_string($confirmation)
                || $password !== $confirmation
            ) {
                $message = 'Les mots de passe ne correspondent pas.';
                $alert = 'alert-danger';
            } elseif (!AuthenticationService::isValidPassword($password)) {
                $message = 'Le mot de passe doit contenir au moins 8 caractères, une lettre et un chiffre.';
                $alert = 'alert-danger';
            } else {
                (new AuthenticationService())->changePassword(
                    (int) $_SESSION[_AUTH_ID_],
                    $password
                );
                $_SESSION[AuthGuard::PASSWORD_CHANGE_SESSION_KEY] = false;
                $_SESSION[AuthGuard::LAST_ACTIVITY_SESSION_KEY] = time();
                session_regenerate_id(true);
                header('Location: /dashboard/', true, 303);
                return;
            }
        }

        $this->views($html, ['reponse' => $message, 'alert' => $alert]);
    }

    /**
     * OTP verification page (OTP)
     * 
     * @param string $html
     * @return void
     */
    public final function confirmOtpCodeSendByEmail(
        string $html
    ):void
    {

        if (static::isValidMethod(true)) {

            static::initConfig()['otp']->verificateOTP(
                static::getPost('__otp__')
            );
        }

        $this->views($html, 
        [
            'email' => $this->session->email()
        ]
    );
    }

    /**
    * start view function
    * 
    * @param string $html
    * @return void
    */
     public final function accueil(string $html): void{
    
        $this->views( $html, [], false );
    }

    /**
    * start view function
    * 
    * @param string $html
    * @return void
    */
     public final function etablissement(string $html): void{
    
        $this->views( $html, [], false );
    }

    /**
    * start view function
    * 
    * @param string $html
    * @return void
    */
     public final function admission(string $html): void{
    
        $this->views( $html, [], false );
    }

    /**
    * start view function
    * 
    * @param string $html
    * @return void
    */
     public final function frais(string $html): void{
    
        $this->views( $html, [], false );
    }

    /**
    * start view function
    * 
    * @param string $html
    * @return void
    */
     public final function contact(string $html): void{
    
        $this->views( $html, [], false );
    }

    /**
    * start view function
    * 
    * @param string $html
    * @return void
    */
     public final function actualites(string $html): void{
    
        $this->views( $html, [], false );
    }
}