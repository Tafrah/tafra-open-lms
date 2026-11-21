<?php

declare(strict_types=1);

namespace Src\Contexts\Settings\Infrastructure\Policies;

use Illuminate\Auth\Access\Response;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Settings\Application\Services\TenantSettings;
use Src\Contexts\Settings\Domain\Models\TenantSetting;
use Src\Support\Application\Contracts\TenantContext;
use Src\Support\Domain\Authorization\Policy;

/**
 * إعدادات المؤسسة — القراءة لأي عضو، والتعديل لمين معاه صلاحية
 * الإعدادات، وعلى المجموعات المسموح للمؤسسة تعدّلها بس.
 */
final class TenantSettingPolicy extends Policy
{
    protected function resource(): string
    {
        return 'settings';
    }

    public function invariants(): array
    {
        return ['update', 'create', 'delete', 'updateGroup'];
    }

    /**
     * تعديل مجموعة كاملة من صفحة إعدادات المؤسسة. الصلاحية باسم المجموعة
     * (`manage_appearance.settings`)، والمجموعة لازم تكون من المسموح
     * للمؤسسة — قاعدة سلامة بتسري على المدير العام كمان.
     */
    public function updateGroup(User $user, string $group): Response
    {
        return $this->decide()
            ->rule(in_array($group, TenantSettings::TENANT_EDITABLE_GROUPS, true), 'settings.global_group')
            ->permission($user, $this, "manage_{$group}")
            ->rule(app(TenantContext::class)->id() !== null, 'settings.tenant_required')
            ->response();
    }

    public function viewAny(User $user): Response
    {
        return $this->decide()->permission($user, $this, 'view_any')->response();
    }

    public function view(User $user, TenantSetting $setting): Response
    {
        return $this->decide()
            ->permission($user, $this, 'view_any')
            ->ruleOrNotFound($this->belongsToCurrentTenant($setting), 'record_not_found')
            ->response();
    }

    public function create(User $user): Response
    {
        return $this->decide()->permission($user, $this, 'update')->response();
    }

    public function update(User $user, TenantSetting $setting): Response
    {
        return $this->decide()
            ->permission($user, $this, 'update')
            ->ruleOrNotFound($this->belongsToCurrentTenant($setting), 'record_not_found')
            ->rule(
                in_array($setting->group, TenantSettings::TENANT_EDITABLE_GROUPS, true),
                'settings.global_group',
            )
            ->response();
    }

    public function delete(User $user, TenantSetting $setting): Response
    {
        return $this->decide()
            ->permission($user, $this, 'update')
            ->ruleOrNotFound($this->belongsToCurrentTenant($setting), 'record_not_found')
            ->response();
    }

    private function belongsToCurrentTenant(TenantSetting $setting): bool
    {
        $current = app(TenantContext::class)->id();

        return $current === null || $setting->tenant_id === $current;
    }
}
