<?php

namespace Models\PasswordManager\Brokers;

use App\Models\Entities\User;
use Zephyrus\Security\Cryptography;

class UserBroker extends Broker
{
    public function __construct()
    {
        parent::__construct("users"); // Table name
    }

    public function existsByEmail(string $email): bool
    {
        $emailHash = Cryptography::hash($email, "sha256");
        $result = $this->selectSingle("SELECT * FROM {$this->table} WHERE email_hash = ?", [$emailHash]);
        if (!$result) {
            return false;
        }
        return true;
    }

    public function findByAuthentification(string $email, string $clearPassword): ?User
    {
        $emailHash = Cryptography::hash($email, "sha256");
        $result = $this->selectSingle("SELECT * FROM {$this->table} WHERE email_hash = ?", [$emailHash]);
        if (!$result) {
            return null;
        }
        if (!Cryptography::verifyHashedPassword($clearPassword, $result->password)) {
            return null;
        }
        $encryptionKey = deriveEncryptionKey($clearPassword, $result->salt);
        // Add to session object
        return $this->decryptUser($result, $encryptionKey);
    }

    public function insert(User $user): int
    {
        $salt = generateSalt();
        $encryptionKey = deriveEncryptionKey($user->password, $salt);
        $user->salt = $salt;
        $user->password = Cryptography::hashPassword($user->password);
        $user->emailHash = Cryptography::hash($user->email, "sha256");
        $encryptedUser = $this->encryptUser($user, $encryptionKey);
        // Add encryption key to session object and encrypt with encryption_project_key
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
            $user->username = encrypt($user->username, $encryptionKey);
        }
        if ($user->email) {
            $user->email = encrypt($user->email, $encryptionKey);
        }
        if ($user->first_name) {
            $user->first_name = encrypt($user->first_name, $encryptionKey);
        }
        if ($user->last_name) {
            $user->last_name = encrypt($user->last_name, $encryptionKey);
        }
        if ($user->phone_number) {
            $user->phone_number = encrypt($user->phone_number, $encryptionKey);
        }

        return $user;
    }

    private function decryptUser(?\stdClass $result, string $encryptionKey): ?User
    {
        if (!$result) {
            return null;
        }

        $user = User::build($result);
        $user->username = decrypt($user->username, $encryptionKey);
        $user->email = decrypt($user->email, $encryptionKey);
        $user->first_name = decrypt($user->first_name, $encryptionKey);
        $user->last_name = decrypt($user->last_name, $encryptionKey);
        $user->phone_number = decrypt($user->phone_number, $encryptionKey);
        return $user;
    }
}
