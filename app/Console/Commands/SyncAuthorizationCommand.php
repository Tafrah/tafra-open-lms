<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\PermissionRegistrar;
use Src\Contexts\Identity\Domain\Models\Permission;
use Src\Contexts\Identity\Domain\Models\Role;
use Src\Support\Infrastructure\Authorization\PermissionBuilder;

/**
 * ⚠️ الموديلات هنا **بتوعنا** مش بتوع spatie — وده مش تفصيلة.
 *
 * `Permission::findOrCreate()` في spatie بينادي
 * `PermissionRegistrar::setPermissionClass(static::class)` على الـ singleton.
 * لو الأمر استخدم الكلاس الأساسي، الـ registrar بيفضل شايله لباقي العملية —
 * وكل فحص صلاحية بعدها بيستعلم بكلاس مالوش `CentralConnection`. في وضع
 * multi ده معناه استعلام `permissions` على قاعدة المؤسسة و500 على كل صفحة
 * (docs/21 بند ٣٠). بيحصل في أي عملية طويلة بتشغّل الأمر: الاختبارات،
 * queue worker، Octane.
 */
final class SyncAuthorizationCommand extends Command
{
    protected $signature = 'authorization:sync
                            {--prune : حذف الصلاحيات غير الموجودة في الكونفيج}
                            {--dry   : عرض التغييرات من غير تنفيذ}';

    protected $description = 'مزامنة الصلاحيات والأدوار من config/authorization.php';

    public function handle(PermissionBuilder $builder, PermissionRegistrar $registrar): int
    {
        $guard = (string) config('authorization.guard');

        // الأدوار والصلاحيات **عامة** (team_id = null) — الربط بالمستخدم هو
        // اللي بيتحدد بالمستأجر. راجع docs/03-multi-tenancy.md.
        $registrar->setPermissionsTeamId(null);

        $defined = $builder->allPermissionNames();
        $existing = Permission::query()->where('guard_name', $guard)->pluck('name')->all();

        $toCreate = array_values(array_diff($defined, $existing));
        $toPrune = array_values(array_diff($existing, $defined));

        $this->table(
            [__('authorization.sync.operation'), __('authorization.sync.count')],
            [
                [__('authorization.sync.to_create'), count($toCreate)],
                [__('authorization.sync.to_prune'), count($toPrune)],
            ],
        );

        if ($this->option('dry')) {
            foreach ($toCreate as $name) {
                $this->line("  + <info>{$name}</info>");
            }

            foreach ($toPrune as $name) {
                $this->line("  - <comment>{$name}</comment>");
            }

            return self::SUCCESS;
        }

        foreach ($toCreate as $name) {
            Permission::findOrCreate($name, $guard);
        }

        if ($this->option('prune') && $toPrune !== []) {
            Permission::query()->whereIn('name', $toPrune)->where('guard_name', $guard)->delete();
        }

        // أدوار المؤسسة وأدوار فريق التشغيل: التعريف واحد، الإسناد هو اللي بيفرق
        $roles = [...(array) config('authorization.roles', []), ...(array) config('authorization.central_roles', [])];

        foreach ($roles as $roleName => $patterns) {
            $role = Role::findOrCreate((string) $roleName, $guard);
            $role->syncPermissions($builder->expandPatterns((array) $patterns));

            $this->line("  ✔ <info>{$roleName}</info> — ".$role->permissions()->count());
        }

        $registrar->forgetCachedPermissions();

        $this->info(__('authorization.sync.done'));

        return self::SUCCESS;
    }
}
