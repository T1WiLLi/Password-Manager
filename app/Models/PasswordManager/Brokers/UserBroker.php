<?php

namespace Models\PasswordManager\Brokers;

use Models\PasswordManager\Entities\User;
use Models\PasswordManager\Services\EncryptionService;
use Zephyrus\Security\Cryptography;

class UserBroker extends Broker
{
    public function __construct()
    {
        parent::__construct("users"); // Table name
    }

    public function existsByEmail(string $email): bool
    {
        $result = $this->selectSingle("SELECT * FROM {$this->table} WHERE email_hash = ?", [EncryptionService::hash256($email)]);
        return !$result ? false : true;
    }

    public function findByIdDecrypt(int $userID, string $encryptionKey): ?User
    {
        return $this->decryptUser($this->findById($userID), $encryptionKey);
    }

    public function findByEmail(string $email): ?User
    {
        $result = $this->selectSingle("SELECT * FROM {$this->table} WHERE email_hash = ?", [EncryptionService::hash256($email)]);
        return $result ? User::build($result) : null;
    }

    public function findByAuthentification(string $email, string $clearPassword, string $encryptionKey): ?User
    {
        // Log incoming parameters (Be careful with logging sensitive data like passwords)
        error_log("[DEBUG] findByAuthentification called with Email: {$email}");

        // Hash the email for lookup
        $emailHash = EncryptionService::hash256($email);
        error_log("[DEBUG] Generated email hash: {$emailHash}");

        // Execute the query to find the user
        $result = $this->selectSingle("SELECT * FROM {$this->table} WHERE email_hash = ?", [$emailHash]);
        if (!$result) {
            error_log("[ERROR] No user found for email hash: {$emailHash}");
            return null;
        }

        error_log("[DEBUG] User found, verifying password...");

        // Verify the hashed password
        if (!Cryptography::verifyHashedPassword($clearPassword, $result->password)) {
            error_log("[ERROR] Password verification failed for user ID: {$result->id}");
            return null;
        }

        error_log("[DEBUG] Password verification successful, decrypting user data...");

        // Decrypt and return the user object
        $user = $this->decryptUser($result, $encryptionKey);
        error_log("[DEBUG] User successfully decrypted: ID {$user->id}, Email Hash: {$emailHash}");

        return $user;
    }


    public function insert(User $user, string $encryptionKey): int
    {
        $encryptedUser = $this->encryptUser($user, $encryptionKey);
        return $this->save($encryptedUser);
    }

    public function update(User $user, $encryptionKey): int
    {
        $encryptedUser = $this->encryptUser($user, $encryptionKey);
        return $this->save($encryptedUser);
    }

    private function encryptUser(User $user, string $encryptionKey): User
    {
        if ($user->username) {
            $user->username = EncryptionService::encrypt($user->username, $encryptionKey);
        }
        if ($user->email) {
            $user->email = EncryptionService::encrypt($user->email, $encryptionKey);
        }
        if ($user->first_name) {
            $user->first_name = EncryptionService::encrypt($user->first_name, $encryptionKey);
        }
        if ($user->last_name) {
            $user->last_name = EncryptionService::encrypt($user->last_name, $encryptionKey);
        }
        if ($user->phone_number) {
            $user->phone_number = EncryptionService::encrypt($user->phone_number, $encryptionKey);
        }

        return $user;
    }

    private function decryptUser(?\stdClass $result, string $encryptionKey): ?User
    {
        if (!$result) {
            return null;
        }

        $user = User::build($result);
        $user->username = EncryptionService::decrypt($user->username, $encryptionKey);
        $user->email = EncryptionService::decrypt($user->email, $encryptionKey);
        $user->first_name = EncryptionService::decrypt($user->first_name, $encryptionKey);
        $user->last_name = EncryptionService::decrypt($user->last_name, $encryptionKey);
        $user->phone_number = EncryptionService::decrypt($user->phone_number, $encryptionKey);
        return $user;
    }
}
