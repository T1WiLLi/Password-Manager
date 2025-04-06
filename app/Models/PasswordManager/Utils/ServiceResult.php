<?php

namespace Models\PasswordManager\Utils;

use Models\Core\Entity;

class ServiceResult
{
    public bool $success;
    public ?Entity $subject = null;
    public array $errors = [];
    public ?string $successMessage = null;
    public int $httpStatus;

    public function __construct(
        bool $success,
        ?Entity $subject = null,
        array $errors = [],
        ?string $successMessage = null,
        int $httpStatus = 200
    ) {
        $this->success = $success;
        $this->subject = $subject;
        $this->errors = $errors;
        $this->successMessage = $successMessage;
        $this->httpStatus = $httpStatus;
    }

    public static function success(
        ?Entity $subject = null,
        ?string $successMessage = null,
        int $httpStatus = 200
    ): self {
        return new self(true, $subject, [], $successMessage, $httpStatus);
    }

    public static function error(
        array $errors,
        int $httpStatus = 400,
        ?Entity $subject = null
    ): self {
        return new self(false, $subject, $errors, null, $httpStatus);
    }
}
