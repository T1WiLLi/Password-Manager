<?php

namespace Controllers\Home;

use Controllers\Controller;
use Models\Exceptions\FormException;
use Models\PasswordManager\Services\AuthentificationService;
use Models\PasswordManager\Services\EmailVerificationService;
use Models\PasswordManager\Brokers\EmailVerificationBroker;
use Models\PasswordManager\Services\MfaService;
use Models\PasswordManager\Services\UserService;
use Zephyrus\Application\Flash;
use Zephyrus\Core\Session;
use Zephyrus\Network\Response;
use Zephyrus\Network\Router\Get;
use Zephyrus\Network\Router\Post;

class AuthController extends Controller
{
    private AuthentificationService $authService;
    private EmailVerificationService $emailVerificationService;
    private MfaService $mfaService;

    public function __construct()
    {
        $this->authService = new AuthentificationService();
        $this->emailVerificationService = new EmailVerificationService();
        $this->mfaService = new MfaService();
    }

    #[Get('/login')]
    public function loginView(): Response
    {
        return $this->render('login', [
            'title' => 'Login',
            'errors' => [],
            'success' => $this->request->getParameter('success')
        ]);
    }

    #[Post('/login')]
    public function login(): Response
    {
        $errors = [];
        try {
            $form = $this->buildForm();
            $form->addFields([
                'ipAddress' => $_SERVER['REMOTE_ADDR'],
                'userAgent' => $_SERVER['HTTP_USER_AGENT'],
                'location' => "Sorel-Tracy"
            ]);
            $user = $this->authService->login($form);

            if (!new UserService()->isUserVerified($user->id)) {
                $existingVerification = (new EmailVerificationBroker())->findByUserID($user->id);
                if (!$existingVerification) {
                    $this->emailVerificationService->createVerification($user->id, $user->email);
                }
                $errors['general'] = 'Please verify your email before logging in. Check your inbox for the verification link.';
                return $this->render('login', [
                    'title' => 'Login',
                    'errors' => $errors
                ]);
            }

            $now = new \DateTime();
            $grace = $user->mfa_grace_period_until ? new \DateTime($user->mfa_grace_period_until) : new \DateTime("@0");

            $needs = false;
            foreach ([MfaService::TYPE_EMAIL, MfaService::TYPE_SMS, MfaService::TYPE_AUTHENTICATOR] as $method) {
                if ($this->mfaService->isMethodEnabled($user->id, $method) && $now > $grace) {
                    $needs = true;
                    break;
                }
            }
            if ($needs) {
                Session::set('pending_mfa_user', $user->id);
                Session::set('mfa_verified', []);
                return $this->redirect('/mfa');
            }

            Session::set("is_logged_in", true);
            Flash::success("Login successful. Welcome, {$user->first_name}!");
            return $this->redirect('/dashboard');
        } catch (FormException $e) {
            $errors = $e->getForm()->getErrorMessages();
        } catch (\Exception $e) {
            $errors['general'] = $e->getMessage();
        }
        return $this->render('login', [
            'title' => 'Login',
            'errors' => $errors
        ]);
    }

    #[Get('/mfa')]
    public function mfaView(): Response
    {
        $userID = Session::get("pending_mfa_user") ?? null;
        if ($userID === null) {
            return $this->redirect('/login', ['error' => 'No pending MFA session found.']);
        }

        $success = Session::get('mfa_success', '');
        $errors = Session::get('mfa_errors', []);
        $errorMessage = !empty($errors['general']) ? $errors['general'] : '';
        Session::remove('mfa_success');
        Session::remove('mfa_errors');

        $verified = Session::get('mfa_verified', []);

        $enabled = array_filter([
            'email'         => $this->mfaService->isMethodEnabled($userID, MfaService::TYPE_EMAIL),
            'sms'           => $this->mfaService->isMethodEnabled($userID, MfaService::TYPE_SMS),
            'authenticator' => $this->mfaService->isMethodEnabled($userID, MfaService::TYPE_AUTHENTICATOR),
        ]);

        return $this->render('mfa', [
            'title'    => 'Multi-Factor Authentication',
            'methods'  => array_keys($enabled),
            'success'  => $success,
            'errors'   => $errorMessage,
            'verified' => $verified
        ]);
    }

