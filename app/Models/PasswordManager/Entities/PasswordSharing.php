<?php

namespace Models\PasswordManager\Entities;

use Models\Core\Entity;

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
}
