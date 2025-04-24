<?php

namespace Controllers;

use Models\PasswordManager\Entities\User;
use Models\PasswordManager\Services\EncryptionService;
use Models\PasswordManager\Services\UserService;
use Zephyrus\Application\Controller;
use Zephyrus\Core\Session;
use Zephyrus\Network\Response;

abstract class SecureController extends Controller
{
    private ?string $currentUserId = null;
    private ?string $currentUserKey = null;

    public function before(): ?Response
    {
        $this->currentUserKey = EncryptionService::getUserKeyFromSession();
        $this->currentUserId = EncryptionService::getUserIdFromSession();

        if (is_null($this->currentUserKey) || is_null($this->currentUserId) || is_null(Session::get('is_logged_in'))) {
            return $this->redirect("/login");
        }
        return parent::before();
    }

    public function getAuth(): array
    {
        return ["user_id" => $this->currentUserId, "user_key" => $this->currentUserKey];
    }

    public function getUser(): User
    {
        return new UserService()->getUser($this->currentUserId, $this->currentUserKey);
    }
}
