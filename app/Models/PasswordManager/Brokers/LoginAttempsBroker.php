<?php

namespace Models\PasswordManager\Brokers;

use App\Models\Entities\LoginAttempts;

class LoginAttempsBroker extends Broker
{
    public function __construct()
    {
        parent::__construct("login_attemps");
    }

    public function findByUserID(int $userID, int $limit = 10): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY login_time DESC LIMIT ?";
        $results = $this->select($sql, [$userID, $limit]);
        return !$results ? LoginAttempts::buildArray($results) : [];
    }

    public function deleteByUserID(int $userID): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE user_id = ?";
        $this->query($sql, [$userID]);
        return $this->getLastAffectedCount() > 0;
    }

    public function deleteByUserIDAndLoginID(int $userID, int $loginID): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE user_id = ? AND id = ?";
        $this->query($sql, [$userID, $loginID]);
        return $this->getLastAffectedCount() > 0;
    }
}
