<?php

declare(strict_types=1);

namespace Src\Contexts\Demo\Presentation\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Src\Contexts\Demo\Application\Actions\SeedDemoAcademyAction;
use Src\Contexts\Demo\Application\DemoMode;
use Src\Contexts\Installer\Application\Actions\InstallApplicationAction;
use Src\Contexts\Installer\Application\DataObjects\AcademyData;
use Src\Contexts\Installer\Application\DataObjects\InstallationData;
use Src\Contexts\Installer\Infrastructure\InstallationState;
use Src\Support\Infrastructure\Tenancy\TenancyModeLock;
use Throwable;

/**
 * نسخة تجربة جاهزة: تثبيت + بيانات + حسابات بتدخلها بضغطة.
 *
 *   DEMO_MODE=true php artisan lms:demo            # أول مرة (قاعدة فاضية)
 *   DEMO_MODE=true php artisan lms:demo --fresh    # امسح كل حاجة وابدأ من الأول
 *
 * بيرفض يشتغل من غير `DEMO_MODE=true`، وبيرفض يلمس منصة متثبّتة «بجد»
 * (مش بـ lms:demo) — `--fresh` بيمسح القاعدة كلها.
 */
final class DemoCommand extends Command
{
    protected $signature = 'lms:demo
        {--fresh : امسح القاعدة وابني نسخة التجربة من الأول}
        {--if-not-installed : اخرج بنجاح لو نسخة التجربة متثبّتة}';

    protected $description = 'نسخة تجربة: بيانات أكاديمية جاهزة وحسابات بتدخلها بضغطة';

    public function handle(DemoMode $demo, InstallationState $state, InstallApplicationAction $install, SeedDemoAcademyAction $seed, TenancyModeLock $lock): int
    {
        if (! $demo->enabled()) {
            $this->components->error(__('demo::demo.cli.disabled'));

            return self::FAILURE;
        }

        if ($state->isInstalled() && ! $demo->installedAsDemo()) {
            $this->components->error(__('demo::demo.cli.real_install'));

            return self::FAILURE;
        }

        if ($state->isInstalled() && ! $this->option('fresh')) {
            if ($this->option('if-not-installed')) {
                $this->components->info(__('demo::demo.cli.already'));

                return self::SUCCESS;
            }

            $this->components->error(__('demo::demo.cli.already_hint'));

            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            $this->components->task(__('demo::demo.cli.wiping'), function () use ($state, $lock): void {
                Artisan::call('migrate:fresh', ['--force' => true]);
                File::delete($state->markerPath());
                $state->forget();
                $lock->forget();
            });
        }

        $teacher = $demo->account('teacher') ?? [];

        try {
            $install->handle(new InstallationData(
                academy: AcademyData::fromArray([
                    ...(array) config('demo.academy'),
                    'admin_email' => $teacher['email'] ?? 'teacher@demo.test',
                    'admin_phone' => null,
                    'admin_password' => $demo->password(),
                ]),
                appUrl: (string) config('app.url'),
                database: null,
                writeEnvironment: false,
                secureCookies: str_starts_with((string) config('app.url'), 'https://'),
            ), function (string $step): void {
                $this->components->info(__('installer::installer.steps.'.$step));
            });

            // قبل البيانات: لو البذر وقع في النص، `--fresh` لسه مسموح (مش «تثبيت حقيقي»)
            $demo->markInstalledAsDemo();

            $this->components->task(__('demo::demo.cli.seeding'), static fn () => $seed->handle());
        } catch (Throwable $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->components->info(__('demo::demo.cli.done'));

        foreach ($demo->accounts() as $key => $account) {
            $this->components->twoColumnDetail(
                __("demo::demo.accounts.{$key}.label"),
                ($account['email'] ?? $account['phone'] ?? '').' / '.$demo->password(),
            );
        }

        $this->components->twoColumnDetail(__('demo::demo.cli.admin_url'), rtrim((string) config('app.url'), '/').'/admin/login');
        $this->components->twoColumnDetail(__('demo::demo.cli.site_url'), rtrim((string) config('app.url'), '/').'/login');

        return self::SUCCESS;
    }
}
