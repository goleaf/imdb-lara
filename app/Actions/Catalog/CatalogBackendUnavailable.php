<?php

namespace App\Actions\Catalog;

use Throwable;

final class CatalogBackendUnavailable
{
    /**
     * @var list<string>
     */
    private const MESSAGE_FRAGMENTS = [
        'max_connections_per_hour',
        'SQLSTATE[HY000] [1226]',
        'Connection refused',
        'php_network_getaddresses',
        'No route to host',
    ];

    public static function matches(Throwable $throwable): bool
    {
        $message = self::normalizeMessage($throwable);

        foreach (self::MESSAGE_FRAGMENTS as $messageFragment) {
            if (str_contains($message, $messageFragment)) {
                return true;
            }
        }

        return false;
    }

    public static function userMessage(bool $usingStaleCache = false): string
    {
        if ($usingStaleCache) {
            return 'The imported IMDb catalog could not be reached. Showing the most recent successful catalog snapshot while the live catalog recovers.';
        }

        return 'The imported IMDb catalog could not be reached. Try again in a few minutes.';
    }

    public static function themeLaneMessage(): string
    {
        return 'We could not load the imported interest-category lanes right now. Browse all themes or try again in a few minutes.';
    }

    private static function normalizeMessage(Throwable $throwable): string
    {
        return preg_replace('/\s+/', ' ', trim($throwable->getMessage())) ?? '';
    }
}
