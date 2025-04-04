<?php

namespace Controllers\Home;

use Controllers\SecureController;
use Models\PasswordManager\Brokers\UserBroker;
use Zephyrus\Network\Response;
use Zephyrus\Network\Router\Get;

class Dashboard extends SecureController
{
    /**
     * Displays the current user's profile.
     */
    #[Get('/me')]
    public function me(): Response
    {
        $user = (new UserBroker())->findByIdDecrypt($this->getAuth()['user_id'], $this->getAuth()['user_key']);
        if (!$user) {
            return $this->abortNotFound("User not found.");
        }

        return $this->render('me', [
            'title' => 'My Profile',
            'user' => $user
        ]);
    }
}
