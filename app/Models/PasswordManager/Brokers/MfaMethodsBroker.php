<?php

namespace Models\PasswordManager\Brokers;

use Models\PasswordManager\Brokers\Broker;
use Models\PasswordManager\Entities\MFAMethods;

class MfaMethodsBroker extends Broker
{
    public function __construct()
    {
        parent::__construct("mfa_methods"); // Table name
    }

    public function findAllByUser(int $userID): array
    {
        $results = $this->select("SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY method_type", [$userID]);
        return MFAMethods::buildArray($results);
    }

    public function findEnabledByUser(int $userID): array
    {
        $results = $this->select("SELECT * FROM {$this->table} WHERE user_id = ? AND is_enabled = TRUE ORDER BY method_type", [$userID]);
        return MFAMethods::buildArray($results);
    }

    public function findByUserAndType(int $userID, string $type): ?MFAMethods
    {
        $result = $this->selectSingle("SELECT * FROM {$this->table} WHERE user_id = ? AND method_type = ?", [$userID, $type]);
        return MFAMethods::build($result);
    }
}
