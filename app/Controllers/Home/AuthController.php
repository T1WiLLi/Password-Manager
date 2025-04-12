<?php

namespace Controllers\Home;

use Controllers\Controller;
use Models\Exceptions\FormException;
use Models\PasswordManager\Services\AuthentificationService;
use Models\PasswordManager\Services\EmailVerificationService;
use Models\PasswordManager\Brokers\EmailVerificationBroker;
use Models\PasswordManager\Services\UserService;
use Zephyrus\Application\Flash;
use Zephyrus\Network\Response;
use Zephyrus\Network\Router\Get;
use Zephyrus\Network\Router\Post;

class AuthController extends Controller
{
    private AuthentificationService $authService;
    private EmailVerificationService $emailVerificationService;

    public function __construct()
    {
        $this->authService = new AuthentificationService();
        $this->emailVerificationService = new EmailVerificationService();
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
        Flash::success("Logout successful.");
        return $this->redirect('/login');
    }
}
