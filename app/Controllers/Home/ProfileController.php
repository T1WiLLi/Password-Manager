<?php

namespace Controllers\Home;

use Controllers\SecureController;
use Models\PasswordManager\Services\UserService;
use Zephyrus\Network\Response;
use Zephyrus\Network\Router\Get;
use Zephyrus\Network\Router\Post;

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
        $user = $this->getUser();
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
        $form = $this->buildForm();
        $form->addField('id', $this->getAuth()['user_id']);

        $result = $this->userService->updateUserPassword($form);
        $user = $result->success ? $result->subject : $this->getUser();

        return $this->render('profile', [
            'title' => 'Profile',
            'user' => $user,
            'errors' => ['password' => $result->errors],
            'success' => $result->successMessage
        ], $result->httpStatus);
    }

    #[Post('/profile/update-details')]
    public function updateDetails(): Response
    {
        $form = $this->buildForm();
        $form->addField('id', $this->getAuth()['user_id']);

        $result = $this->userService->updateUser($form);
        $user = $result->success ? $result->subject : $this->getUser();;

        return $this->render('profile', [
            'title' => 'Profile',
            'user' => $user,
            'errors' => ['details' => $result->errors],
            'success' => $result->successMessage
        ], $result->httpStatus);
    }
}
