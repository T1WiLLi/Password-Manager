<?php

namespace Models\PasswordManager\Services;

use Models\PasswordManager\Brokers\PasswordBroker;
use Models\PasswordManager\Brokers\PasswordSharingBroker;
use Models\PasswordManager\Entities\Password;
use Models\PasswordManager\Entities\PasswordSharing;
use Models\PasswordManager\Validators\PasswordValidator;
use Zephyrus\Application\Form;

class PasswordService
{
    public function getAllPasswords($userID): array
    {
        $passwords = (new PasswordBroker())->findByUserId($userID, EncryptionService::getUserKeyFromSession());

        $passwordValues = [];
        $duplicates = [];

        foreach ($passwords as $password) {
            $passValue = $password->password;
            if (!isset($passwordValues[$passValue])) {
                $passwordValues[$passValue] = [];
            }
            $passwordValues[$passValue][] = $password->id;
        }

        foreach ($passwordValues as $passValue => $ids) {
            if (count($ids) > 1) {
                foreach ($ids as $id) {
                    $duplicates[$id] = true;
                }
            }
        }

        foreach ($passwords as $password) {
            $password->isDuplicate = isset($duplicates[$password->id]);
        }

        return $passwords;
    }

    public function getAllPasswordsWithStrength($userID): array
    {
        $passwords = $this->getAllPasswords($userID);
        foreach ($passwords as $password) {
            $strengthInfo = $this->calculatePasswordStrength($password->password);
            $password->strength = $strengthInfo['strength'];
            $password->entropy = $strengthInfo['entropy'];
        }
        return array_map(fn($p) => [
            'service_name' => $p->service_name,
            'username' => $p->username,
            'strength' => $p->strength,
            'entropy' => $p->entropy
        ], $passwords);
    }

    public function getPasswordCountByUserId(int $userID): int
    {
        return new PasswordBroker()->countByUserId($userID);
    }

    public function getDuplicatePasswordCount(int $userID): int
    {
        return new PasswordBroker()->countDuplicatePasswords($userID, EncryptionService::getUserKeyFromSession());
    }

    public function createPassword(Form $form): Password
    {
        PasswordValidator::validatePassword($form);

        if (new PasswordBroker()->existsByServiceAndUsername(EncryptionService::encrypt($form->getValue("service_name"), EncryptionService::getUserKeyFromSession()), EncryptionService::encrypt($form->getValue("username"), EncryptionService::getUserKeyFromSession()))) {
            throw new \Exception("Password already exists for this service and username.");
        }

        $password = Password::build($form->buildObject());
        $password->user_id = EncryptionService::getUserIDFromSession();
        $password->id = new PasswordBroker()->insert($password, EncryptionService::getUserKeyFromSession());
        return $password;
    }

    public function updatePassword(Form $form, int $id): Password
    {
        $password = updateEntity(Password::build(new PasswordBroker()->findById($id)), $form, ["id"]);
        $this->updateSharingOnPasswordUpdate($id, $password);
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

    public function deletePassword(int $id): bool
    {
        new PasswordSharingBroker()->deleteByPasswordId($id);
        return new PasswordBroker()->delete($id);
    }

    private function updateSharingOnPasswordUpdate(int $passwordID, Password $newPassword)
    {
        $existingSharings = PasswordSharing::buildArray(new PasswordSharingBroker()->findByPasswordID($passwordID));

        if (!empty($existingSharings)) {
            foreach ($existingSharings as $sharing) {
                try {
                    new PasswordSharingService()->updateSharing($sharing, $newPassword);
                } catch (\Exception $e) {
                    error_log("Failed to update sharing ID: {$sharing->id} for password ID: $passwordID: " . $e->getMessage());
                }
            }
        }
    }

    private function calculatePasswordStrength(string $password): array
    {
        $length = strlen($password);
        $charset = 0;

        if (preg_match('/[a-z]/', $password)) $charset += 26;
        if (preg_match('/[A-Z]/', $password)) $charset += 26;
        if (preg_match('/[0-9]/', $password)) $charset += 10;
        if (preg_match('/[^a-zA-Z0-9]/', $password)) $charset += 32;

        $entropy = $length * ($charset > 0 ? log($charset, 2) : 0);

        if ($entropy >= 85) {
            $strength = 'Strong';
        } elseif ($entropy >= 75) {
            $strength = 'Medium';
        } else {
            $strength = 'Weak';
        }

        return [
            'strength' => $strength,
            'entropy' => $entropy
        ];
    }
}
