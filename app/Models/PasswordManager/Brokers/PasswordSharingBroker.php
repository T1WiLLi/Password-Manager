<?php

namespace Models\PasswordManager\Brokers;

use Models\PasswordManager\Entities\PasswordSharing;
use stdClass;

class PasswordSharingBroker extends Broker
{
    public function __construct()
    {
        parent::__construct("password_sharing");
    }

    public function sharePassword(int $passwordID, int $ownerID, int $sharedUserID, string $encryptedData, string $status = "pending"): int
    {
        $sql = "INSERT INTO {$this->table} (password_id, owner_id, shared_with_id, encrypted_data, status, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, NOW(), NOW()) RETURNING id";
        $result = $this->query($sql, [$passwordID, $ownerID, $sharedUserID, $encryptedData, $status]);
        return isset($result->id) ? (int) $result->id : 0;
    }

    public function findByPasswordID(int $passwordID): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE password_id = ? ORDER BY created_at DESC";
        $result = $this->select($sql, [$passwordID]);
        return $result ? PasswordSharing::buildArray($result) : [];
    }

    public function findByPasswordIdAndSharedUserId(int $passwordID, int $sharedUserID): ?stdClass
    {
        $sql = "SELECT * FROM {$this->table} WHERE password_id = ? AND shared_with_id = ?";
        $result = $this->selectSingle($sql, [$passwordID, $sharedUserID]);
        return $result;
    }

    public function findByOwnerID(int $ownerID, ?string $status = null): array
    {
        return $this->findByColumn('owner_id', $ownerID, $status);
    }

    public function findBySharedUserID(int $sharedUserID, ?string $status = null): array
    {
        return $this->findByColumn('shared_with_id', $sharedUserID, $status);
    }

    public function deleteByPasswordId(int $passwordId): bool
    {
        $sql = "DELETE FROM password_sharing WHERE password_id = ?";
        $this->query($sql, [$passwordId]);
        return $this->getLastAffectedCount() > 0;
    }

    private function findByColumn(string $column, int $value, ?string $status = null): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$column} = ?";
        $params = [$value];
        if ($status) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }
        $result = $this->select($sql, $params);
        return $result ? PasswordSharing::buildArray($result) : [];
    }
}
