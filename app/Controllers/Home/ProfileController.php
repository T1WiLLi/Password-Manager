<?php

namespace Controllers\Home;

use Controllers\SecureController;
use Models\Exceptions\FormException;
use Models\PasswordManager\Services\UserService;
use Zephyrus\Network\Response;
use Zephyrus\Network\Router\Get;
use Zephyrus\Network\Router\Post;
use Zephyrus\Security\Cryptography;

class ProfileController extends SecureController
{
    private UserService $userService;

    public function __construct()
    {
        $this->userService = new UserService();
    }

    #[Get('/profile')]
    public function profile(): Response
    {
        return $this->render('profile', [
            'title' => 'Profile',
            'user' => $this->getUser(),
            'errors' => [],
            'success' => null
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
}
