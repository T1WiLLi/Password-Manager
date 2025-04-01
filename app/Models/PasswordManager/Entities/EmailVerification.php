<?php

namespace App\Models\Entities;

use Models\Core\Entity;

class EmailVerification extends Entity
{
    public $id;
    public $user_id;
    public $token;
    public $created_at;
    public $expires_at;
}
