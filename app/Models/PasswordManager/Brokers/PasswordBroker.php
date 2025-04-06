<?php

namespace Models\PasswordManager\Brokers;

use Models\PasswordManager\Entities\Password;
use Models\PasswordManager\Services\EncryptionService;

class PasswordBroker extends Broker
{
    public function __construct()
    {
        parent::__construct("passwords");
    }

    public function existsByServiceAndUsername(string $serviceName, string $username): bool
    {
        $result = $this->selectSingle("SELECT * FROM {$this->table} WHERE service_name = ? AND username = ?", [$serviceName, $username]);
        return !$result ? false : true;
    }

    public function countByUserId(int $userID): int
    {
        $result = $this->selectSingle("SELECT COUNT(*) as count FROM {$this->table} WHERE user_id = ?", [$userID]);
        return (int)$result->count;
    }

    public function countDuplicatePasswords(int $userID, string $encryptionKey): int
    {
        $passwords = $this->findByUserId($userID, $encryptionKey);
        $uniquePasswords = [];
        $count = 0;
        foreach ($passwords as $password) {
            if (in_array($password->service_name, $uniquePasswords)) {
                $count++;
            } else {
                $uniquePasswords[] = $password->service_name;
            }
        }
        return $count;
    }

    public function findByIdDecrypt(int $id, string $encryptionKey): ?Password
    {
        return $this->decryptPassword($this->findById($id), $encryptionKey);
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
            $password->service_name = EncryptionService::encrypt($password->service_name, $encryptionKey);
        }
        if ($password->username) {
            $password->username = EncryptionService::encrypt($password->username, $encryptionKey);
        }
        if ($password->password) {
            $password->password = EncryptionService::encrypt($password->password, $encryptionKey);
        }
        if ($password->notes) {
            $password->notes = EncryptionService::encrypt($password->notes, $encryptionKey);
        }
        return $password;
    }

    private function decryptPassword(?\stdClass $result, string $encryptionKey): ?Password
    {
        if (!$result) {
            return null;
        }

        $password = Password::build($result);
        $password->service_name = EncryptionService::decrypt($password->service_name, $encryptionKey);
        $password->username = EncryptionService::decrypt($password->username, $encryptionKey);
        $password->password = EncryptionService::decrypt($password->password, $encryptionKey);
        $password->notes = EncryptionService::decrypt($password->notes, $encryptionKey);
        return $password;
    }
}
