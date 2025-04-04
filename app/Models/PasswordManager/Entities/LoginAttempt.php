<?php

namespace Models\PasswordManager\Entities;

use Models\Core\Entity;

class LoginAttempt extends Entity
{
    public int $id;
    public int $user_id;
    public string $ip_address;
    public string $user_agent;
    public string $login_time;
    public string $status;
    public ?string $location = null;
}
