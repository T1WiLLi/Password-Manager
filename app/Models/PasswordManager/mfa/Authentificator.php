<?php

namespace Models\PasswordManager\mfa;

use RobThree\Auth\TwoFactorAuth;

class Authentificator
{
    private TwoFactorAuth $tfa;

    public function __construct()
    {
        $this->tfa = new TwoFactorAuth('PasswordManager');
    }

    public function generateSecret(): string
    {
        return $this->tfa->createSecret(160); // 160‑bit secret
    }

    public function getQRCodeInline(string $username, string $secret): string
    {
        return $this->tfa->getQRCodeImageAsDataUri($username, $secret);
    }

    public function verifyCode(string $secret, string $code): bool
    {
        return $this->tfa->verifyCode($secret, $code);
    }
}
