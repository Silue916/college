<?php

namespace Epaphrodites\controllers\controllers;

use Epaphrodites\controllers\switchers\MainSwitchers;
use Epaphrodites\services\AuthenticationConfig;
use Epaphrodites\services\AuthenticationService;
use Epaphrodites\services\AuthGuard;
use Epaphrodites\services\ContactFormService;
use Epaphrodites\services\NewsService;
use Epaphrodites\services\PreinscriptionService;
use Epaphrodites\services\PreinscriptionValidationException;
use Epaphrodites\services\PublicSiteConfig;
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
        $this->renderPublic($html);
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
        $this->renderPublic($html);
    }

    /**
    * start view function
    * 
    * @param string $html
    * @return void
    */
     public final function etablissement(string $html): void{
        $this->renderPublic($html);
    }

    /**
    * start view function
    * 
    * @param string $html
    * @return void
    */
     public final function admission(string $html): void{
        $this->renderPublic($html);
    }

    /**
    * start view function
    * 
    * @param string $html
    * @return void
    */
     public final function frais(string $html): void{
        $this->renderPublic($html);
    }

    /**
    * start view function
    * 
    * @param string $html
    * @return void
    */
     public final function contact(string $html): void{
        $values = [
            'name' => $_POST['name'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'email' => $_POST['email'] ?? '',
            'message' => $_POST['message'] ?? '',
            'website' => $_POST['website'] ?? '',
        ];
        foreach ($values as $key => $value) {
            $values[$key] = is_string($value) ? $value : '';
        }

        $errors = [];
        $message = '';
        $alert = '';
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $service = new ContactFormService();
            if (trim($values['website']) !== '') {
                $service->submit(
                    $values,
                    (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
                    static fn (array $contact): bool => false
                );
                $message = 'Votre message a bien été pris en compte.';
                $alert = 'success';
            } else {
                $errors = ContactFormService::validate($values);
                if ($errors === []) {
                    $site = PublicSiteConfig::local();
                    if (filter_var($site['contact_email'], FILTER_VALIDATE_EMAIL) === false) {
                        throw new RuntimeException(
                            'Configurez SITE.CONTACT_EMAIL dans bin/config/Config.ini pour recevoir les messages.'
                        );
                    }

                    $result = $service->submit(
                        $values,
                        (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
                        static function (array $contact) use ($site): bool {
                            $body = '<h2>Message du formulaire de contact</h2>'
                                . '<p><strong>Nom :</strong> '
                                . htmlspecialchars($contact['name'], ENT_QUOTES, 'UTF-8')
                                . '</p><p><strong>Téléphone :</strong> '
                                . htmlspecialchars($contact['phone'], ENT_QUOTES, 'UTF-8')
                                . '</p><p><strong>E-mail :</strong> '
                                . htmlspecialchars($contact['email'], ENT_QUOTES, 'UTF-8')
                                . '</p><p><strong>Message :</strong><br>'
                                . nl2br(htmlspecialchars($contact['message'], ENT_QUOTES, 'UTF-8'))
                                . '</p>';

                            return static::initNamespace()['mail']->sendEmail(
                                [$site['contact_email']],
                                'Nouveau message depuis le site',
                                $body
                            ) === true;
                        }
                    );

                    if ($result === 'rate_limited') {
                        http_response_code(429);
                        $message = 'La limite de trois messages par heure est atteinte. Réessayez plus tard.';
                        $alert = 'danger';
                    } else {
                        $message = 'Votre message a bien été envoyé.';
                        $alert = 'success';
                        $values = ['name' => '', 'phone' => '', 'email' => '', 'message' => '', 'website' => ''];
                    }
                }
            }
        }

        $this->renderPublic($html, [
            'contact_values' => $values,
            'contact_errors' => $errors,
            'contact_message' => $message,
            'contact_alert' => $alert,
        ]);
    }

    /**
    * start view function
    * 
    * @param string $html
    * @return void
    */
     public final function actualites(string $html): void{
        $this->renderPublic($html, [
            'news' => (new NewsService())->listPublished(),
        ]);
    }

    public final function actualite(string $html): void
    {
        $id = $_GET['id'] ?? null;
        $article = is_string($id) && ctype_digit($id)
            ? (new NewsService())->findPublished((int) $id)
            : null;

        if ($article === null) {
            http_response_code(404);
            $this->views('errors/404.html', PublicSiteConfig::viewData(), false);
            return;
        }

        $this->renderPublic($html, ['article' => $article]);
    }

    public final function preinscription(string $html): void
    {
        $service = new PreinscriptionService();
        $number = $_GET['numero'] ?? null;
        if (is_string($number) && $number !== '') {
            $confirmation = preg_match('/^PRE-[0-9]{4}-[0-9]{5,}$/', $number) === 1
                ? $service->findForConfirmation($number)
                : null;
            if ($confirmation === null) {
                http_response_code(404);
                $this->views('errors/404.html', PublicSiteConfig::viewData(), false);
                return;
            }

            $emailNotice = $_SESSION['preinscription_email_notice'] ?? '';
            unset($_SESSION['preinscription_email_notice']);
            $this->renderPublic($html, [
                'confirmation' => $confirmation,
                'email_notice' => is_string($emailNotice) ? $emailNotice : '',
            ]);
            return;
        }

        $formValues = $this->preinscriptionValues();
        $errors = [];
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $formValues = $this->preinscriptionValues($_POST);
            try {
                $confirmation = $service->submit($formValues, $_FILES);
                $email = trim($formValues['email']);
                if ($email !== '') {
                    $body = '<p>Votre demande de préinscription a bien été reçue.</p>'
                        . '<p>Numéro de dossier : <strong>'
                        . htmlspecialchars($confirmation['numero_dossier'], ENT_QUOTES, 'UTF-8')
                        . '</strong></p><p>Conservez ce numéro pour suivre votre dossier.</p>';
                    $sent = static::initNamespace()['mail']->sendEmail(
                        [$email],
                        'Confirmation de votre préinscription',
                        $body
                    );
                    $_SESSION['preinscription_email_notice'] = $sent === true
                        ? 'Un e-mail de confirmation vous a été envoyé.'
                        : 'Votre dossier est enregistré, mais l’e-mail de confirmation n’a pas pu être envoyé.';
                } else {
                    $_SESSION['preinscription_email_notice'] = '';
                }

                header(
                    'Location: /preinscription/?numero=' . rawurlencode($confirmation['numero_dossier']),
                    true,
                    303
                );
                return;
            } catch (PreinscriptionValidationException $exception) {
                $errors = $exception->errors();
            }
        }

        $this->renderPublic($html, [
            'form_values' => $formValues,
            'form_errors' => $errors,
            'niveaux' => $service->getNiveaux(),
            'series' => $service->getSeries(),
            'first_error_step' => self::firstPreinscriptionErrorStep($errors),
        ]);
    }

    public final function suiviPreinscription(string $html): void
    {
        $number = '';
        $birthDate = '';
        $status = null;
        $message = '';

        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $number = isset($_POST['numero_dossier']) && is_string($_POST['numero_dossier'])
                ? trim($_POST['numero_dossier'])
                : '';
            $birthDate = isset($_POST['date_naissance']) && is_string($_POST['date_naissance'])
                ? trim($_POST['date_naissance'])
                : '';
            if ($number === '' || $birthDate === '') {
                $message = 'Saisissez le numéro de dossier et la date de naissance de l’élève.';
            } else {
                $status = (new PreinscriptionService())->findStatusForTracking($number, $birthDate);
                if ($status === null) {
                    $message = 'Aucun dossier ne correspond aux informations saisies.';
                }
            }
        }

        $this->renderPublic($html, [
            'numero_dossier' => $number,
            'date_naissance' => $birthDate,
            'dossier_statut' => $status,
            'suivi_message' => $message,
        ]);
    }

    /**
     * @param array<string, mixed> $source
     * @return array<string, string>
     */
    private function preinscriptionValues(array $source = []): array
    {
        $fields = [
            'nom',
            'prenoms',
            'date_naissance',
            'lieu_naissance',
            'sexe',
            'nationalite',
            'matricule_mena',
            'statut_affectation',
            'etablissement_origine',
            'niveau_id',
            'serie_id',
            'responsable_nom',
            'lien_parente',
            'telephone',
            'email',
            'profession',
            'commune',
        ];
        $values = [];
        foreach ($fields as $field) {
            $value = $source[$field] ?? '';
            $values[$field] = is_string($value) ? trim($value) : '';
        }

        return $values;
    }

    /**
     * @param array<string, string> $errors
     */
    private static function firstPreinscriptionErrorStep(array $errors): int
    {
        $responsibleFields = [
            'responsable_nom',
            'lien_parente',
            'telephone',
            'email',
            'profession',
            'commune',
        ];
        $documentFields = ['extrait_acte', 'bulletin', 'photo_identite'];
        foreach ($errors as $field => $_message) {
            if (in_array($field, $responsibleFields, true)) {
                return 2;
            }
            if (in_array($field, $documentFields, true)) {
                return 3;
            }
        }

        return 1;
    }

    /**
     * @param array<string, mixed> $content
     */
    private function renderPublic(string $html, array $content = []): void
    {
        $this->views($html, array_merge(PublicSiteConfig::viewData(), $content), false);
    }
}