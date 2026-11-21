<?php

declare(strict_types=1);

namespace Src\Contexts\Installer\Application\Actions;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Src\Contexts\Identity\Domain\Enums\UserStatus;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Installer\Application\DataObjects\AcademyData;
use Src\Contexts\Installer\Application\DataObjects\AcademyOwner;
use Src\Contexts\Settings\Domain\Settings\GeneralSettings;
use Src\Contexts\Tenancy\Application\Actions\ProvisionTenantAction;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;
use Src\Support\Infrastructure\Authorization\CentralRoles;

/**
 * المنصة (المؤسسة الوحيدة في وضع single) وصاحبها.
 *
 * نفس منطق `TenantSeeder` + `UserSeeder` بس كـ Action: المدرّس `admin` في
 * مؤسسته **و** `super_admin` على الفريق المركزي — هو مالك التثبيت كله.
 *
 * ⚠️ سياق المؤسسة بيتضبط **قبل** `syncRoles()` — أدوار spatie مربوطة
 * بالفريق، والإسناد من غير سياق بيتكتب على فريق غلط (docs/03 بند ٣).
 *
 * idempotent: لو التثبيت وقف بعد الخطوة دي وأعاده، مابيتعملش مؤسسة تانية.
 */
final readonly class CreateAcademyOwnerAction
{
    public function __construct(
        private ProvisionTenantAction $provision,
        private TenantContext $context,
        private CentralRoles $centralRoles,
    ) {}

    public function handle(AcademyData $data): AcademyOwner
    {
        $tenant = $this->tenant($data);
        $user = $this->user($data);

        $user->tenants()->syncWithoutDetaching([$tenant->getKey() => ['joined_at' => now()]]);

        $role = (string) config('installer.tenant.owner_role');

        $this->context->forTenant((int) $tenant->getKey(), static function () use ($user, $role): void {
            $user->unsetRelation('roles');
            $user->syncRoles([$role]);
        });

        $this->centralRoles->assign($user, (string) config('authorization.super_admin_role'));

        $this->context->set(null);

        $this->settings($data);

        return new AcademyOwner($tenant, $user->refresh());
    }

    private function tenant(AcademyData $data): Tenant
    {
        $name = ['ar' => $data->platformName, 'en' => $data->platformName];
        $description = ['ar' => $data->subject, 'en' => $data->subject];

        $existing = Tenant::query()->withTrashed()->orderBy('id')->first();

        if ($existing instanceof Tenant) {
            if ($existing->trashed()) {
                $existing->restore();
            }

            $existing->fill(['name' => $name, 'description' => $description, 'is_active' => true])->save();

            return $existing;
        }

        return $this->provision->handle([
            'slug' => $this->slug($data->platformName),
            'name' => $name,
            'description' => $description,
            'primary_color' => (string) config('installer.tenant.primary_color'),
            'is_active' => true,
        ]);
    }

    private function user(AcademyData $data): User
    {
        $user = User::query()->withTrashed()->where('email', $data->adminEmail)->first() ?? new User;

        $user->forceFill([
            'name' => $data->teacherName,
            'email' => $data->adminEmail,
            'phone' => $data->adminPhone,
            'password' => Hash::make($data->adminPassword),
            'email_verified_at' => $user->email_verified_at ?? now(),
            'status' => UserStatus::Active,
            'is_active' => true,
            'locale' => 'ar',
            'deleted_at' => null,
        ])->save();

        return $user;
    }

    private function settings(AcademyData $data): void
    {
        $general = app(GeneralSettings::class);

        $general->app_name = ['ar' => $data->platformName, 'en' => $data->platformName];
        $general->support_email = $data->adminEmail;

        if ($data->adminPhone !== null) {
            $general->support_phone = $data->adminPhone;
        }

        $general->save();
    }

    /** اسم عربي بيتحوّل لحروف لاتينية غريبة في الرابط — الأفضل اسم ثابت. */
    private function slug(string $name): string
    {
        $slug = preg_match('/^[\x20-\x7E]+$/', $name) === 1 ? Str::limit(Str::slug($name), 40, '') : '';

        return $slug !== '' ? $slug : (string) config('installer.tenant.fallback_slug');
    }
}
