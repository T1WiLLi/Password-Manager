<?php

namespace Controllers\Home;

use Controllers\SecureController;
use Models\Exceptions\FormException;
use Models\PasswordManager\Services\MfaService;
use Models\PasswordManager\Services\UserService;
use Zephyrus\Network\Response;
use Zephyrus\Network\Router\Get;
use Zephyrus\Network\Router\Post;
use Zephyrus\Security\Cryptography;

class ProfileController extends SecureController
{
    private UserService $userService;
    private MfaService $mfaService;

    public function __construct()
    {
        $this->userService = new UserService();
        $this->mfaService = new MfaService();
    }

    #[Get('/profile')]
    public function profile(): Response
    {
        $user = $this->getUser();
        $mfa  = $this->buildMfaData($user->id);

        return $this->render('profile', [
            'title' => 'Profile',
            'user' => $user,
            'errors' => [],
            'success' => null,
            'mfa' => $mfa
        ]);
    }

    #[Post('/profile/update-mfa')]
    public function updateMfa(): Response
    {
        $form = $this->buildForm();
        $user = $this->getUser();
        $methodType = $form->getValue('method_type');
        $enable = (bool)$form->getValue('enable');

        $this->mfaService->setMethodEnabled($user->id, $methodType, $enable);

        $mfa = $this->buildMfaData($user->id);
        $message = ucfirst($methodType) . ' MFA ' . ($enable ? 'enabled' : 'disabled') . '!';

        return $this->render('profile', [
            'title' => 'Profile',
            'user' => $user,
            'errors' => [],
            'success' => $message,
            'mfa' => $mfa
        ]);
    }

    #[Post('/profile/update-password')]
    public function updatePassword(): Response
    {
        $form = $this->buildForm();
        $errors = ['password' => []];

        try {
            if ($form->getValue('password') !== $form->getValue('confirm_password')) {
                $errors['password'][] = 'New password and confirm password do not match!';
                return $this->render('profile', [
                    'title' => 'Profile',
                    'user' => $this->getUser(),
                    'errors' => $errors,
                    'success' => null
                ]);
            }

            if (!Cryptography::verifyHashedPassword($form->getValue('old_password'), $this->getUser()->password)) {
                $errors['password'][] = 'Old password is incorrect!';
                return $this->render('profile', [
                    'title' => 'Profile',
                    'user' => $this->getUser(),
                    'errors' => $errors,
                    'success' => null
                ]);
            }

            $this->userService->updateUserPassword($form);
            return $this->render('profile', [
                'title' => 'Profile',
                'user' => $this->getUser(),
                'errors' => [],
                'success' => 'Password updated successfully!'
            ]);
        } catch (FormException $e) {
            $errors['password'] = $e->getForm()->getErrorMessages();
            return $this->render('profile', [
                'title' => 'Profile',
                'user' => $this->getUser(),
                'errors' => $errors,
                'success' => null
            ]);
        } catch (\Exception $e) {
            $errors['password'][] = "An unexpected error occurred: " . $e->getMessage();
            return $this->render('profile', [
                'title' => 'Profile',
                'user' => $this->getUser(),
                'errors' => $errors,
                'success' => null
            ]);
        }
    }

    #[Post('/profile/update-details')]
    public function updateDetails(): Response
    {
        $form = $this->buildForm();
        $errors = ['details' => []];

        try {
            $this->userService->updateUser($form);
            return $this->render('profile', [
                'title' => 'Profile',
                'user' => $this->getUser(),
                'errors' => [],
                'success' => 'Profile updated successfully!'
            ]);
        } catch (FormException $e) {
            $errors['details'] = $e->getForm()->getErrorMessages();
            return $this->render('profile', [
                'title' => 'Profile',
                'user' => $this->getUser(),
                'errors' => $errors,
                'success' => null
            ]);
        } catch (\Exception $e) {
            $errors['details'][] = "An unexpected error occurred: " . $e->getMessage();
            return $this->render('profile', [
                'title' => 'Profile',
                'user' => $this->getUser(),
                'errors' => $errors,
                'success' => null
            ]);
        }
    }

    private function buildMfaData(int $userId): array
    {
        return [
            'email' => [
                'enabled' => $this->mfaService->isMethodEnabled($userId, MfaService::TYPE_EMAIL),
                'lastVerification' => $this->mfaService->getLastVerification($userId, MfaService::TYPE_EMAIL),
            ],
            'sms' => [
                'enabled'          => $this->mfaService->isMethodEnabled($userId, MfaService::TYPE_SMS),
                'lastVerification' => $this->mfaService->getLastVerification($userId, MfaService::TYPE_SMS),
            ],
            'authenticator' => [
                'enabled'          => $this->mfaService->isMethodEnabled($userId, MfaService::TYPE_AUTHENTICATOR),
                'lastVerification' => $this->mfaService->getLastVerification($userId, MfaService::TYPE_AUTHENTICATOR),
            ],
        ];
    }
}
