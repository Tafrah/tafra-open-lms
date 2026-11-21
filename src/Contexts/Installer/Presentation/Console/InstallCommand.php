<?php

declare(strict_types=1);

namespace Src\Contexts\Installer\Presentation\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Src\Contexts\Installer\Application\Actions\CheckRequirementsAction;
use Src\Contexts\Installer\Application\Actions\InstallApplicationAction;
use Src\Contexts\Installer\Application\DataObjects\AcademyData;
use Src\Contexts\Installer\Application\DataObjects\DatabaseCredentials;
use Src\Contexts\Installer\Application\DataObjects\InstallationData;
use Src\Contexts\Installer\Domain\Exceptions\AlreadyInstalledException;
use Src\Contexts\Installer\Domain\Exceptions\DatabaseConnectionFailed;
use Src\Contexts\Installer\Domain\Exceptions\InstallationFailed;
use Src\Contexts\Installer\Infrastructure\InstallationState;

/**
 * نفس خطوات `/install` من غير واجهة — VPS، Docker، Coolify.
 *
 *   php artisan lms:install --no-interaction \
 *       --db-host=127.0.0.1 --db-database=lms --db-username=lms --db-password=... \
 *       --url=https://academy.example.com \
 *       --platform-name="أكاديمية النجاح" --teacher-name="أ. محمد" --subject="فيزياء" \
 *       --admin-email=teacher@example.com --admin-password=...
 *
 * `--skip-env`: القيم جاية من متغيّرات البيئة (حاوية) — مفيش `.env` يتكتب
 * والاتصال الحالي هو اللي بيتستخدم. `--if-not-installed` بيخرج بنجاح لو
 * متثبّت (entrypoint بيشغّله مع كل تشغيل).
 */
final class InstallCommand extends Command
{
    protected $signature = 'lms:install
        {--db-driver=mysql : mysql أو pgsql}
        {--db-host= : عنوان سيرفر القاعدة}
        {--db-port= : المنفذ}
        {--db-database= : اسم القاعدة}
        {--db-username= : المستخدم}
        {--db-password= : كلمة السر}
        {--skip-env : استخدم متغيّرات البيئة الحالية ومتكتبش .env}
        {--url= : رابط المنصة (APP_URL)}
        {--platform-name= : اسم المنصة}
        {--teacher-name= : اسم المدرّس}
        {--subject= : المادة}
        {--admin-email= : بريد المدير}
        {--admin-phone= : موبايل المدير}
        {--admin-password= : كلمة سر المدير}
        {--if-not-installed : اخرج بنجاح لو المنصة متثبّتة}';

    protected $description = 'تثبيت المنصة من غير واجهة (VPS / Docker / Coolify)';

    public function handle(InstallationState $state, CheckRequirementsAction $requirements, InstallApplicationAction $install): int
    {
        if ($state->isInstalled()) {
            if ($this->option('if-not-installed')) {
                $this->components->info(__('installer::installer.cli.already_installed'));

                return self::SUCCESS;
            }

            $this->components->error(__('installer::installer.errors.already_installed'));

            return self::FAILURE;
        }

        $skipEnv = (bool) $this->option('skip-env');

        $report = $requirements->handle(checkEnvironmentFile: ! $skipEnv);

        if (! $report->passes()) {
            foreach ($report->failures() as $failure) {
                $this->components->error($failure->label.' — '.$failure->hint);
            }

            return self::FAILURE;
        }

        $academyInput = $this->academyInput();
        $databaseInput = $skipEnv ? [] : $this->databaseInput();

        $rules = [...AcademyData::rules(confirmPassword: false, headless: true), ...($skipEnv ? [] : DatabaseCredentials::rules())];
        $validator = Validator::make([...$academyInput, ...$databaseInput], $rules, [], [
            ...(array) __('installer::installer.academy.fields'),
            ...(array) __('installer::installer.database.fields'),
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->components->error($message);
            }

            return self::FAILURE;
        }

        $data = new InstallationData(
            academy: AcademyData::fromArray($academyInput),
            appUrl: (string) ($this->option('url') ?: config('app.url')),
            database: $skipEnv ? null : DatabaseCredentials::fromArray($databaseInput),
            writeEnvironment: ! $skipEnv,
            secureCookies: str_starts_with((string) ($this->option('url') ?: config('app.url')), 'https://'),
        );

        try {
            $result = $install->handle($data, function (string $step): void {
                $this->components->info(__('installer::installer.steps.'.$step));
            });
        } catch (AlreadyInstalledException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        } catch (DatabaseConnectionFailed|InstallationFailed $exception) {
            $this->components->error($exception->getMessage());

            if ($exception->details !== '') {
                $this->line($exception->details);
            }

            return self::FAILURE;
        }

        $this->components->info(__('installer::installer.cli.done', ['email' => $data->academy->adminEmail]));
        $this->components->twoColumnDetail(__('installer::installer.finished.admin'), rtrim($data->appUrl, '/').'/admin');
        $this->components->twoColumnDetail(__('installer::installer.finished.media'), __('installer::installer.finished.media_modes.'.$result->mediaServing->value));
        $this->components->twoColumnDetail(__('installer::installer.finished.cron'), '* * * * * php '.base_path('artisan').' schedule:run >> /dev/null 2>&1');

        return self::SUCCESS;
    }

    /** @return array<string, string|null> */
    private function academyInput(): array
    {
        return [
            'platform_name' => $this->value('platform-name', 'academy.fields.platform_name'),
            'teacher_name' => $this->value('teacher-name', 'academy.fields.teacher_name'),
            'subject' => $this->value('subject', 'academy.fields.subject'),
            'admin_email' => $this->value('admin-email', 'academy.fields.admin_email'),
            'admin_phone' => $this->value('admin-phone', 'academy.fields.admin_phone', optional: true),
            'admin_password' => $this->value('admin-password', 'academy.fields.admin_password', secret: true),
        ];
    }

    /** @return array<string, string|null> */
    private function databaseInput(): array
    {
        $driver = (string) $this->option('db-driver');

        return [
            'db_driver' => $driver,
            'db_host' => $this->value('db-host', 'database.fields.db_host', default: '127.0.0.1'),
            'db_port' => $this->value('db-port', 'database.fields.db_port', default: (string) config("installer.database.default_ports.{$driver}", 3306)),
            'db_database' => $this->value('db-database', 'database.fields.db_database'),
            'db_username' => $this->value('db-username', 'database.fields.db_username'),
            'db_password' => $this->value('db-password', 'database.fields.db_password', secret: true, optional: true),
        ];
    }

    /**
     * الخيار لو اتبعت، وإلا سؤال (لو الطرفية تفاعلية)، وإلا القيمة الافتراضية.
     * `--no-interaction` من غير الخيارات المطلوبة = أخطاء تحقق واضحة.
     */
    private function value(string $option, string $label, bool $secret = false, bool $optional = false, ?string $default = null): ?string
    {
        $value = $this->option($option);

        if (is_string($value) && $value !== '') {
            return $value;
        }

        if (! $this->input->isInteractive() || ($optional && ! $secret)) {
            return $default;
        }

        $question = (string) __('installer::installer.'.$label);

        $answer = $secret ? $this->secret($question) : $this->ask($question, $default);

        return is_string($answer) && $answer !== '' ? $answer : $default;
    }
}
