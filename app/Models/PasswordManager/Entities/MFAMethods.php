<?php

namespace Models\PasswordManager\Entities;

use Models\Core\Entity;

class MFAMethods extends Entity
{
    public int $id;
    public int $user_id;
    public string $method_type;
    public bool $is_enabled;
    public ?string $secret_data = null;
    public ?string $last_verification = null;
    public string $created_at;
    public string $updated_at;
}
