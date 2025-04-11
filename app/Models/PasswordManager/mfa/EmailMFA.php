<?php

namespace Models\PasswordManager\mfa;

use Zephyrus\Application\Configuration;
use Zephyrus\Application\Mailer\Mailer;
use Zephyrus\Application\Mailer\MailerSmtpConfiguration;
use Zephyrus\Security\Cryptography;

class EmailMFA
{
    private Mailer $mailer;

    public function __construct()
    {
        $config = new MailerSmtpConfiguration(Configuration::getMailer('smtp'));
        $this->mailer = new Mailer($config);
        $this->mailer->setFrom(config('mailer', "from_address"), config('mailer', "from_name"));
    }

    public function generateCode(): string
    {
        $code = Cryptography::randomInt(0, 999999);
        return str_pad($code, 6, '0', STR_PAD_LEFT);
    }

    public function sendCode(string $email, string $code, ?callable $customBody = null): bool
    {
        try {
            $this->mailer->setSubject('Your Verification Email');

            if ($customBody === null) {
                $this->mailer->setBody(
                    "Your one-time MFA code is: <strong>$code</strong><br>This code will expire in 10 minutes.",
                    "Your one-time MFA code is: $code\nThis code will expire in 10 minutes."
                );
            } else {
                [$htmlBody, $textBody] = $customBody($code);
                $this->mailer->setBody($htmlBody, $textBody);
            }

            $this->mailer->addRecipient($email);
            $this->mailer->send();
            return true;
        } catch (\Exception $e) {
            error_log("Failed to send MFA email: " . $e->getMessage());
            return false;
        } finally {
            $this->mailer->clearRecipients();
        }
    }
}
