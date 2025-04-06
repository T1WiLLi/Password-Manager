<?php

namespace Controllers\Home;

use Controllers\SecureController;
use Models\PasswordManager\Services\UserService;
use Zephyrus\Network\Response;
use Zephyrus\Network\Router\Get;
use Zephyrus\Network\Router\Post;

class Dashboard extends SecureController
{
    private UserService $userService;

    public function __construct()
    {
        $this->userService = new UserService();
    }

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
