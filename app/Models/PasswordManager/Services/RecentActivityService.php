<?php

namespace Models\PasswordManager\Services;

use Models\PasswordManager\Brokers\LoginAttemptsBroker;
use Models\PasswordManager\Brokers\PasswordBroker;
use Models\PasswordManager\Brokers\PasswordSharingBroker;
use Models\PasswordManager\DTOs\RecentActivity;
use Models\PasswordManager\Utils\BootstrapIcon;

class RecentActivityService
{
    public function getRecentActivities(int $userID, int $limit = 5): array
    {
        $activities = [];

        $activities = array_merge(
            $this->getLoginActivities($userID),
            $this->getPasswordActivities($userID),
            $this->getSharingActivities($userID),
        );
        usort($activities, fn($a, $b) => $b->timestamp <=> $a->timestamp);
        return array_slice($activities, 0, $limit);
    }

    private function getLoginActivities(int $userID): array
    {
        $loginAttempts = new LoginAttemptsBroker()->findByUserID($userID);
        $activities = [];

        foreach ($loginAttempts as $attempt) {
            $dateTime = \DateTime::createFromFormat('Y-m-d H:i:s.u', $attempt->login_time, new \DateTimeZone('UTC'));
            if ($dateTime === false) {
                $dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', $attempt->login_time, new \DateTimeZone('UTC'));
                if ($dateTime === false) {
                    continue;
                }
            }
            $timestamp = $dateTime->getTimestamp();
            $date = self::formatDate($attempt->login_time);
            $location = $attempt->location ?? "Unknown location";
            $title = $attempt->status === "success" ? "Successful Login" : "Failed Login Attempt";
            $description = $attempt->status === "success"
                ? "You logged in from {$location}"
                : "Failed login attempt from {$location}";
            $icon = $attempt->status === "success" ? BootstrapIcon::PersonPlus : BootstrapIcon::PersonCheck;
            $activities[] = new RecentActivity($icon, $title, $description, $date, $timestamp);
        }

        return $activities;
    }

    private function getPasswordActivities(int $userID): array
    {
        $passwords = new PasswordBroker()->findByUserID($userID, EncryptionService::getUserKeyFromSession());
        $activities = [];

        foreach ($passwords as $password) {
            $serviceName = $password->service_name;
            $isUpdate = strtotime($password->updated_at) > strtotime($password->created_at) + 60;
            $rawDate = $isUpdate ? $password->updated_at : $password->created_at;
            $dateTime = \DateTime::createFromFormat('Y-m-d H:i:s.u', $rawDate, new \DateTimeZone('UTC'));
            if ($dateTime === false) {
                $dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', $rawDate, new \DateTimeZone('UTC'));
                if ($dateTime === false) {
                    continue;
                }
            }
            $timestamp = $dateTime->getTimestamp();
            $date = self::formatDate($rawDate);
            $title = $isUpdate ? "Password Updated" : "Password Created";
            $description = $isUpdate
                ? "You updated your {$serviceName} password"
                : "You created a new password for {$serviceName}";
            $icon = BootstrapIcon::KeyFill;
            $activities[] = new RecentActivity($icon, $title, $description, $date, $timestamp);
        }

        return $activities;
    }

    private function getSharingActivities(int $userId): array
    {
        $sharings = new PasswordSharingBroker()->findByOwnerID($userId);
        $activities = [];

        foreach ($sharings as $sharing) {
            $password = new PasswordBroker()->findByIdDecrypt($sharing->password_id, EncryptionService::getUserKeyFromSession());

            if ($password) {
                $serviceName = $password->service_name;
                $dateTime = \DateTime::createFromFormat('Y-m-d H:i:s.u', $sharing->created_at, new \DateTimeZone('UTC'));
                if ($dateTime === false) {
                    $dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', $sharing->created_at, new \DateTimeZone('UTC'));
                    if ($dateTime === false) {
                        continue;
                    }
                }
                $timestamp = $dateTime->getTimestamp();
                $date = $this->formatDate($sharing->created_at);
                $title = "Password Shared";
                $description = "You have shared the password for \"{$serviceName}\"";
                $icon = BootstrapIcon::Share;
                $activities[] = new RecentActivity($icon, $title, $description, $date, $timestamp);
            }
        }

        return $activities;
    }

    private static function formatDate(string $date): string
    {
        $dateTime = \DateTime::createFromFormat('Y-m-d H:i:s.u', $date, new \DateTimeZone('UTC'));
        if ($dateTime === false) {
            $dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', $date, new \DateTimeZone('UTC'));
            if ($dateTime === false) {
                return "Invalid date: $date";
            }
        }
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $isToday = $dateTime->format('Y-m-d') === $now->format('Y-m-d');

        return $isToday
            ? "Today, " . $dateTime->format('H:i A')
            : $dateTime->format('M j, H:i A');
    }
}
