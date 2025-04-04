<?php

namespace Controllers;

use Models\PasswordManager\Services\EncryptionService as ServicesEncryptionService;
use Zephyrus\Application\Controller;
use Zephyrus\Network\Response;

abstract class SecureController extends Controller
{
    protected ?string $currentUserId = null;
    protected ?string $currentUserKey = null;

    public function before(): ?Response
    {
        $this->currentUserKey = ServicesEncryptionService::getUserKeyFromSession();
        $this->currentUserId = ServicesEncryptionService::getUserIdFromContext();

        if (is_null($this->currentUserKey) || is_null($this->currentUserId)) {
            return $this->redirect("/login");
            return $this->abortUnauthorized("Session invalide ou expirée.");
        }

        return parent::before();
    }

    public function getAuth(): array
    {
        return ["user_id" => $this->currentUserId, "user_key" => $this->currentUserKey];
    }
}
