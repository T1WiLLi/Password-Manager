<?php

namespace Models\PasswordManager\Services;

use Models\Exceptions\FormException;
use Models\PasswordManager\Brokers\UserBroker;
use Models\PasswordManager\Entities\User;
use Models\PasswordManager\Utils\ServiceResult;
use Models\PasswordManager\Validators\UserValidator;
use Zephyrus\Application\Form;
use Zephyrus\Security\Cryptography;

class UserService
{
    public function getUser(int $userId): User
    {
        return new UserBroker()->findByIdDecrypt($userId, EncryptionService::getUserKeyFromSession());
    }

    public function updateUser(Form $form): ServiceResult
    {
        try {
            $user = new UserBroker()->findByIdDecrypt($form->getValue("id"), EncryptionService::getUserKeyFromSession());
            UserValidator::validateUser($form, $user);
            $user = updateEntity($user, $form, ["id"]);
            new UserBroker()->update($user, EncryptionService::getUserKeyFromSession());
            return ServiceResult::success(new UserBroker()->findByIdDecrypt($form->getValue("id"), EncryptionService::getUserKeyFromSession()), 'Profile updated successfully!');
        } catch (FormException $e) {
            return ServiceResult::error($e->getForm()->getErrorMessages());
        } catch (\Exception $e) {
            return ServiceResult::error(["An unexpected error occurred: " . $e->getMessage()], 500);
        }
    }

    public function updateUserPassword(Form $form): ServiceResult
    {
        try {
            $oldEncryptionKey = EncryptionService::getUserKeyFromSession();
            $user = new UserBroker()->findByIdDecrypt($form->getValue("id"), $oldEncryptionKey);
            UserValidator::validateUserPassword($form, $user);

            $newSalt = EncryptionService::generateSalt();
            $newEncryptionKey = EncryptionService::deriveEncryptionKey($form->getValue("password"), $newSalt);
            $user->password = Cryptography::hashPassword($form->getValue("password"));
            $user->salt = $newSalt;

            EncryptionService::storeUserKeyInSession($user->id, $newEncryptionKey);
            new PasswordService()->updatePasswordEncryption($user->id, $newEncryptionKey, $oldEncryptionKey);
            new UserBroker()->update($user, $newEncryptionKey);
            return ServiceResult::success(new UserBroker()->findByIdDecrypt($form->getValue("id"), EncryptionService::getUserKeyFromSession()), 'Password updated successfully!');
        } catch (FormException $e) {
            return ServiceResult::error($e->getForm()->getErrorMessages());
        } catch (\Exception $e) {
            return ServiceResult::error(["An unexpected error occurred: " . $e->getMessage()], 500);
        }
    }
}
