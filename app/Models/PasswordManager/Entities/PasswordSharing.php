<?php

namespace App\Models\Entities;

class PasswordSharing
{
    public $id;
    public $password_id;
    public $owner_id;
    public $shared_with_id;
    public $created_at;
    public $updated_at;
    public $status;
}
