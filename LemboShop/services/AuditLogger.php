<?php
declare(strict_types=1);

/**
 * Records important administrative actions (who, what, when).
 */
final class AuditLogger
{
    public static function log(string $action, string $entityType, ?int $entityId = null, array $details = []): void
    {
        Database::query(
            'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details, ip_hash) VALUES (?, ?, ?, ?, ?, ?)',
            [
                Auth::id(),
                $action,
                $entityType,
                $entityId,
                $details ? json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                IS_CLI ? null : ip_hash(),
            ]
        );
    }
}
