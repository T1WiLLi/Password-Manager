<?php

namespace Controllers\Home;

use Controllers\SecureController;
use Zephyrus\Network\Response;
use Zephyrus\Network\Router\Get;

class Dashboard extends SecureController
{
    #[Get('/dashboard')]
    public function dashboard(): Response
    {
        $user = $this->getUser();
        return $this->render('dashboard', [
            'title' => 'Dashboard',
            'username' => $user->username,
            'picture_set' => $user->profile_image != null,
            'profile_image' => $user->profile_image,
        ]);
    }
}
