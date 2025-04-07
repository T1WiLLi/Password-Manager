<?php

namespace Controllers\Home;

use Controllers\SecureController;
use Models\PasswordManager\Services\PasswordService;
use Models\Exceptions\FormException;
use Zephyrus\Core\Session;
use Zephyrus\Network\Response;
use Zephyrus\Network\Router\Get;
use Zephyrus\Network\Router\Post;

class Dashboard extends SecureController
{
    private PasswordService $passwordService;

    public function __construct()
    {
        $this->passwordService = new PasswordService();
    }

    #[Get('/dashboard')]
    public function dashboard(): Response
    {
        if (Session::get('messages_displayed', false)) {
            Session::remove('errors');
            Session::remove('success');
            Session::remove('messages_displayed');
        }

        $user = $this->getUser();
        $passwords = $this->passwordService->getAllPasswords($user->id);
        $revealedPasswordIds = Session::get('revealed_password_ids', []);

        $errors = Session::get('errors', []);
        $success = Session::get('success', null);

        if (!empty($errors) || $success !== null) {
            Session::set('messages_displayed', true);
        }

        return $this->render('dashboard', [
            'title' => 'Dashboard',
            'username' => $user->username,
            'picture_set' => $user->profile_image != null,
            'profile_image' => $user->profile_image,
            'passwords' => $passwords,
            'revealedPasswordIds' => $revealedPasswordIds,
            'totalPasswords' => $this->passwordService->getPasswordCountByUserId($user->id),
            'duplicatePasswords' => $this->passwordService->getDuplicatePasswordCount($user->id),
            'errors' => $errors,
            'success' => $success,
        ]);
    }

    #[Post('/dashboard/password/create')]
    public function createPassword(): Response
    {
        try {
            $form = $this->buildForm();
            $this->passwordService->createPassword($form);
            Session::set('success', 'Password created successfully.');
            return $this->redirect('/dashboard');
        } catch (FormException $e) {
            $errors = [];
            foreach ($e->getForm()->getErrors() as $field => $messages) {
                $errors[] = "Error in $field: " . implode(', ', $messages);
            }
            Session::set('errors', $errors);
            return $this->redirect('/dashboard');
        } catch (\Exception $e) {
            Session::set('errors', [$e->getMessage()]);
            return $this->redirect('/dashboard');
        }
    }

    #[Post('/dashboard/password/update/{id}')]
    public function updatePassword(int $id): Response
    {
        try {
            $form = $this->buildForm();
            $this->passwordService->updatePassword($form, $id);
            Session::set('success', 'Password updated successfully.');
            return $this->redirect('/dashboard');
        } catch (FormException $e) {
            $errors = [];
            foreach ($e->getForm()->getErrors() as $field => $messages) {
                $errors[] = "Error in $field: " . implode(', ', $messages);
            }
            Session::set('errors', $errors);
            return $this->redirect('/dashboard');
        } catch (\Exception $e) {
            Session::set('errors', [$e->getMessage()]);
            return $this->redirect('/dashboard');
        }
    }

    #[Post('/dashboard/password/delete/{id}')]
    public function deletePassword(int $id): Response
    {
        $this->passwordService->deletePassword($id);
        $revealedPasswordIds = Session::get('revealed_password_ids', []);
        if (in_array($id, $revealedPasswordIds)) {
            $revealedPasswordIds = array_diff($revealedPasswordIds, [$id]);
            Session::set('revealed_password_ids', $revealedPasswordIds);
        }
        Session::set('success', 'Password deleted successfully.');
        return $this->redirect('/dashboard');
    }

    #[Post('/dashboard/password/toggle-reveal/{id}')]
    public function toggleReveal(int $id): Response
    {
        $revealedPasswordIds = Session::get('revealed_password_ids', []);
        if (in_array($id, $revealedPasswordIds)) {
            $revealedPasswordIds = array_diff($revealedPasswordIds, [$id]);
        } else {
            $revealedPasswordIds[] = $id;
        }
        Session::set('revealed_password_ids', $revealedPasswordIds);
        return $this->redirect('/dashboard');
    }
}
