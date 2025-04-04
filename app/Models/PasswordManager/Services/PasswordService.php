<?php

namespace Models\PasswordManager\Services;

use Models\PasswordManager\Brokers\PasswordBroker;
use Models\PasswordManager\Entities\Password;
use PasswordValidator;
use Zephyrus\Application\Form;

class PasswordManagerService
{
    public function getAllPasswords($userID): array
    {
        $passwords = new PasswordBroker()->findByUserId($userID, EncryptionService::getUserKeyFromSession());
        return $passwords;
    }

    public function revealPassword(int $id): string
    {
        $password = new PasswordBroker()->findById($id);
        if ($password === null) {
            throw new \Exception("Password not found.");
        }
        return EncryptionService::decrypt($password->password, EncryptionService::getUserKeyFromSession());
    }

    public function createPassword(Form $form): Password
    {
        PasswordValidator::validatePassword($form);

        if (new PasswordBroker()->existsByServiceAndUsername($form->getValue("service_name"), $form->getValue("username"))) {
            throw new \Exception("Password already exists for this service and username.");
        }

        $password = $form->buildEntity(Password::class);
        $password->id = new PasswordBroker()->insert($password, EncryptionService::getUserKeyFromSession());
        return $password;
    }

    public function updatePassword(Form $form): Password
    {
        $password = $form->updateEntity(Password::build(new PasswordBroker()->findById($form->getValue("id"))), ["id"]);
        new PasswordBroker()->update($password, EncryptionService::getUserKeyFromSession());
        return $password;
    }

    public function deletePassword(int $id): void
    {
        new PasswordBroker()->delete($id);
    }
}
