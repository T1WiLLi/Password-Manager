<?php

use Zephyrus\Core\Session;
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

function generateSalt(int $length = 16): string
{
    return Cryptography::randomBytes($length);
}

function storeUserKeyInSession(int $userID, string $userKey): void
{
    $sessionKey = "user_{$userID}_encryption_key";
    Session::set($sessionKey, $userKey);
}

function getUserKeyFromSession(int $userID): ?string
{
    $sessionKey = "user_{$userID}_encryption_key";
    $encryptionKey = Session::get($sessionKey);
    if ($encryptionKey === null) {
        return null;
    }

    return $encryptionKey;
}
