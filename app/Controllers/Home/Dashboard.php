<?php

namespace Controllers\Home;

use Controllers\SecureController;
use Models\PasswordManager\Services\PasswordService;
use Models\Exceptions\FormException;
use Models\PasswordManager\Services\PasswordSharingService;
use Models\PasswordManager\Services\UserService;
use Zephyrus\Core\Session;
use Zephyrus\Network\Response;
use Zephyrus\Network\Router\Get;
use Zephyrus\Network\Router\Post;

class Dashboard extends SecureController
{
    private PasswordService $passwordService;
    private PasswordSharingService $sharingService;
    private UserService $userService;

    public function __construct()
    {
        $this->passwordService = new PasswordService();
        $this->sharingService = new PasswordSharingService();
        $this->userService = new UserService();
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
        $sharedWithMe = $this->sharingService->getSharedPasswords();
        $sharedByMe = $this->sharingService->getPasswordShared();
        $revealedPasswordIds = Session::get('revealed_password_ids', []);
        $revealedSharedIds = Session::get('revealed_shared_ids', []);

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
            'sharedWithMe' => $sharedWithMe,
            'sharedByMe' => $sharedByMe,
            'revealedPasswordIds' => $revealedPasswordIds,
            'revealedSharedIds' => $revealedSharedIds,
            'totalPasswords' => $this->passwordService->getPasswordCountByUserId($user->id),
            'duplicatePasswords' => $this->passwordService->getDuplicatePasswordCount($user->id),
            'sharedCount' => count($sharedByMe),
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
            error_log("Error : " . print_r($e, true));
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

    #[Post('/dashboard/share')]
    public function sharePassword(): Response
    {
        try {
            $form = $this->buildForm();
            $passwordId = (int)$form->getValue('password_id');
            $email = $form->getValue('email');

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \Exception("Invalid email format");
            }

            if (!$this->userService->existsByEmail($email)) {
                Session::set('errors', ["No user found with email: $email. Please invite them to join first!"]);
                return $this->redirect('/dashboard');
            }

            $user = $this->getUser();
            $recipient_id = $this->userService->getIdByEmail($email);
            $this->sharingService->sharePassword($passwordId, $user->id, $recipient_id);

            Session::set('success', 'Password shared successfully!');
            return $this->redirect('/dashboard');
        } catch (\Exception $e) {
            Session::set('errors', [$e->getMessage()]);
            return $this->redirect('/dashboard');
        }
    }

    #[Post('/dashboard/sharing/revoke/{id}')]
    public function revokeSharing(int $id): Response
    {
        if ($this->sharingService->revokeSharing($id)) {
            Session::set('success', 'Sharing revoked successfully!');
        } else {
            Session::set('errors', ['Failed to revoke sharing or you don\'t have permission']);
        }
        return $this->redirect('/dashboard');
    }

    #[Post('/dashboard/sharing/revoke-all')]
    public function revokeAllSharings(): Response
    {
        if ($this->sharingService->revokeAllSharings()) {
            Session::set('success', 'All sharings revoked successfully!');
        } else {
            Session::set('errors', ['Failed to revoke some or all sharings']);
        }
        return $this->redirect('/dashboard');
    }

    #[Post('/dashboard/sharing/delete/{id}')]
    public function deleteSharing(int $id): Response
    {
        if ($this->sharingService->deleteSharing($id)) {
            $revealedSharedIds = Session::get('revealed_shared_ids', []);
            if (in_array($id, $revealedSharedIds)) {
                $revealedSharedIds = array_diff($revealedSharedIds, [$id]);
                Session::set('revealed_shared_ids', $revealedSharedIds);
            }
            Session::set('success', 'Shared password removed successfully!');
        } else {
            Session::set('errors', ['Failed to remove shared password']);
        }
        return $this->redirect('/dashboard');
    }

    #[Post('/dashboard/sharing/toggle-reveal/{id}')]
    public function toggleSharedReveal(int $id): Response
    {
        $revealedSharedIds = Session::get('revealed_shared_ids', []);
        if (in_array($id, $revealedSharedIds)) {
            $revealedSharedIds = array_diff($revealedSharedIds, [$id]);
        } else {
            $revealedSharedIds[] = $id;
        }
        Session::set('revealed_shared_ids', $revealedSharedIds);
        return $this->redirect('/dashboard');
    }
}
