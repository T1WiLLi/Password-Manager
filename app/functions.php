<?php

use Zephyrus\Security\Cryptography;

/**
 * Add global project functions here ...
 */

function encrypt(string $data, string $hashedPassword, string $encryptionSalt): string
{
    $key = deriveEncryptionKey($hashedPassword, $encryptionSalt);
    return Cryptography::encrypt($data, $key);
}

function decrypt(string $data, string $hashedPassword, string $encryptionSalt): string
{
    $key = deriveEncryptionKey($hashedPassword, $encryptionSalt);
    return Cryptography::decrypt($data, $key);
}

function deriveEncryptionKey(string $password, string $encryptionSalt, int $length = 64, int $iterations = 80000): string
{
    return Cryptography::deriveEncryptionKey($password, $encryptionSalt, $length, $iterations);
}

function generateSalt(int $length = 16): string
{
    return Cryptography::randomBytes($length);
}
