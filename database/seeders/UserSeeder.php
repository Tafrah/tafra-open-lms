<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Src\Contexts\Identity\Domain\Enums\UserStatus;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;
use Src\Support\Infrastructure\Authorization\CentralRoles;

/**
 * الأدوار مربوطة بالمؤسسة (teams) — فلازم السياق يتضبط **قبل**
 * كل `assignRole()`، وإلا الربط بيتكتب على مؤسسة غلط أو على null.
 */
final class UserSeeder extends Seeder
{
    public function run(): void
    {
        $acme = Tenant::query()->where('slug', 'acme')->firstOrFail();
        $beta = Tenant::query()->where('slug', 'beta')->firstOrFail();

        // فريق التشغيل: دوره مركزي ومش عضو في أي مؤسسة — بيدخل /central،
        // ولوحة المؤسسة بالانتحال بس (docs/24 بند ٢)
        $this->makeUser('مدير عام', 'super@futurecode.test', 'USR-000001', [], 'super_admin');
        $this->makeUser('مدير أكمي', 'admin@futurecode.test', 'USR-000002', [$acme], 'admin');
        $this->makeUser('محرّر أكمي', 'editor@futurecode.test', 'USR-000003', [$acme], 'editor');
        $this->makeUser('مشاهد أكمي', 'viewer@futurecode.test', 'USR-000004', [$acme], 'viewer');
        $this->makeUser('مدير بيتا', 'beta-admin@futurecode.test', 'USR-000005', [$beta], 'admin');
    }

    /** @param  list<Tenant>  $tenants */
    private function makeUser(string $name, string $email, string $code, array $tenants, string $role): void
    {
        $user = User::query()->withTrashed()->where('email', $email)->first() ?? new User;

        $user->forceFill([
            'name' => $name,
            'email' => $email,
            'code' => $code,
            'password' => Hash::make((string) config('seeding.password')),
            'email_verified_at' => now(),
            'status' => UserStatus::Active,
            'is_active' => true,
            'locale' => 'ar',
            'deleted_at' => null,
        ])->save();

        $context = app(TenantContext::class);

        if (CentralRoles::isCentralRole($role)) {
            app(CentralRoles::class)->assign($user, $role);
        }

        foreach (CentralRoles::isCentralRole($role) ? [] : $tenants as $tenant) {
            $user->tenants()->syncWithoutDetaching([$tenant->getKey() => ['joined_at' => now()]]);

            $context->forTenant($tenant->getKey(), static function () use ($user, $role): void {
                $user->unsetRelation('roles');
                $user->syncRoles([$role]);
            });
        }

        $context->set(null);
    }
}
