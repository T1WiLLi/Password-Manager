<?php

use Zephyrus\Security\Cryptography;

/**
 * Add global project functions here ...
 */

function encrypt(string $data, string $hashedPassword, string $salt): string
{
    $key = deriveEncryptionKey($hashedPassword, $salt);
    return Cryptography::encrypt($data, $key);
}

function decrypt(string $data, string $hashedPassword, string $salt): string
{
    $key = deriveEncryptionKey($hashedPassword, $salt);
    return Cryptography::decrypt($data, $key);
}

function deriveEncryptionKey(string $password, string $salt, int $length = 64, int $iterations = 80000): string
{
    return Cryptography::deriveEncryptionKey($password, $salt, $length, $iterations);
}
