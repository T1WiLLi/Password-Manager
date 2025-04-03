<?php

namespace Models\PasswordManager\Services;

use Zephyrus\Core\Session;
use Zephyrus\Security\Cryptography;

class EncryptionService
{
    private const string CONTEXT_KEY = "user_context";
    private const string ALGORITHM_HASH_EMAIL = "sha256";

    public static function deriveEncryptionKey(string $password, string $encryptionSalt, int $length = 64, int $iterations = 80000): string
    {
        return Cryptography::deriveEncryptionKey($password, $encryptionSalt, $length, $iterations);
    }

    public static function encrypt(string $data, string $encryptionKey): string
    {
        return Cryptography::encrypt($data, $encryptionKey);
    }

    public static function decrypt(string $data, string $encryptionKey): string
    {
        return Cryptography::decrypt($data, $encryptionKey);
    }

    public static function storeUserKeyInSession(int $userID, string $userKey): void
    {
        $context = json_encode(["user_id" => $userID, "user_key" => $userKey]);
        Session::set(self::CONTEXT_KEY, Cryptography::encrypt($context));
    }

    public static function getUserKeyFromSession(): ?string
    {
        $context = Session::get(self::CONTEXT_KEY);
        if ($context === null) {
            return null;
        }
        return Cryptography::decrypt(json_decode($context))["user_key"] ?? null;
    }

    public static function getUserIDFromContext(): ?string
    {
        $context = Session::get(self::CONTEXT_KEY);
        if ($context === null) {
            return null;
        }
        return Cryptography::decrypt(json_decode($context))["user_id"] ?? null;
    }

    public static function hash256(string $data): string
    {
        return Cryptography::hash($data, self::ALGORITHM_HASH_EMAIL);
    }

    public static function generateSalt(int $length = 16): string
    {
        return Cryptography::randomBytes($length);
    }
}
