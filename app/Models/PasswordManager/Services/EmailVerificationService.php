<?php

namespace Models\PasswordManager\Services;

use Models\PasswordManager\Brokers\EmailVerificationBroker;
use Models\PasswordManager\Brokers\UserBroker;
use Models\PasswordManager\Entities\EmailVerification;
use Models\PasswordManager\mfa\EmailMFA;
use Zephyrus\Security\Cryptography;

class EmailVerificationService
{
    private EmailMFA $emailMFA;

    public function __construct()
    {
        $this->emailMFA = new EmailMFA();
    }

    public function createVerification(int $userID, string $email): bool
    {
        $token = Cryptography::randomHex(32);
        $verification = new EmailVerification();
        $verification->user_id = $userID;
        $verification->token = $token;
        $verification->created_at = date("Y-m-d H:i:s");
        $verification->expires_at = date("Y-m-d H:i:s", strtotime("+24 hours"));

        new EmailVerificationBroker()->save($verification);

        $customBody = function (string $token) {
            $link = "https://localhost/verify-email/{$token}";
            return [
                "Please verify your email by clicking this link: <a href=\"{$link}\">Verify Email</a><br>This link will expire in 24 hours.",
                "Please verify your email by visiting this link: {$link}\nThis link will expire in 24 hours."
            ];
        };

        return $this->emailMFA->sendCode($email, $token, $customBody);
    }

    public function verifiyEmail(string $token): void
    {
        $verification = new EmailVerificationBroker()->findByToken($token);

        if (!$verification) {
            throw new \Exception("Invalid token or already verified.");
        }

        if (strtotime($verification->expires_at) < time()) {
            throw new \LogicException("Token expired.{$verification->user_id}"); // Ask the user to re-enter their email so that we can send a new token and we can parse the user ID from the exception message.
        }

        $this->markAsVerified($verification->user_id);
        new EmailVerificationBroker()->deleteByToken($token);
    }

    private function markAsVerified(int $userID): void
    {
        new UserBroker()->markAsVerified($userID);
    }
}
