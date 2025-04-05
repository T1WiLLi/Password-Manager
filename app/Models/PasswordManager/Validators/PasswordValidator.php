<?php

use Models\Core\Validators\Validator;
use Models\Exceptions\FormException;
use Zephyrus\Application\Form;
use Zephyrus\Application\Rule;

class PasswordValidator
{
    public static function validatePassword(Form $form): void
    {
        $form->field("service_name", [
            Rule::required("Service name is required"),
            Rule::minLength(3, "Service name must be at least 3 characters long"),
        ]);
        $form->field("username", [
            Rule::required("Username is required"),
            Rule::minLength(3, "Username must be at least 3 characters long"),
        ]);
        $form->field("password", [
            Rule::required("Password is required"),
            Rule::passwordCompliant("Password must include at least 8 characters, an uppercase letter, a lowercase letter, a number and a special character."),
        ]);

        if (!$form->verify()) {
            throw new FormException($form);
        }
    }
}
