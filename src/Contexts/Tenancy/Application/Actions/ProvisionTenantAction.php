<?php

declare(strict_types=1);

namespace Src\Contexts\Tenancy\Application\Actions;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;
use Src\Support\Infrastructure\Tenancy\TenancyMode;
use Throwable;

/**
 * الطريق الوحيد لإنشاء مؤسسة من اللوحة (docs/24 بند ٣).
 *
 * - single: الصف ← البيانات الافتراضية بسياق المؤسسة.
 * - multi: الصف بس — `TenantCreated` بيطلق السلسلة (قاعدة ← تهجير ←
 *   بيانات افتراضية ← `ready`)، في الطابور لو `TENANCY_PROVISION_QUEUED`.
 *
 * ⚠️ مفيش `DB::transaction` هنا عن قصد: Postgres بيرفض `CREATE DATABASE`
 * جوه transaction.
 */
final readonly class ProvisionTenantAction
{
    public function __construct(private TenantContext $context) {}

    /** @param array<string, mixed> $attributes */
    public function handle(array $attributes, ?User $actor = null): Tenant
    {
        if ($actor !== null) {
            Gate::forUser($actor)->authorize('create', Tenant::class);
        }

        $tenant = new Tenant;

        try {
            $tenant->fill($attributes)->save();
        } catch (Throwable $exception) {
            // السلسلة بتشتغل في `created` بعد الإدخال — من غير كده الصف بيفضل `pending` للأبد
            if ($tenant->exists) {
                $tenant->forceFill(['provisioning_status' => Tenant::PROVISIONING_FAILED])->saveQuietly();
            }

            throw $exception;
        }

        if (! TenancyMode::isMulti()) {
            $this->context->forTenant((int) $tenant->getKey(), static function (): void {
                Artisan::call('db:seed', [
                    '--class' => (string) config('tenancy.seeder_parameters.--class'),
                    '--force' => true,
                ]);
            });
        }

        activity('tenancy')
            ->causedBy($actor)
            ->performedOn($tenant)
            ->withProperties(['mode' => TenancyMode::current()])
            ->log('tenant_provisioned');

        return $tenant->refresh();
    }
}
