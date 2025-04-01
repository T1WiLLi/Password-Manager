<?php

namespace App\Models\Entities;

class Password
{
    public $id;
    public $user_id;
    public $service_name;
    public $username;
    public $password;
    public $created_at;
    public $last_used;
    public $updated_at;
    public $notes;
}
