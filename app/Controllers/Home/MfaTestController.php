<?php

namespace Controllers\Home;

use Controllers\Controller;
use Models\PasswordManager\mfa\EmailMFA;
use Models\PasswordManager\mfa\SmsMFA;
use Zephyrus\Application\Mailer\Mailer;
use Zephyrus\Network\Response;
use Zephyrus\Network\Router\Get;
use Zephyrus\Network\Router\Post;

class MfaTestController extends Controller
{
    private EmailMFA $emailMfa;
    private SmsMFA $smsMfa;

    public function __construct()
    {
        $this->emailMfa = new EmailMFA();
        $this->smsMfa = new SmsMFA();

        // Ensure session is started
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    #[Get("/mfa-test")]
    public function index(): Response
    {
        // Get any messages from the session and clear them
        $successMessage = $_SESSION['mfa_success_message'] ?? '';
        $errorMessage = $_SESSION['mfa_error_message'] ?? '';

        // Clear session messages after reading them
        unset($_SESSION['mfa_success_message']);
        unset($_SESSION['mfa_error_message']);

        return $this->render('mfa_test', [
            'title' => 'Test MFA Services',
            'successMessage' => $successMessage,
            'errorMessage' => $errorMessage
        ]);
    }

    #[Post("/mfa-test")]
    public function send(): Response
    {
        $form = $this->buildForm();
        $form->verify('service', fn($value) => in_array($value, ['email', 'sms']), 'Invalid service selected');
        $form->verify('recipient', fn($value) => !empty($value), 'Recipient is required');

        if ($form->hasError()) {
            $_SESSION['mfa_error_message'] = implode('<br>', $form->getErrorMessages());
            return $this->redirect('/mfa-test');
        }

        $service = $form->getValue('service');
        $recipient = $form->getValue('recipient');
        $useGeneratedCode = $form->getValue('use_generated_code') === 'on';
        $customBody = $form->getValue('custom_body');

        try {
            if ($useGeneratedCode) {
                $code = $service === 'email' ? $this->emailMfa->generateCode() : $this->smsMfa->generateCode();
                $success = $this->sendMfaCode($service, $recipient, $code);
                if ($success) {
                    $_SESSION['mfa_success_message'] = "MFA code ($code) sent successfully to $recipient via $service.";
                } else {
                    $_SESSION['mfa_error_message'] = "Failed to send MFA code to $recipient via $service.";
                }
            } else {
                if (empty($customBody)) {
                    $_SESSION['mfa_error_message'] = "Custom body is required if not using a generated code.";
                    return $this->redirect('/mfa-test');
                }
                $success = $this->sendCustomMessage($service, $recipient, $customBody);
                if ($success) {
                    $_SESSION['mfa_success_message'] = "Custom message sent successfully to $recipient via $service.";
                } else {
                    $_SESSION['mfa_error_message'] = "Failed to send custom message to $recipient via $service.";
                }
            }
        } catch (\Exception $e) {
            $_SESSION['mfa_error_message'] = "Error: " . $e->getMessage();
        }

        return $this->redirect('/mfa-test');
    }

    /**
     * Sends an MFA code using the specified service.
     *
     * @param string $service The service to use ('email' or 'sms').
     * @param string $recipient The recipient (email address or phone number).
     * @param string $code The MFA code to send.
     * @return bool True if sent successfully, false otherwise.
     */
    private function sendMfaCode(string $service, string $recipient, string $code): bool
    {
        if ($service === 'email') {
            return $this->emailMfa->sendCode($recipient, $code);
        } elseif ($service === 'sms') {
            $sentCode = $this->smsMfa->sendCode($recipient);
            return $sentCode === $code;
        }
        return false;
    }

    private function sendCustomMessage(string $service, string $recipient, string $customBody): bool
    {
        if ($service === 'email') {
            try {
                $mailer = new Mailer(null);
                $mailer->setFrom(config('mailer', "from_address"), config('mailer', "from_name"));
                $mailer->setSubject('Custom MFA Message');
                $mailer->setBody($customBody, strip_tags($customBody));
                $mailer->addRecipient($recipient);
                $mailer->send();
                return true;
            } catch (\Exception $e) {
                error_log("Failed to send custom email: " . $e->getMessage());
                return false;
            } finally {
                $mailer->clearRecipients();
            }
        } elseif ($service === 'sms') {
            try {
                $twilio = new \Twilio\Rest\Client(
                    config('twilio', "account_sid"),
                    config('twilio', "auth_token")
                );
                $twilio->messages->create(
                    $recipient,
                    [
                        "from" => config('twilio', "from_number"),
                        "body" => $customBody
                    ]
                );
                return true;
            } catch (\Twilio\Exceptions\TwilioException $e) {
                error_log("Failed to send custom SMS: " . $e->getMessage());
                return false;
            }
        }
        return false;
    }
}
