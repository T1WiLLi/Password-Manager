<?php

namespace App\Models\Entities;

use Models\Core\Entity;

class PasswordSharing extends Entity
{
    public $id;
    public $password_id;
    public $owner_id;
    public $shared_with_id;
    public $created_at;
    public $updated_at;
    public $status;
}
