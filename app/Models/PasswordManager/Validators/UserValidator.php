<?php

namespace Models\PasswordManager\Validators;

use Models\Exceptions\FormException;
use Models\PasswordManager\Entities\User;
use Zephyrus\Application\Form;
use Zephyrus\Application\Rule;
use Zephyrus\Security\Cryptography;

class UserValidator
{
    public static function validateUser(Form $form, User $currentUser): void
    {
        if ($form->getValue("username") != null) {
            $form->field("username", [
                Rule::required("Username is required"),
                new Rule(function ($newUsername) use ($currentUser) {
                    return $newUsername != "admin";
                }, "Username cannot be 'admin'"),
                Rule::maxLength(50, "Username must not exceed 50 characters")
            ]);
        }

        if ($form->getValue("first_name") != null) {
            $form->field("first_name", [
                Rule::maxLength(50, "First name must not exceed 50 characters")
            ]);
        }

        if ($form->getValue("last_name") != null) {
            $form->field("last_name", [
                Rule::maxLength(50, "Last name must not exceed 50 characters")
            ]);
        }

        if ($form->getValue("phone_number") != null) {
            $form->field("phone_number", [
                Rule::phone("Phone number must be a valid format (e.g., +1234567890)"),
                Rule::maxLength(15, "Phone number must not exceed 15 characters")
            ]);
        }

        if ($form->getValue("profile_image") != null) {
            $form->field("profile_image", [
                Rule::url("Profile image must be a valid URL", true)
            ]);
        }

        if (!$form->verify()) {
            throw new FormException($form);
        }
    }

    public static function validateUserPassword(Form $form, User $currentUser): void
    {
        $form->field("password", [
            Rule::required("Password is required"),
            Rule::passwordCompliant("Password must include at least 8 characters, an uppercase letter, a lowercase letter, and a number."),
            new Rule(function ($newPassword) use ($currentUser) {
                return !Cryptography::verifyHashedPassword($newPassword, $currentUser->password);
            }, "Password must not be the same as the current one!")
        ]);

        $form->field("old_password", [
            Rule::required("Old password is required"),
            new Rule(function ($oldPassword) use ($currentUser) {
                return Cryptography::verifyHashedPassword($oldPassword, $currentUser->password);
            }, "Old password is incorrect!")
        ]);

        if (!$form->verify()) {
            throw new FormException($form);
        }
    }
}
