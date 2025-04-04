<?php

namespace Models\PasswordManager\Services;

use Models\PasswordManager\Brokers\UserBroker;
use Models\PasswordManager\Entities\User;

class UserService
{
    public function getUser(int $userId): User
    {
        return User::build(new UserBroker()->findById($userId));
    }
}
