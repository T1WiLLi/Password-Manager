<?php

namespace Controllers;

use Models\PasswordManager\Entities\User;
use Models\PasswordManager\Services\EncryptionService as ServicesEncryptionService;
use Models\PasswordManager\Services\UserService;
use Zephyrus\Application\Controller;
use Zephyrus\Network\Response;

abstract class SecureController extends Controller
{
    protected ?string $currentUserId = null;
    protected ?string $currentUserKey = null;

    public function before(): ?Response
    {
        $this->currentUserKey = ServicesEncryptionService::getUserKeyFromSession();
        $this->currentUserId = ServicesEncryptionService::getUserIdFromSession();

        if (is_null($this->currentUserKey) || is_null($this->currentUserId)) {
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
