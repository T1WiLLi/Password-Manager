<?php

namespace App\Models\Entities;

use Models\Core\Entity;

class Password extends Entity
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
