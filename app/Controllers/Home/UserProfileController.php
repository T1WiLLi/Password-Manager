<?php

namespace Controllers\Home;

use Controllers\SecureController;
use Models\PasswordManager\Brokers\UserBroker;
use Zephyrus\Network\Response;
use Zephyrus\Network\Router\Get;

class UserProfileController extends SecureController
{
    #[Get('/me')]
    public function me(): Response
    {
        $user = new UserBroker()->findByIdDecrypt($this->getAuth()["user_id"], $this->getAuth()["user_key"]);
        return $user != null ? $this->json($user) : $this->abortNotFound("Utilisateur introuvable.");
    }
}
