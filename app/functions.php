<?php

use Zephyrus\Security\Cryptography;

/**
 * Add global project functions here ...
 */

function encrypt(string $data, string $encryptionKey): string
{
    return Cryptography::encrypt($data, $encryptionKey);
}

function decrypt(string $data, string $encryptionKey): string
{
    return Cryptography::decrypt($data, $encryptionKey);
}

function deriveEncryptionKey(string $password, string $encryptionSalt, int $length = 64, int $iterations = 80000): string
{
    return Cryptography::deriveEncryptionKey($password, $encryptionSalt, $length, $iterations);
}

function encryptSessionData(string $data, int $userID): void {}

function decryptSessionData(string $data, int $userID): void {}

function generateSalt(int $length = 16): string
{
    return Cryptography::randomBytes($length);
}
