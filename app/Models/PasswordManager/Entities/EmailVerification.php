<?php

namespace App\Models\Entities;

class EmailVerification
{
    public $id;
    public $user_id;
    public $token;
    public $created_at;
    public $expires_at;
}
