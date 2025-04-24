<?php

namespace Models\PasswordManager\mfa;

use Tracy\Debugger;
use Twilio\Rest\Client as TwilioClient;
use Zephyrus\Security\Cryptography;

class SmsMFA
{
    private TwilioClient $twilio;
    private string $fromNumber;

    public function __construct()
    {
        $sid = config('twilio', "account_sid");
        $token = config('twilio', "auth_token");
        $this->fromNumber = config('twilio', "from_number");

        Debugger::barDump($sid);
        Debugger::barDump($token);

        if (!$sid || !$token || !$this->fromNumber) {
            throw new \RuntimeException("Twilio configuration is missing: account_sid=$sid, auth_token=$token, from_number=$this->fromNumber");
        }

        $this->twilio = new TwilioClient($sid, $token);
    }

    public function generateCode(): string
    {
        $code = Cryptography::randomInt(0, 999999);
        return str_pad($code, 6, '0', STR_PAD_LEFT);
    }

    public function sendCode(string $phoneNumber, string $code): string
    {
        try {
            $message = $this->twilio->messages->create(
                (str_starts_with($phoneNumber, '+1') ? $phoneNumber : '+1' . $phoneNumber), // to
                [
                    "from" => $this->fromNumber,
                    "body" => "Your one-time MFA code is: $code\nThis code will expire in 10 minutes."
                ]
            );
            return $code;
        } catch (\Twilio\Exceptions\TwilioException $e) {
            error_log("Failed to send SMS MFA code: " . $e->getMessage());
            throw $e;
        }
    }
}
