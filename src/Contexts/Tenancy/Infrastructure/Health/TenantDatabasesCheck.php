<?php

declare(strict_types=1);

namespace Src\Contexts\Tenancy\Infrastructure\Health;

use Illuminate\Support\Facades\DB;
use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Infrastructure\Tenancy\TenancyMode;

/**
 * كل مؤسسة نشطة قاعدتها موجودة ومتهجّرة لآخر إصدار (وضع multi).
 *
 * `deploy.sh` بيشغّل `tenants:migrate` — لو فشل لمؤسسة واحدة، النشر بيكمّل
 * للباقي والمؤسسة دي بتفتح على سكيما قديمة. الفحص بيقارن عدد صفوف
 * `migrations` في قاعدتها بعدد ملفات ميجريشنز المستأجرين.
 */
final class TenantDatabasesCheck extends Check
{
    public function run(): Result
    {
        if (! TenancyMode::isMulti()) {
            return Result::make()
                ->shortSummary(TenancyMode::current())
                ->ok(__('tenancy::tenancy.health.databases.not_applicable'));
        }

        $expected = self::expectedMigrations();
        $broken = [];
        $checked = 0;

        Tenant::query()->active()->orderBy('id')->each(function (Tenant $tenant) use ($expected, &$broken, &$checked): void {
            $checked++;

            try {
                $applied = (int) $tenant->run(static fn (): int => DB::table('migrations')->count());
            } catch (\Throwable) {
                $applied = -1;
            } finally {
                // `run()` مفيهوش finally — مؤسسة بايظة كانت بتسيب السياق عليها لباقي الفحوصات
                tenancy()->end();
            }

            if ($applied < $expected) {
                $broken[] = $tenant->slug;
            }
        });

        $result = Result::make()
            ->meta(['checked' => $checked, 'expected_migrations' => $expected, 'broken' => $broken])
            ->shortSummary(($checked - count($broken)).'/'.$checked);

        if ($broken !== []) {
            return $result->failed(__('tenancy::tenancy.health.databases.failed', [
                'count' => count($broken),
                'slugs' => implode('، ', array_slice($broken, 0, 10)),
            ]));
        }

        return $result->ok(__('tenancy::tenancy.health.databases.ok', ['count' => $checked]));
    }

    private static function expectedMigrations(): int
    {
        $files = [];

        // بالاسم مش بالعدد: الـ Migrator بيشيل التكرار بالاسم، فالمتوقّع لازم يعمل زيه
        foreach (array_unique((array) config('tenancy.migration_parameters.--path', [])) as $path) {
            foreach (glob(rtrim((string) $path, '/').'/*.php') ?: [] as $file) {
                $files[basename($file)] = true;
            }
        }

        return count($files);
    }
}
