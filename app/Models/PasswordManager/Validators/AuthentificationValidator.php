<?php

namespace Models\PasswordManager\Validators;

use Models\Exceptions\FormException;
use Zephyrus\Application\Form;
use Zephyrus\Application\Rule;

class AuthentificationValidator
{
    public static function validateLogin(Form $form): void
    {
        $form->field("email", [
            Rule::required("Email Address is required."),
            Rule::email("Invalid email address.")
        ]);
        $form->field("password", [
            Rule::required("Password is required.")
        ]);

        if (!$form->verify()) {
            throw new FormException($form);
        }
    }

    public static function validateRegister(Form $form): void
    {
        $form->field("username", [
            Rule::required("Username is required"),
            Rule::minLength(3, "Username must be at least 3 characters long"),
            Rule::maxLength(32, "Username must be at most 32 characters long"),
        ]);
        $form->field("email", [
            Rule::required("Email Address is required"),
            Rule::email("Invalid email address"),
        ]);
        $form->field("password", [
            Rule::required("Password is required"),
            Rule::passwordCompliant("Password must include at least 8 characters, an uppercase letter, a lowercase letter, and a number."),

        ]);
        $form->field("first_name", [
            Rule::required("First name is required"),
            Rule::minLength(2, "First name must be at least 2 characters long"),
            Rule::maxLength(32, "First name must be at most 32 characters long"),
        ]);
        $form->field("last_name", [
            Rule::required("Last name is required"),
            Rule::minLength(2, "Last name must be at least 2 characters long"),
            Rule::maxLength(32, "Last name must be at most 32 characters long"),
        ]);
        $form->field("phone_number", [
            Rule::required("Phone number is required"),
            Rule::phone("Phone number must be in a valid format (e.g., XXX-XXX-XXXX, (XXX) XXX-XXXX, +XX XXX-XXX-XXXX)"),
        ]);

        if (!$form->verify()) {
            throw new FormException($form);
        }
    }
}
