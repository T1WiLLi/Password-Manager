<?php

namespace Models\PasswordManager\DTOs;

use Models\PasswordManager\Utils\BootstrapIcon;

class RecentActivity
{
    public function __construct(
        public BootstrapIcon $icon,
        public string $title,
        public string $description,
        public string $date,
        public int $timestamp
    ) {}
}
