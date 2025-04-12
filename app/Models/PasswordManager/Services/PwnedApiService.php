<?php

namespace Models\PasswordManager\Services;

use Zephyrus\Network\HttpRequester;

class PwnedApiService
{
    public static function findBreachCount(string $password): int
    {
        $sha1Hash = strtoupper(sha1($password));
        $sha1Prefix = substr($sha1Hash, 0, 5);

        $httpRequester = new HttpRequester("GET", "https://api.pwnedpasswords.com/range/{$sha1Prefix}");
        $httpRequester->addHeader('Add-Padding', 'true');
        $httpRequester->addHeader('User-Agent', 'JoltSecure/1.0 (Password Manager Security Check)');

        usleep(1500000);

        $httpResponse = $httpRequester->execute();
        if ($httpResponse->getHttpCode() == 404) {
            return 0;
        }

        $breaches = self::formatBreachResponse($httpResponse->getResponse(), $sha1Prefix);
        return $breaches[$sha1Hash] ?? 0;
    }

    private static function formatBreachResponse(string $rawResponse, string $sha1Prefix): array
    {
        $input = trim($rawResponse);
        if (empty($input)) {
            return [];
        }

        $results = explode("\n", $input);
        $breaches = [];
        foreach ($results as $result) {
            $parts = explode(":", trim($result));
            if (count($parts) !== 2) {
                continue;
            }
            list($hashSuffix, $count) = $parts;
            $breaches[$sha1Prefix . $hashSuffix] = (int) $count;
        }

        return $breaches;
    }
}
