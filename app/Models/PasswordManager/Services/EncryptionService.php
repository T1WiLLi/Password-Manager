<?php

namespace Models\PasswordManager\Services;

use InvalidArgumentException;
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
        $decryptedContext = Cryptography::decrypt($context);
        $contextData = json_decode($decryptedContext, true);
        return $contextData["user_key"] ?? null;
    }

    public static function getUserIDFromSession(): ?string
    {
        $context = Session::get(self::CONTEXT_KEY);
        if ($context === null) {
            return null;
        }
        $decryptedContext = Cryptography::decrypt($context);
        $contextData = json_decode($decryptedContext, true);
        return $contextData["user_id"] ?? null;
    }

    public static function destroySession(): void
    {
        Session::destroy();
    }

    public static function hash256(string $data): string
    {
        return Cryptography::hash($data, self::ALGORITHM_HASH_EMAIL);
    }

    public static function generateSalt(int $length = 32): string
    {
        return Cryptography::randomHex($length);
    }

    public static function generateCompliantPassword(int $length = 12): string
    {
        if ($length < 8) {
            throw new InvalidArgumentException('Password length must be at least 8 characters.');
        }

        $upper = Cryptography::randomString(1, range('A', 'Z'));
        $lower = Cryptography::randomString(1, range('a', 'z'));
        $number = Cryptography::randomString(1, range('0', '9'));
        $special = Cryptography::randomString(1, '!@#$%^&*()-_=+[]{}|;:,.<>?');
        $remaining = Cryptography::randomString($length - 4);

        $password = str_shuffle($upper . $lower . $number . $special . $remaining);
        return $password;
    }
}
