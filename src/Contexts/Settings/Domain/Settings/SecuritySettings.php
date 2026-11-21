<?php

declare(strict_types=1);

namespace Src\Contexts\Settings\Domain\Settings;

use Spatie\LaravelSettings\Settings;

final class SecuritySettings extends Settings
{
    public int $password_min_length;

    public bool $password_requires_uncompromised;

    public int $password_history_size;

    public int $password_expiry_days;

    /** @var list<string> */
    public array $two_factor_required_roles;

    public int $two_factor_grace_period_days;

    public int $session_lifetime_minutes;

    public int $impersonation_max_minutes;

    /** مفتاح إيقاف خادم MCP للنظام كله (docs/23 بند ٥) */
    public bool $mcp_enabled = true;

    public static function group(): string
    {
        return 'security';
    }

    /** @return list<string> */
    public static function encrypted(): array
    {
        return [];
    }
}
