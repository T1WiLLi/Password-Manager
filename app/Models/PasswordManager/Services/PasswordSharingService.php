<?php

namespace Models\PasswordManager\Services;

use Models\PasswordManager\Brokers\PasswordBroker;
use Models\PasswordManager\Brokers\PasswordSharingBroker;
use Models\PasswordManager\Entities\Password;
use Models\PasswordManager\Entities\PasswordSharing;
use Zephyrus\Security\Cryptography;

class PasswordSharingService
{

    public function sharePassword(int $passwordID, int $ownerID, int $sharedUserID): int
    {
        $password = new PasswordBroker()->findByIdDecrypt($passwordID, EncryptionService::getUserKeyFromSession());

        if (!$password || $password->user_id !== $ownerID) {
            throw new \Exception("Invalid password or ownership.");
        }

        $encryptedData = Cryptography::encrypt(
            json_encode([
                "service_name" => $password->service_name,
                "username" => $password->username,
                "password" => $password->password
            ])
        );

        return new PasswordSharingBroker()->sharePassword(
            $passwordID,
            $ownerID,
            $sharedUserID,
            $encryptedData
        );
    }

    public function getSharedPasswords(): ?array
    {
        $sharings = new PasswordSharingBroker()->findBySharedUserID(EncryptionService::getUserIDFromSession());

        if (empty($sharings)) {
            return [];
        }

        return $sharings;
    }

    public function getPasswordShared(): ?array
    {
        $sharedRecords = new PasswordSharingBroker()->findByOwnerID(EncryptionService::getUserIDFromSession());

        if (empty($sharedRecords)) {
            return [];
        }

        $sharedPasswords = [];
        foreach ($sharedRecords as $sharing) {
            try {
                $password = (new PasswordBroker())->findByIdDecrypt($sharing->password_id, EncryptionService::getUserKeyFromSession());

                if ($password && (int)$password->user_id === (int)EncryptionService::getUserIDFromSession()) {
                    $tempSharing = new PasswordSharing();
                    $tempSharing->id = $sharing->id;
                    $tempSharing->password_id = $sharing->password_id;
                    $tempSharing->owner_id = $sharing->owner_id;
                    $tempSharing->shared_with_id = $sharing->shared_with_id;
                    $tempSharing->status = $sharing->status;
                    $tempSharing->created_at = $sharing->created_at;
                    $tempSharing->updated_at = $sharing->updated_at;

                    $jsonData = json_encode([
                        "service_name" => $password->service_name,
                        "username" => $password->username,
                        "password" => $password->password
                    ]);
                    $tempSharing->encrypted_data = $jsonData;
                    $sharedPasswords[] = $tempSharing;
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        return $sharedPasswords;
    }

    public function activateSharing(): bool
    {
        $success = true;
        $sharings = new PasswordSharingBroker()->findBySharedUserID(EncryptionService::getUserIDFromSession());

        if (empty($sharings)) {
            return true;
        }

        foreach ($sharings as $sharing) {
            if ($sharing->status === "pending") {
                try {
                    $decryptedData = Cryptography::decrypt($sharing->encrypted_data);
                    $userEncryptedData = EncryptionService::encrypt($decryptedData, EncryptionService::getUserKeyFromSession());

                    $sharing->encrypted_data = $userEncryptedData;
                    $sharing->status = "active";

                    if (!new PasswordSharingBroker()->save($sharing)) {
                        $success = false;
                    }
                } catch (\Exception $e) {
                    $success = false;
                    continue;
                }
            }
        }
        return $success;
    }

    public function deleteSharing(int $sharingID): bool
    {
        return new PasswordSharingBroker()->delete($sharingID);
    }

    public function revokeSharing(int $sharingID): bool
    {
        $sharing = PasswordSharing::build(new PasswordSharingBroker()->findById($sharingID));

        if (!$sharing || $sharing->owner_id !== EncryptionService::getUserIDFromSession()) {
            return false;
        }

        return new PasswordSharingBroker()->delete($sharing->id);
    }

    public function revokeAllSharings(): bool
    {
        $success = true;
        $sharings = new PasswordSharingBroker()->findByOwnerID(EncryptionService::getUserIDFromSession());
        foreach ($sharings as $sharing) {
            $success = $success && $this->revokeSharing($sharing->id, EncryptionService::getUserIDFromSession());
            if (!$success) {
                break;
            }
        }
        return $success;
    }

    public function updateSharing(PasswordSharing $sharing, Password $newPassword)
    {
        $encryptedData = Cryptography::encrypt(
            json_encode([
                "service_name" => $newPassword->service_name,
                "username" => $newPassword->username,
                "password" => $newPassword->password,
            ])
        );

        $sharing->encrypted_data = $encryptedData;
        $sharing->status = "pending";
        $sharing->updated_at = date('Y-m-d H:i:s');

        if (!new PasswordSharingBroker()->save($sharing)) {
            throw new \Exception("Failed to update sharing with ID: $sharing->id");
        }
    }
}
