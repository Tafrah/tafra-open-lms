<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Infrastructure\Tenancy\TenancyMode;
use Src\Support\Infrastructure\Tenancy\TenancyModeLock;

/**
 * قفل وضع تعدد المستأجرين عند أول نشر (docs/03 بند ١).
 *
 * `php artisan tenancy:init --mode=multi`
 *
 * الاسم مش `tenancy:install` — ده أمر stancl بينشر ملفاته.
 */
final class InitTenancyCommand extends Command
{
    protected $signature = 'tenancy:init {--mode= : single أو multi}';

    protected $description = 'قفل وضع تعدد المستأجرين عند أول نشر';

    public function handle(TenancyModeLock $lock): int
    {
        $mode = (string) ($this->option('mode') ?? TenancyMode::current());

        if (! TenancyMode::isValid($mode)) {
            $this->error(__('tenancy::tenancy.init.invalid', ['mode' => $mode]));

            return self::FAILURE;
        }

        // الأمر مايقدرش يقفل وضع غير اللي التطبيق شغّال بيه فعلاً
        if ($mode !== TenancyMode::current()) {
            $this->error(__('tenancy::tenancy.init.env_mismatch', [
                'mode' => $mode,
                'configured' => TenancyMode::current(),
            ]));

            return self::FAILURE;
        }

        if ($lock->isLocked()) {
            $this->info(__('tenancy::tenancy.init.already', ['mode' => (string) $lock->stored()]));

            return self::SUCCESS;
        }

        // قفل وضع مايطابقش الواقع هو بالظبط اللي القفل موجود عشان يمنعه:
        // multi ومؤسسات من غير قواعد، أو single وفيه قواعد مؤسسات
        $inconsistent = $this->inconsistentTenants($mode);

        if ($inconsistent !== []) {
            $this->error(__('tenancy::tenancy.init.inconsistent', [
                'mode' => $mode,
                'slugs' => implode(', ', array_slice($inconsistent, 0, 10)),
            ]));

            return self::FAILURE;
        }

        $lock->lock($mode);

        $this->info(__('tenancy::tenancy.init.locked', ['mode' => $mode]));

        return self::SUCCESS;
    }

    /** @return list<string> */
    private function inconsistentTenants(string $mode): array
    {
        $central = (string) config('tenancy.database.central_connection');

        if (! Schema::connection($central)->hasTable('tenants')) {
            return [];
        }

        $inconsistent = [];

        Tenant::query()->withTrashed()->orderBy('id')->each(function (Tenant $tenant) use ($mode, &$inconsistent): void {
            try {
                $exists = $tenant->database()->manager()->databaseExists($tenant->database()->getName());
            } catch (\Throwable) {
                $exists = false;
            }

            if ($exists !== ($mode === TenancyMode::MULTI)) {
                $inconsistent[] = (string) $tenant->slug;
            }
        });

        return $inconsistent;
    }
}
