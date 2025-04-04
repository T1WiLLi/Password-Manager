<?php

namespace Models\PasswordManager\Entities;

use Models\Core\Entity;

class EmailVerification extends Entity
{
    public int $id;
    public int $user_id;
    public string $token;
    public string $created_at;
    public string $expires_at;
}
