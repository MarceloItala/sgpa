<?php
declare(strict_types=1);

namespace SGPA\Core;

class TenantScope
{
    private static ?string $currentTenantId = null;

    public static function setTenant(string $tenantId): void
    {
        self::$currentTenantId = $tenantId;
    }

    public static function getCurrentTenant(): ?string
    {
        return self::$currentTenantId;
    }

    public static function clear(): void
    {
        self::$currentTenantId = null;
    }
}
