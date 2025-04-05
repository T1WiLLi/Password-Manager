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
            'username' => $user->username,
            'profile_picture' => $user->username[0],
        ]);
    }
}
