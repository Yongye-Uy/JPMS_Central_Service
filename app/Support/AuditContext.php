<?php

namespace App\Support;

/**
 * Per-request "who is performing this action" context. Populated by the
 * EnsureInternalToken middleware from the X-Actor-User-Id header that API
 * forwards through from Backend (which knows the authenticated user).
 */
class AuditContext
{
    private static ?int $actorId = null;

    public static function set(?int $actorId): void
    {
        self::$actorId = $actorId;
    }

    public static function actorId(): ?int
    {
        return self::$actorId;
    }
}
