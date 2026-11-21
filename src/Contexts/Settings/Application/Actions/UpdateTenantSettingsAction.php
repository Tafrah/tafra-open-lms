<?php

declare(strict_types=1);

namespace Src\Contexts\Settings\Application\Actions;

use Illuminate\Support\Facades\Gate;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Settings\Application\Services\TenantSettings;
use Src\Contexts\Settings\Domain\Models\TenantSetting;

/**
 * بيكتب مجموعة إعدادات للمؤسسة الحالية (docs/05 بند ٤ · docs/24 بند ٦).
 *
 * المؤسسة بتغيّر **المظهر والإشعارات** بس — أي مجموعة تانية (البريد،
 * التخزين، الأمان) عامة وبتترفض هنا حتى للمدير العام.
 */
final readonly class UpdateTenantSettingsAction
{
    public function __construct(private TenantSettings $settings) {}

    /** @param array<string, mixed> $values */
    public function handle(string $group, array $values, ?User $actor = null): void
    {
        if ($actor !== null) {
            Gate::forUser($actor)->authorize('updateGroup', [TenantSetting::class, $group]);
        }

        foreach ($values as $key => $value) {
            $this->settings->set($group, (string) $key, $value);
        }
    }
}
