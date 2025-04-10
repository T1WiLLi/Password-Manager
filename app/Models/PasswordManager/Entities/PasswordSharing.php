<?php

namespace Models\PasswordManager\Entities;

use Models\Core\Entity;
use Models\PasswordManager\Services\EncryptionService;

class PasswordSharing extends Entity
{
    public int $id;
    public int $password_id;
    public int $owner_id;
    public int $shared_with_id;
    public string $encrypted_data;
    public string $status;
    public string $created_at;
    public string $updated_at;

    public function getDecryptedDataAsArray(): ?array
    {
        try {
            $decryptedData = EncryptionService::decrypt($this->encrypted_data, EncryptionService::getUserKeyFromSession());
            return json_decode($decryptedData, true);
        } catch (\Exception $e) {
            error_log("Failed to decrypt data for sharing ID {$this->id}: " . $e->getMessage());
            return null;
        }
    }

    public function getDataAsArray(): ?array
    {
        try {
            $data = json_decode($this->encrypted_data, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return $data;
            }

            return $this->getDecryptedDataAsArray(EncryptionService::getUserKeyFromSession());
        } catch (\Exception $th) {
            error_log("Error in getDataAsArray for sharing ID {$this->id}: " . $th->getMessage());
            return null;
        }
    }
}
