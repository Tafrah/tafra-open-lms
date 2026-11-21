<?php

declare(strict_types=1);

namespace Src\Contexts\Settings\Domain\Settings;

use Spatie\LaravelSettings\Settings;

final class NotificationSettings extends Settings
{
    public bool $database_enabled;

    public bool $mail_enabled;

    public bool $broadcast_enabled;

    public int $prune_read_after_days;

    public int $digest_hour;

    public static function group(): string
    {
        return 'notifications';
    }

    /** @return list<string> */
    public static function encrypted(): array
    {
        return [];
    }
}
