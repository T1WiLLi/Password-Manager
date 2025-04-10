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
        $passwords = new PasswordBroker()->findByUserId($userID, EncryptionService::getUserKeyFromSession());
        return $passwords;
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

        if (new PasswordBroker()->existsByServiceAndUsername($form->getValue("service_name"), $form->getValue("username"))) {
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
}
