<?php

namespace Models\PasswordManager\Services;

use DateInterval;
use DateTime;
use Models\PasswordManager\Brokers\MfaMethodsBroker;
use Models\PasswordManager\Brokers\UserBroker;
use Models\PasswordManager\Entities\MFAMethods;
use Models\PasswordManager\Entities\User;
use Models\PasswordManager\mfa\Authentificator;
use Models\PasswordManager\mfa\EmailMFA;
use Models\PasswordManager\mfa\SmsMFA;
use Zephyrus\Core\Session;

class MfaService
{
    public const TYPE_EMAIL = 'email';
    public const TYPE_SMS = 'sms';
    public const TYPE_AUTHENTICATOR = 'authenticator';

    private const FLAG_EMAIL = 1;
    private const FLAG_SMS = 2;
    private const FLAG_AUTHENTICATOR = 4;

    private const CODE_TTL_SECONDS = 600;
    private const GRACE_PERIOD = 'P20D';

    private UserBroker $userBroker;
    private MfaMethodsBroker $methodBroker;
    private Authentificator $authenticator;
    private EmailMFA $emailMfa;
    private SmsMFA $smsMfa;
    public function __construct()
    {
        $this->userBroker = new UserBroker();
        $this->methodBroker = new MfaMethodsBroker();
        $this->authenticator = new Authentificator();
        $this->emailMfa = new EmailMFA();
        $this->smsMfa = new SmsMFA();
    }

    public function setMethodEnabled(int $userId, string $methodType, bool $enable): User
    {
        $user = $this->loadUser($userId);
        $flag = $this->getFlagForType($methodType);

        $user->mfa_config = $enable
            ? ($user->mfa_config | $flag)
            : ($user->mfa_config & (~$flag));

        $this->userBroker->update($user, $this->getUserKey());

        $this->setupMethodRecord($userId, $methodType, $enable);

        return $user;
    }

    public function isMethodEnabled(int $userId, string $methodType): bool
    {
        $user = $this->loadUser($userId);
        return (bool)($user->mfa_config & $this->getFlagForType($methodType));
    }

    public function getLastVerification(int $userId, string $methodType): ?string
    {
        return $this->methodBroker
            ->findByUserAndType($userId, $methodType)
            ?->last_verification;
    }

    public function getMethodUpdatedAt(int $userId, string $methodType): ?string
    {
        return $this->methodBroker
            ->findByUserAndType($userId, $methodType)
            ?->updated_at;
    }

    public function getAuthenticatorQrCode(int $userId): string
    {
        $record = $this->methodBroker->findByUserAndType($userId, self::TYPE_AUTHENTICATOR);
        if (!$record || !$record->secret_data) {
            throw new \RuntimeException("Authenticator not enabled for user $userId");
        }
        $username = $this->loadUser($userId)->username;
        return $this->authenticator->getQRCodeInline($username, $record->secret_data);
    }

    public function verifyAuthenticatorCode(int $userId, string $code): bool
    {
        $record = $this->methodBroker->findByUserAndType($userId, self::TYPE_AUTHENTICATOR);
        if (!$record || !$record->secret_data) {
            return false;
        }
        if ($this->authenticator->verifyCode($record->secret_data, $code)) {
            $this->recordVerificationAndGrace($userId, self::TYPE_AUTHENTICATOR);
            return true;
        }
        return false;
    }

    public function sendEmailMfaCode(int $userId): bool
    {
        $user = $this->loadUser($userId);
        if (empty($user->email)) {
            throw new \RuntimeException("User or email missing.");
        }
        $code = $this->emailMfa->generateCode();
        $this->storeSessionCode($userId, self::TYPE_EMAIL, $code);
        return $this->emailMfa->sendCode($user->email, $code);
    }

    public function verifyEmailMfaCode(int $userId, string $code): bool
    {
        if ($this->verifySessionCode($userId, self::TYPE_EMAIL, $code)) {
            $this->recordVerificationAndGrace($userId, self::TYPE_EMAIL);
            return true;
        }
        return false;
    }

    public function sendSmsMfaCode(int $userId): bool
    {
        $user = $this->loadUser($userId);
        if (empty($user->phone_number)) {
            throw new \RuntimeException("User or phone missing.");
        }
        $code = $this->smsMfa->generateCode();
        $this->storeSessionCode($userId, self::TYPE_SMS, $code);
        $sentCode = $this->smsMfa->sendCode($user->phone_number, $code);
        return $sentCode === $code;
    }

    public function verifySmsMfaCode(int $userId, string $code): bool
    {
        if ($this->verifySessionCode($userId, self::TYPE_SMS, $code)) {
            $this->recordVerificationAndGrace($userId, self::TYPE_SMS);
            return true;
        }
        return false;
    }

    private function loadUser(int $userId): User
    {
        return $this->userBroker->findByIdDecrypt($userId, $this->getUserKey());
    }

    private function getUserKey(): string
    {
        return EncryptionService::getUserKeyFromSession();
    }

    private function getFlagForType(string $type): int
    {
        return match ($type) {
            self::TYPE_EMAIL => self::FLAG_EMAIL,
            self::TYPE_SMS => self::FLAG_SMS,
            self::TYPE_AUTHENTICATOR => self::FLAG_AUTHENTICATOR,
            default => throw new \InvalidArgumentException("Unknown MFA method: $type"),
        };
    }

    private function setupMethodRecord(int $userId, string $methodType, bool $enable): void
    {
        $record = $this->methodBroker->findByUserAndType($userId, $methodType)
            ?? new MFAMethods();

        if (!isset($record->id)) {
            $record->user_id = $userId;
            $record->method_type = $methodType;
        }

        $record->is_enabled = $enable;

        if ($methodType === self::TYPE_AUTHENTICATOR) {
            $record->secret_data = $enable
                ? $this->authenticator->generateSecret()
                : null;
        } else {
            $record->secret_data = null;
        }

        $record->last_verification = null;

        $this->methodBroker->save($record);
    }

    private function getSessionKey(int $userId, string $methodType): string
    {
        return "mfa_{$methodType}_{$userId}";
    }

    private function storeSessionCode(int $userId, string $methodType, string $code): void
    {
        Session::set($this->getSessionKey($userId, $methodType), [
            'code' => $code,
            'expires' => time() + self::CODE_TTL_SECONDS,
        ]);
    }

    private function verifySessionCode(int $userId, string $methodType, string $code): bool
    {
        $key = $this->getSessionKey($userId, $methodType);
        $data = Session::get($key);

        if (!$data || time() > $data['expires'] || $data['code'] !== $code) {
            Session::remove($key);
            return false;
        }

        Session::remove($key);
        return true;
    }

    private function recordVerificationAndGrace(int $userId, string $methodType): void
    {
        $record = $this->methodBroker->findByUserAndType($userId, $methodType);
        if ($record) {
            $now = (new DateTime())->format('Y-m-d H:i:s');
            $record->last_verification = $now;
            $this->methodBroker->save($record);
        }

        $user = $this->loadUser($userId);
        $graceUntil = (new DateTime())
            ->add(new DateInterval(self::GRACE_PERIOD))
            ->format('Y-m-d H:i:s');
        $user->mfa_grace_period_until = $graceUntil;
        $this->userBroker->update($user, $this->getUserKey());
    }
}
