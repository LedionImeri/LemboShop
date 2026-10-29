<?php
declare(strict_types=1);

/**
 * Fixed-window rate limiter stored in the `rate_limits` table.
 * Identifiers are hashed so raw IPs/emails are never stored.
 */
final class RateLimiter
{
    public static function tooMany(string $scope, string $identifier): bool
    {
        [$max, $window] = self::limits($scope);
        $hits = Database::value(
            'SELECT hits FROM rate_limits
              WHERE scope = ? AND identifier_hash = ? AND window_start > UTC_TIMESTAMP() - INTERVAL ? SECOND',
            [$scope, self::hash($identifier), $window]
        );
        return $hits !== null && (int) $hits >= $max;
    }

    public static function hit(string $scope, string $identifier): void
    {
        [, $window] = self::limits($scope);
        // `hits` is assigned before `window_start`, so both IFs still see the old window_start
        Database::query(
            'INSERT INTO rate_limits (scope, identifier_hash, hits, window_start) VALUES (?, ?, 1, UTC_TIMESTAMP())
             ON DUPLICATE KEY UPDATE
               hits = IF(window_start <= UTC_TIMESTAMP() - INTERVAL ? SECOND, 1, hits + 1),
               window_start = IF(window_start <= UTC_TIMESTAMP() - INTERVAL ? SECOND, UTC_TIMESTAMP(), window_start)',
            [$scope, self::hash($identifier), $window, $window]
        );
    }

    /** Seconds until the current window resets. */
    public static function availableIn(string $scope, string $identifier): int
    {
        [, $window] = self::limits($scope);
        $start = Database::value(
            'SELECT TIMESTAMPDIFF(SECOND, UTC_TIMESTAMP(), window_start + INTERVAL ? SECOND)
               FROM rate_limits WHERE scope = ? AND identifier_hash = ?',
            [$window, $scope, self::hash($identifier)]
        );
        return max(0, (int) $start);
    }

    public static function clear(string $scope, string $identifier): void
    {
        Database::query('DELETE FROM rate_limits WHERE scope = ? AND identifier_hash = ?', [$scope, self::hash($identifier)]);
    }

    private static function limits(string $scope): array
    {
        return config('rate_limits')[$scope] ?? [30, 600];
    }

    private static function hash(string $identifier): string
    {
        return hash('sha256', $identifier);
    }
}
