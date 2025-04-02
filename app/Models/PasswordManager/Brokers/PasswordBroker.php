<?php

namespace Models\PasswordManager\Brokers;

use App\Models\Entities\Password;

class PasswordBroker extends Broker
{
    public function __construct()
    {
        parent::__construct("passwords");
    }

    public function findByUserId(int $userID, string $hashedPassword, string $salt): array
    {
        $results = $this->select("SELECT * FROM {$this->table} WHERE user_id = ?", [$userID]);
        $passwords = [];
        foreach ($results as $result) {
            $passwords[] = $this->decryptPassword($result, $hashedPassword, $salt);
        }
        return $passwords;
    }

    public function insert(Password $password, string $hashedPassword, string $salt): int
    {
        $encryptedPassword = $this->encryptPassword($password, $hashedPassword, $salt);
        return $this->save($encryptedPassword);
    }

    public function update(Password $password, string $hashedPassword, string $salt): int
    {
        $encryptedPassword = $this->encryptPassword($password, $hashedPassword, $salt);
        return $this->save($encryptedPassword);
    }

    private function encryptPassword(Password $password, string $hashedPassword, string $salt): Password
    {
        if ($password->service_name) {
            $password->service_name = encrypt($password->service_name, $hashedPassword, $salt);
        }
        if ($password->username) {
            $password->username = encrypt($password->username, $hashedPassword, $salt);
        }
        if ($password->password) {
            $password->password = encrypt($password->password, $hashedPassword, $salt);
        }
        if ($password->notes) {
            $password->notes = encrypt($password->notes, $hashedPassword, $salt);
        }
        return $password;
    }

    private function decryptPassword(?\stdClass $result, string $hashedPassword, string $salt): ?Password
    {
        if (!$result) {
            return null;
        }

        $password = Password::build($result);
        $password->service_name = decrypt($password->service_name, $hashedPassword, $salt);
        $password->username = decrypt($password->username, $hashedPassword, $salt);
        $password->password = decrypt($password->password, $hashedPassword, $salt);
        $password->notes = decrypt($password->notes, $hashedPassword, $salt);
        return $password;
    }
}
