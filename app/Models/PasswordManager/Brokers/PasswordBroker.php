<?php

namespace Models\PasswordManager\Brokers;

use App\Models\Entities\Password;

class PasswordBroker extends Broker
{
    public function __construct()
    {
        parent::__construct("passwords");
    }

    public function findByUserId(int $userID, string $encryptionKey): array
    {
        $results = $this->select("SELECT * FROM {$this->table} WHERE user_id = ?", [$userID]);
        $passwords = [];
        foreach ($results as $result) {
            $passwords[] = $this->decryptPassword($result, $encryptionKey);
        }
        return $passwords;
    }

    public function insert(Password $password, string $encryptionKey): int
    {
        $encryptedPassword = $this->encryptPassword($password, $encryptionKey);
        return $this->save($encryptedPassword);
    }

    public function update(Password $password, string $encryptionKey): int
    {
        $encryptedPassword = $this->encryptPassword($password, $encryptionKey);
        return $this->save($encryptedPassword);
    }

    private function encryptPassword(Password $password, string $encryptionKey): Password
    {
        if ($password->service_name) {
            $password->service_name = encrypt($password->service_name, $encryptionKey);
        }
        if ($password->username) {
            $password->username = encrypt($password->username, $encryptionKey);
        }
        if ($password->password) {
            $password->password = encrypt($password->password, $encryptionKey);
        }
        if ($password->notes) {
            $password->notes = encrypt($password->notes, $encryptionKey);
        }
        return $password;
    }

    private function decryptPassword(?\stdClass $result, string $encryptionKey): ?Password
    {
        if (!$result) {
            return null;
        }

        $password = Password::build($result);
        $password->service_name = decrypt($password->service_name, $encryptionKey);
        $password->username = decrypt($password->username, $encryptionKey);
        $password->password = decrypt($password->password, $encryptionKey);
        $password->notes = decrypt($password->notes, $encryptionKey);
        return $password;
    }
}
