<?php

namespace Models\PasswordManager\Services;

use Models\PasswordManager\Brokers\UserBroker;
use Models\PasswordManager\Entities\User;
use Models\PasswordManager\Validators\UserValidator;
use Zephyrus\Application\Form;
use Zephyrus\Security\Cryptography;

class UserService
{
    public function getUser(int $userId): User
    {
        return new UserBroker()->findByIdDecrypt($userId, EncryptionService::getUserKeyFromSession());
    }

    public function existsByEmail(string $email): bool
    {
        return new UserBroker()->existsByEmail($email);
    }

    public function getIdByEmail(string $email): int
    {
        return new UserBroker()->findByEmail($email)->id;
    }

    public function updateUser(Form $form): User
    {
        $user = new UserBroker()->findByIdDecrypt($form->getValue("id"), EncryptionService::getUserKeyFromSession());
        UserValidator::validateUser($form, $user);
        $user = updateEntity($user, $form, ["id"]);
        new UserBroker()->update($user, EncryptionService::getUserKeyFromSession());
        return $user;
    }

    public function updateUserPassword(Form $form): User
    {
        $oldEncryptionKey = EncryptionService::getUserKeyFromSession();
        $user = new UserBroker()->findByIdDecrypt($form->getValue("id"), $oldEncryptionKey);
        UserValidator::validateUserPassword($form, $user);

        $newSalt = EncryptionService::generateSalt();
        $newEncryptionKey = EncryptionService::deriveEncryptionKey($form->getValue("password"), $newSalt);
        $user->password = Cryptography::hashPassword($form->getValue("password"));
        $user->salt = $newSalt;

        EncryptionService::storeUserKeyInSession($user->id, $newEncryptionKey);
        new PasswordService()->updatePasswordEncryption($user->id, $newEncryptionKey, $oldEncryptionKey);
        new UserBroker()->update($user, $newEncryptionKey);
        return $user;
    }

    public function isUserVerified(int $userID): bool
    {
        return new UserBroker()->isUserVerified($userID);
    }
}
