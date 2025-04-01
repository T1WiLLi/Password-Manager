<?php

namespace App\Models\Entities;

class MFAMethods
{
    public $id;
    public $user_id;
    public $method_type;
    public $is_enabled;
    public $secret_data;
    public $last_verification;
    public $created_at;
    public $updated_at;
}
