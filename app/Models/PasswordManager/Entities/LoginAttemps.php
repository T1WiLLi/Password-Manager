<?php

namespace App\Models\Entities;

class LoginAttempts
{
    public $id;
    public $user_id;
    public $ip_address;
    public $user_agent;
    public $login_time;
    public $status;
    public $location;
}
