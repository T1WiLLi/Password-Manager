<?php

namespace Models\PasswordManager\Entities;

use Models\Core\Entity;

class Password extends Entity
{
    public int $id;
    public int $user_id;
    public string $service_name;
    public string $username;
    public string $password;
    public string $created_at;
    public ?string $last_used = null;
    public string $updated_at;
}
