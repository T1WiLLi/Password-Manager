<?php

namespace Controllers\Home;

use Controllers\Controller;
use Models\PasswordManager\mfa\EmailMFA;
use Models\PasswordManager\mfa\SmsMFA;
use Models\PasswordManager\mfa\Authentificator;
use Zephyrus\Application\Mailer\Mailer;
use Zephyrus\Network\Response;
use Zephyrus\Network\Router\Get;
use Zephyrus\Network\Router\Post;

class MfaTestController extends Controller
{
    private EmailMFA $emailMfa;
    private SmsMFA $smsMfa;
    private Authentificator $authenticator;

    public function __construct()
    {
        $this->emailMfa      = new EmailMFA();
        $this->smsMfa        = new SmsMFA();
        $this->authenticator = new Authentificator();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Ensure we have a per‑session TOTP secret
        if (empty($_SESSION['mfa_auth_secret'])) {
            $_SESSION['mfa_auth_secret'] = $this->authenticator->generateSecret();
        }
    }

    #[Get("/mfa-test")]
    public function index(): Response
    {
        // flash messages
        $success = $_SESSION['mfa_success_message'] ?? '';
        $error   = $_SESSION['mfa_error_message']   ?? '';
        unset($_SESSION['mfa_success_message'], $_SESSION['mfa_error_message']);

        return $this->render('mfa_test', [
            'title'          => 'Test MFA Services',
            'successMessage' => $success,
            'errorMessage'   => $error
        ]);
    }

    #[Post("/mfa-test")]
    public function send(): Response
    {
        $form = $this->buildForm();
        $form->verify('service', fn($v) => in_array($v, ['email', 'sms', 'authenticator']), 'Invalid service');
        if (in_array($form->getValue('service'), ['email', 'sms'])) {
            $form->verify('recipient', fn($v) => !empty($v), 'Recipient is required');
        }
        if ($form->getValue('service') === 'authenticator') {
            $form->verify('code', fn($v) => preg_match('/^\d{6}$/', $v), 'Enter a 6‑digit code');
        }

        if ($form->hasError()) {
            $_SESSION['mfa_error_message'] = implode('<br>', $form->getErrorMessages());
            return $this->redirect('/mfa-test');
        }

        $svc = $form->getValue('service');
        $rcp = $form->getValue('recipient');

        try {
            if ($svc === 'authenticator') {
                // verify TOTP
                $ok = $this->authenticator->verifyCode(
                    $_SESSION['mfa_auth_secret'],
                    $form->getValue('code')
                );
                $_SESSION[$ok ? 'mfa_success_message' : 'mfa_error_message']
                    = $ok
                    ? '✔️ Authenticator code is valid!'
                    : '❌ Invalid authenticator code.';
            } elseif ($form->getValue('use_generated_code') === 'on') {
                // email or SMS with generated code
                $code = $svc === 'email'
                    ? $this->emailMfa->generateCode()
                    : $this->smsMfa->generateCode();
                $sent = $this->sendMfaCode($svc, $rcp, $code);
                $_SESSION[$sent ? 'mfa_success_message' : 'mfa_error_message']
                    = $sent
                    ? "MFA code ($code) sent to $rcp via $svc."
                    : "Failed to send MFA code to $rcp via $svc.";
            } else {
                // custom body
                $body = $form->getValue('custom_body');
                if (empty($body)) {
                    throw new \Exception("Custom body is required if not using generated code");
                }
                $ok = $this->sendCustomMessage($svc, $rcp, $body);
                $_SESSION[$ok ? 'mfa_success_message' : 'mfa_error_message']
                    = $ok
                    ? "Custom message sent to $rcp via $svc."
                    : "Failed to send custom message to $rcp via $svc.";
            }
        } catch (\Exception $e) {
            $_SESSION['mfa_error_message'] = "Error: " . $e->getMessage();
        }

        return $this->redirect('/mfa-test');
    }

    /**
     * Endpoint to return a tiny HTML snippet with the QR <img>.
     */
    #[Get("/verify/qrcode")]
    public function getQrCode(): Response
    {
        $secret = $_SESSION['mfa_auth_secret']
            ?? $_SESSION['mfa_auth_secret'] = $this->authenticator->generateSecret();

        $dataUri = $this->authenticator->getQRCodeInline(
            "TiWill",
            $secret
        );

        $html = '
        <div class="qr-code-image text-center">
            <img src="' . htmlspecialchars($dataUri, ENT_QUOTES) . '"
                 alt="QR Code To Scan"
                 class="img-fluid rounded shadow"
                 style="max-width:250px;">
        </div>';

        $resp = new Response();
        $resp->setContent($html);
        return $resp;
    }

    private function sendMfaCode(string $svc, string $to, string $code): bool
    {
        if ($svc === 'email') {
            return $this->emailMfa->sendCode($to, $code);
        }
        $sent = $this->smsMfa->sendCode($to);
        return $sent === $code;
    }

    private function sendCustomMessage(string $svc, string $to, string $body): bool
    {
        if ($svc === 'email') {
            try {
                $mailer = new Mailer(null);
                $mailer->setFrom(config('mailer', 'from_address'), config('mailer', 'from_name'));
                $mailer->setSubject('Custom MFA Message');
                $mailer->setBody($body, strip_tags($body));
                $mailer->addRecipient($to);
                $mailer->send();
                return true;
            } catch (\Exception $e) {
                error_log($e->getMessage());
                return false;
            } finally {
                $mailer->clearRecipients();
            }
        }

        try {
            $tw = new \Twilio\Rest\Client(
                config('twilio', 'account_sid'),
                config('twilio', 'auth_token')
            );
            $tw->messages->create($to, [
                'from' => config('twilio', 'from_number'),
                'body' => $body
            ]);
            return true;
        } catch (\Twilio\Exceptions\TwilioException $e) {
            error_log($e->getMessage());
            return false;
        }
    }
}
