<?php

namespace Models\PasswordManager\Services;

use Models\PasswordManager\Brokers\LoginAttemptsBroker;
use Models\PasswordManager\Brokers\UserBroker;
use Models\PasswordManager\Entities\LoginAttempt;
use Models\PasswordManager\Entities\User;
use Models\PasswordManager\Validators\AuthentificationValidator;
use Zephyrus\Application\Form;
use Zephyrus\Security\Cryptography;

class AuthentificationService
{
    public function register(Form $form): User
    {
        AuthentificationValidator::validateRegister($form);

        if (new UserBroker()->existsByEmail($form->getValue("email"))) {
            throw new \Exception("Email already exists");
        }

        $user = new User();
        $user->username = $form->getValue("username");
        $user->email = $form->getValue("email");
        $user->email_hash = EncryptionService::hash256($user->email);
        $user->password = Cryptography::hashPassword($form->getValue("password"));
        $user->salt = EncryptionService::generateSalt(32); // 16 bytes salt
        $user->first_name = $form->getValue("first_name");
        $user->last_name = $form->getValue("last_name");
        $user->phone_number = $form->getValue("phone_number") ?? null;
        $user->created_at = date('Y-m-d H:i:s');
        $user->updated_at = date('Y-m-d H:i:s');

        $encryptionKey = EncryptionService::deriveEncryptionKey($form->getValue("password"), $user->salt);

        $userId = new UserBroker()->insert($user, $encryptionKey);
        $user->id = $userId;

        EncryptionService::storeUserKeyInSession($userId, $encryptionKey);
        return $user;
    }

    public function login(Form $form): User
    {
        AuthentificationValidator::validateLogin($form);

        $email = $form->getValue("email");
        $ipAddress = $form->getValue("ipAddress");
        $userAgent = $form->getValue("userAgent");
        $location = $form->getValue("location");

        $user = new UserBroker()->findByEmail($email);
        $userID = $user ? $user->id : 0; // 0 if user is not found

        if (!$user) {
            throw new \Exception("Invalid email or password.");
        }

        $encryptionKey = EncryptionService::deriveEncryptionKey($form->getValue("password"), $user->salt);
        $authenticatedUser = new UserBroker()->findByAuthentification($email, $form->getValue("password"), $encryptionKey);

        $status = $authenticatedUser ? "success" : "failure";

        new LoginAttemptsBroker()->save($this->generateLoginAttemps($userID, $ipAddress, $userAgent, $status, $location));

        if (!$authenticatedUser) {
            throw new \Exception("Invalid email or password.");
        }

        EncryptionService::storeUserKeyInSession($authenticatedUser->id, $encryptionKey);
        new PasswordSharingService()->activateSharing();
        return $authenticatedUser;
    }


    public function logout(): void
    {
        EncryptionService::destroySession();
    }

    private function generateLoginAttemps(int $userId, $ipAddress, $userAgent, $status, $location): LoginAttempt
    {
        $loginAttempts = new LoginAttempt();
        $loginAttempts->user_id = $userId;
        $loginAttempts->ip_address = $ipAddress;
        $loginAttempts->user_agent = $userAgent;
        $loginAttempts->status = $status;
        $loginAttempts->location = $location;
        return $loginAttempts;
    }
}
