<?php

namespace App\Models\Entities;

class User
{
    public $id;
    public $username;
    public $email;
    public $password;
    public $salt;
    public $is_verified;
    public $created_at;
    public $updated_at;
    public $first_name;
    public $last_name;
    public $phone_number;
    public $profile_image;
    public $mfa_config; // Bitwise mask (0=none, 1=email, 2=sms, 4=authenticator)
    public $mfa_grace_period_until;
}
