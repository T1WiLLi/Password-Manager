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

    public function findByAuthentification(string $email, string $password): ?User
    {
        $emailHash = Cryptography::hash($email, "sha256");
        $result = $this->selectSingle("SELECT * FROM {$this->table} WHERE email_hash = ?", [$emailHash]);
        if (!$result) {
            return null;
        }
        if (!Cryptography::verifyHashedPassword($password, $result->password)) {
            return null;
        }
        $hashedPassword = $result->password;
        $salt = $result->salt;
        return $this->decryptUser($result, $hashedPassword, $salt);
    }

    public function insert(User $user): int
    {
        $salt = generateSalt();
        $user->salt = $salt;
        $user->password = Cryptography::hashPassword($user->password);
        $encryptedUser = $this->encryptUser($user, $user->password, $user->salt);
        return $this->save($encryptedUser);
    }

    public function update(User $user): int
    {
        $encryptedUser = $this->encryptUser($user, $user->password, $user->salt);
        return $this->save($encryptedUser);
    }

    private function encryptUser(User $user, string $hashedPassword, string $salt): User
    {
        if ($user->username) {
            $user->username = encrypt($user->username, $hashedPassword, $salt);
        }
        if ($user->email) {
            $user->email = encrypt($user->email, $hashedPassword, $salt);
        }
        if ($user->first_name) {
            $user->first_name = encrypt($user->first_name, $hashedPassword, $salt);
        }
        if ($user->last_name) {
            $user->last_name = encrypt($user->last_name, $hashedPassword, $salt);
        }
        if ($user->phone_number) {
            $user->phone_number = encrypt($user->phone_number, $hashedPassword, $salt);
        }

        return $user;
    }

    private function decryptUser(?\stdClass $result, string $hashedPassword, string $salt): ?User
    {
        if (!$result) {
            return null;
        }

        $user = User::build($result);
        $user->username = decrypt($user->username, $hashedPassword, $salt);
        $user->email = decrypt($user->email, $hashedPassword, $salt);
        $user->first_name = decrypt($user->first_name, $hashedPassword, $salt);
        $user->last_name = decrypt($user->last_name, $hashedPassword, $salt);
        $user->phone_number = decrypt($user->phone_number, $hashedPassword, $salt);
        return $user;
    }
}