    #[Get('/mfa/qrcode')]
    public function mfaQrCode(): Response
    {
        $userId = Session::get('pending_mfa_user') ?? null;
        if (!$userId) {
            return $this->redirect('/login');
        }
        $qr = $this->mfaService->getAuthenticatorQrCode($userId);
        return new Response($qr, 200, [
            'Content-Type' => 'text/plain'
        ]);
    }


    #[Post('/mfa')]
    public function mfa(): Response
    {
        $userId = Session::get('pending_mfa_user');
        if (!$userId) {
            return $this->redirect('/login');
        }

        $form = $this->buildForm();
        $action = $form->getValue('action');
        $methodType = $form->getValue('method_type');
        $code = $form->getValue('code');

        if ($action === 'send') {
            try {
                if ($methodType === 'email') {
                    $ok = $this->mfaService->sendEmailMfaCode($userId);
                } else { // sms
                    $ok = $this->mfaService->sendSmsMfaCode($userId);
                }
                Session::set('mfa_success', $ok
                    ? ucfirst($methodType) . " code sent!"
                    : "Failed to send $methodType code.");
                Session::set('mfa_errors', ['general' => '']);
            } catch (\Exception $e) {
                Session::set('mfa_errors', ['general' => $e->getMessage()]);
                Session::set('mfa_success', '');
            }
            return $this->redirect('/mfa');
        }

        if ($action === 'verify') {
            try {
                $ok = match ($methodType) {
                    'email'         => $this->mfaService->verifyEmailMfaCode($userId, $code),
                    'sms'           => $this->mfaService->verifySmsMfaCode($userId, $code),
                    'authenticator' => $this->mfaService->verifyAuthenticatorCode($userId, $code),
                    default         => false
                };
                if (!$ok) {
                    throw new \RuntimeException('Invalid code, please try again.');
                }

                $verified = Session::get('mfa_verified', []);
                if (!in_array($methodType, $verified)) {
                    $verified[] = $methodType;
                    Session::set('mfa_verified', $verified);
                }

                $methods = Session::get('mfa_verified', []);
                $enabled = array_keys(array_filter([
                    'email'         => $this->mfaService->isMethodEnabled($userId, MfaService::TYPE_EMAIL),
                    'sms'           => $this->mfaService->isMethodEnabled($userId, MfaService::TYPE_SMS),
                    'authenticator' => $this->mfaService->isMethodEnabled($userId, MfaService::TYPE_AUTHENTICATOR),
                ]));
                if (count($methods) === count($enabled)) {
                    Session::remove('pending_mfa_user');
                    Session::remove('mfa_verified');
                    Session::set('is_logged_in', true);
                    return $this->redirect('/dashboard');
                }

                Session::set('mfa_success', "{$methodType} verified!");
                Session::set('mfa_errors', ['general' => '']);
            } catch (\Throwable $e) {
                Session::set('mfa_errors', ['general' => $e->getMessage()]);
                Session::set('mfa_success', '');
            }
            return $this->redirect('/mfa');
        }

        return $this->redirect('/mfa');
    }

    #[Get('/register')]
    public function registerView(): Response
    {
        return $this->render('register', [
            'title' => 'Register',
            'errors' => []
        ]);
    }

    #[Post('/register')]
    public function register(): Response
    {
        $errors = [];
        try {
            $form = $this->buildForm();
            $user = $this->authService->register($form);

            if ($this->emailVerificationService->createVerification($user->id, $form->getValue('email'))) {
                return $this->redirect('/login', ['success' => "Registration successful. Please check your email to verify your account, {$form->getValue('first_name')}!"]);
            }

            throw new \Exception('Failed to send verification email');
        } catch (FormException $e) {
            $errors = $e->getForm()->getErrorMessages();
        } catch (\Exception $e) {
            $errors['general'] = $e->getMessage();
        }
        return $this->render('register', [
            'title' => 'Register',
            'errors' => $errors
        ]);
    }

    #[Get('/logout')]
    public function logout(): Response
    {
        $this->authService->logout();
        Session::remove('is_logged_in');
        Flash::success("Logout successful.");
        return $this->redirect('/login');
    }
}
