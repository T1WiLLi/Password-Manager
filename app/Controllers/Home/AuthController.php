<?php

namespace Controllers\Home;

use Controllers\Controller;
use Controllers\SecureController;
use Models\PasswordManager\Brokers\UserBroker;
use Models\PasswordManager\Services\AuthentificationService;
use Zephyrus\Application\Flash;
use Zephyrus\Network\Response;
use Zephyrus\Network\Router\Get;

class AuthController extends Controller
{

    #[Get('/login')]
    public function loginView(): Response
    {
        return $this->render('login', [
            'title' => 'Connexion',
        ]);
    }

    #[Get('/register')]
    public function registerView(): Response
    {
        return $this->render('register', [
            'title' => 'Inscription',
        ]);
    }

    #[Get('/logout')]
    public function logout(): Response
    {
        new AuthentificationService()->logout();
        Flash::success("Déconnexion réussie.");
        return $this->redirect('/login');
    }
}
