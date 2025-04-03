<?php

namespace Models\PasswordManager\Brokers;

use App\Models\Entities\EmailVerification;

class EmailVerificationBroker extends Broker
{
    public function __construct()
    {
        parent::__construct("email_verification");
    }

    public function findByToken(string $token): ?EmailVerification
    {
        $sql = "SELECT * FROM {$this->table} WHERE token = ?";
        $result = $this->selectSingle($sql, [$token]);
        return EmailVerification::build($result) ?? null;
    }

    public function findByUserID(int $userID): ?EmailVerification
    {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
        $result = $this->selectSingle($sql, [$userID]);
        return EmailVerification::build($result) ?? null;
    }

    public function deleteByToken(string $token): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE token = ?";
        $this->query($sql, [$token]);
        return $this->getLastAffectedCount() > 0;
    }
}
