<?php

namespace Controllers\Home;

use Controllers\SecureController;
use Models\PasswordManager\Brokers\UserBroker;
use Zephyrus\Network\Response;
use Zephyrus\Network\Router\Get;

class Dashboard extends SecureController
{
    #[Get('/dashboard')]
    public function dashboard(): Response
    {
        $user = (new UserBroker())->findByIdDecrypt($this->getAuth()['user_id'], $this->getAuth()['user_key']);
        return $this->render('dashboard', [
            'title' => 'Dashboard',
            'user' => $user
        ]);
    }

    #[Get('/password')]
    public function password(): Response
    {
        return $this->render('password', [
            'title' => 'Gestion des mots de passe'
        ]);
    }

    #[Get('/sharing')]
    public function sharing(): Response
    {
        return $this->render('sharing', [
            'title' => 'Partage de mot de passe'
        ]);
    }

    #[Get('/profile')]
    public function profile(): Response
    {
        $user = (new UserBroker())->findByIdDecrypt($this->getAuth()['user_id'], $this->getAuth()['user_key']);
        return $this->render('profile', [
            'title' => 'Profil',
            'user' => $user,
            'mfaSettings' => 0
        ]);
    }
}
