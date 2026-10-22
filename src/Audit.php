<?php

declare(strict_types=1);

namespace Lvt;

final class Audit
{
    public static function write(?int $userId, string $action, ?string $entityType = null, ?int $entityId = null, ?string $details = null): void
    {
        $ip = null;
        try {
            $ip = Request::current()->ip();
        } catch (\Throwable) {
            $ip = null;
        }
        if ($details !== null) {
            $details = mb_substr($details, 0, 500);
        }
        Database::insertRow('audit_log', [
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'details' => $details,
            'ip' => $ip,
            'created_at' => now_dt(),
        ]);
    }
}
