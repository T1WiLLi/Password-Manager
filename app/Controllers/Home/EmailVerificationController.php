<?php

namespace Controllers\Home;

use Controllers\Controller;
use Models\PasswordManager\Services\EmailVerificationService;
use Models\PasswordManager\Services\UserService;
use Zephyrus\Network\Response;
use Zephyrus\Network\Router\Get;
use Zephyrus\Network\Router\Post;

class EmailVerificationController extends Controller
{
    private EmailVerificationService $service;

    public function __construct()
    {
        $this->service = new EmailVerificationService();
    }

    #[Get('/verify-email/{token}')]
    public function verifyEmail(string $token): Response
    {
        try {
            $this->service->verifiyEmail($token);
            return $this->redirect('/dashboard', ['success' => 'Email successfully verified!']);
        } catch (\Exception $e) {
            if ($e instanceof \LogicException) {
                $userId = (int)substr($e->getMessage(), strpos($e->getMessage(), '.') + 1);
                return $this->render('verify-email', [
                    'title' => 'Email Verification',
                    'error' => 'Verification link has expired',
                    'expired' => true,
                    'userId' => $userId
                ]);
            }
            return $this->render('verify-email', [
                'title' => 'Email Verification',
                'error' => 'Invalid verification link',
                'expired' => false
            ]);
        }
    }

    #[Post('/resend-verification')]
    public function resendVerification(): Response
    {
        $errors = [];
        try {
            $form = $this->buildForm();
            $email = $form->getValue('email');
            $userId = (int)$form->getValue('user_id');

            if (!new UserService()->existsByEmail($email)) {
                throw new \LogicException('User not found');
            }

            if ($this->service->createVerification($userId, $email)) {
                return $this->redirect('/login', ['success' => 'Verification email resent successfully']);
            }

            throw new \Exception('Failed to resend verification email');
        } catch (\Exception $e) {
            $errors['general'] = $e->getMessage();
            return $this->render('login', [
                'title' => 'Login',
                'errors' => $errors
            ]);
        }
    }
}
