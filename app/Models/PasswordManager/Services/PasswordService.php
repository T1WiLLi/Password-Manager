<?php

namespace Models\PasswordManager\Services;

use Models\PasswordManager\Brokers\PasswordBroker;
use Models\PasswordManager\Entities\Password;
use PasswordValidator;
use Zephyrus\Application\Form;

class PasswordService
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

        $password = buildEntity(Password::class, $form);
        $password->id = new PasswordBroker()->insert($password, EncryptionService::getUserKeyFromSession());
        return $password;
    }

    public function updatePassword(Form $form): Password
    {
        $password = updateEntity(Password::build(new PasswordBroker()->findById($form->getValue("id"))), $form, ["id"]);
        new PasswordBroker()->update($password, EncryptionService::getUserKeyFromSession());
        return $password;
    }

    public function updatePasswordEncryption(int $userID, string $newEncryptionKey, string $oldEncryptionKey): void
    {
        $passwords = new PasswordBroker()->findByUserId($userID, $oldEncryptionKey);
        foreach ($passwords as $password) {
            new PasswordBroker()->update($password, $newEncryptionKey);
        }
    }

    public function deletePassword(int $id): void
    {
        new PasswordBroker()->delete($id);
    }
}
