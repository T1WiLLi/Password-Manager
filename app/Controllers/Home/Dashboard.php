<?php

namespace Controllers\Home;

use Controllers\SecureController;
use Models\Exceptions\FormException;
use Models\PasswordManager\Brokers\UserBroker;
use Models\PasswordManager\Services\UserService;
use Zephyrus\Network\Response;
use Zephyrus\Network\Router\Get;
use Zephyrus\Network\Router\Post;
use Zephyrus\Security\Cryptography;

class Dashboard extends SecureController
{
    private UserService $userService;

    public function __construct()
    {
        $this->userService = new UserService();
    }

    #[Get('/dashboard')]
    public function dashboard(): Response
    {
        $user = (new UserBroker())->findByIdDecrypt($this->getAuth()['user_id'], $this->getAuth()['user_key']);
        return $this->render('dashboard', [
            'title' => 'Dashboard',
            'username' => $user->username,
            'picture_set' => $user->profile_image != null,
            'profile_image' => $user->profile_image,
        ]);
    }

    #[Get('/profile')]
    public function profile(): Response
    {
        $user = (new UserBroker())->findByIdDecrypt($this->getAuth()['user_id'], $this->getAuth()['user_key']);
        return $this->render('profile', [
            'title' => 'Profile',
            'user' => $user,
            'errors' => [],
            'success' => null
        ]);
    }

    #[Post('/profile/update-password')]
    public function updatePassword(): Response
    {
        $user = (new UserBroker())->findByIdDecrypt($this->getAuth()['user_id'], $this->getAuth()['user_key']);
        $form = $this->buildForm();
        $errors = ['password' => []];

        try {
            if ($form->getValue('password') !== $form->getValue('confirm_password')) {
                $errors['password'][] = 'New password and confirm password do not match!';
                return $this->render('profile', [
                    'title' => 'Profile',
                    'user' => $user,
                    'errors' => $errors,
                    'success' => null
                ]);
            }

            if (!Cryptography::verifyHashedPassword($form->getValue('old_password'), $user->password)) {
                $errors['password'][] = 'Old password is incorrect!';
                return $this->render('profile', [
                    'title' => 'Profile',
                    'user' => $user,
                    'errors' => $errors,
                    'success' => null
                ]);
            }

            $this->userService->updateUserPassword($form);
            return $this->render('profile', [
                'title' => 'Profile',
                'user' => $user,
                'errors' => [],
                'success' => 'Password updated successfully!'
            ]);
        } catch (FormException $e) {
            $errors['password'] = $e->getForm()->getErrorMessages();
            return $this->render('profile', [
                'title' => 'Profile',
                'user' => $user,
                'errors' => $errors,
                'success' => null
            ]);
        } catch (\Exception $e) {
            $errors['password'][] = "An unexpected error occurred: " . $e->getMessage();
            return $this->render('profile', [
                'title' => 'Profile',
                'user' => $user,
                'errors' => $errors,
                'success' => null
            ]);
        }
    }

    #[Post('/profile/update-details')]
    public function updateDetails(): Response
    {
        $user = (new UserBroker())->findByIdDecrypt($this->getAuth()['user_id'], $this->getAuth()['user_key']);
        $form = $this->buildForm();
        $errors = ['details' => []];

        try {
            $this->userService->updateUser($form);
            return $this->render('profile', [
                'title' => 'Profile',
                'user' => $user,
                'errors' => [],
                'success' => 'Profile updated successfully!'
            ]);
        } catch (FormException $e) {
            $errors['details'] = $e->getForm()->getErrorMessages();
            return $this->render('profile', [
                'title' => 'Profile',
                'user' => $user,
                'errors' => $errors,
                'success' => null
            ]);
        } catch (\Exception $e) {
            $errors['details'][] = "An unexpected error occurred: " . $e->getMessage();
            return $this->render('profile', [
                'title' => 'Profile',
                'user' => $user,
                'errors' => $errors,
                'success' => null
            ]);
        }
    }
}
