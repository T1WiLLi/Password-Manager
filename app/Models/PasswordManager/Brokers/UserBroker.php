<?php

namespace Models\PasswordManager\Brokers;

use App\Models\Entities\User;
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
        $emailHash = EncryptionService::hash256($email);
        $result = $this->selectSingle("SELECT * FROM {$this->table} WHERE email_hash = ?", [$emailHash]);
        if (!$result) {
            return null;
        }
        if (!Cryptography::verifyHashedPassword($clearPassword, $result->password)) {
            return null;
        }
        return $this->decryptUser($result, $encryptionKey);
    }

    public function insert(User $user, string $encryptionKey): int
    {
        $user->password = Cryptography::hashPassword($user->password);
        $user->emailHash = EncryptionService::hash256($user->email);
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
