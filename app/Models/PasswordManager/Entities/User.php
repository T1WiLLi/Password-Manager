<?php

namespace Models\PasswordManager\Entities;

use Models\Core\Entity;

class User extends Entity
{
    public int $id;
    public string $username;
    public string $email;
    public string $email_hash;
    public string $password;
    public string $salt;
    public string $public_key;
    public bool $is_verified;
    public string $created_at;
    public string $updated_at;
    public ?string $first_name = null;
    public ?string $last_name = null;
    public ?string $phone_number = null;
    public ?string $profile_image = null;
    public int $mfa_config; // Bitwise mask (0=none, 1=email, 2=sms, 4=authenticator)
    public ?string $mfa_grace_period_until = null;
}
