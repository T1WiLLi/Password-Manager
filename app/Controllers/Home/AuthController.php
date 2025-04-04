<?php

namespace Controllers\Home;

use Controllers\Controller;
use Models\Exceptions\FormException;
use Models\PasswordManager\Services\AuthentificationService;
use Zephyrus\Application\Flash;
use Zephyrus\Network\Response;
use Zephyrus\Network\Router\Get;
use Zephyrus\Network\Router\Post;

class AuthController extends Controller
{
    private AuthentificationService $authService;

    public function __construct()
    {
        $this->authService = new AuthentificationService();
    }

    /**
     * Displays the login form.
     */
    #[Get('/login')]
    public function loginView(): Response
    {
        return $this->render('login', [
            'title' => 'Login',
            'errors' => []
        ]);
    }

    /**
     * Handles the login form submission.
     */
    #[Post('/login')]
    public function login(): Response
    {
        $errors = [];
        try {
            $form = $this->buildForm();
            $form->addFields([
                'ipAddress' => $_SERVER['REMOTE_ADDR'],
                'userAgent' => $_SERVER['HTTP_USER_AGENT'],
                'location' => null
            ]);
            $user = $this->authService->login($form);
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

    /**
     * Displays the registration form.
     */
    #[Get('/register')]
    public function registerView(): Response
    {
        return $this->render('register', [
            'title' => 'Register',
            'errors' => []
        ]);
    }

    /**
     * Handles the registration form submission.
     */
    #[Post('/register')]
    public function register(): Response
    {
        $errors = [];
        try {
            $form = $this->buildForm();
            $user = $this->authService->register($form);
            Flash::success("Registration successful. Welcome, {$form->getValue("first_name")}!");
            return $this->redirect('/dashboard');
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

    /**
     * Logs out the current user.
     */
    #[Get('/logout')]
    public function logout(): Response
    {
        $this->authService->logout();
        Flash::success("Logout successful.");
        return $this->redirect('/login');
    }
}
