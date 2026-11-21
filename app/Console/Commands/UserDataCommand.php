<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Src\Contexts\Identity\Application\Actions\ExportUserDataAction;
use Src\Contexts\Identity\Application\Actions\ForgetUserAction;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Support\Application\Contracts\TenantContext;

/**
 * حق التصدير وحق الحذف (docs/20 بند ١٣).
 *
 *     php artisan user:data 42 --export=storage/app/private/user-42.json
 *     php artisan user:data 42 --forget
 *
 * أمر CLI مش شاشة عن قصد: العمليتين دول بيتنفّذوا رداً على طلب رسمي،
 * مش من حد بيدوس زرار وهو بيتصفّح. والحذف بيتأكّد قبل ما ينفّذ.
 */
final class UserDataCommand extends Command
{
    protected $signature = 'user:data
                            {user : معرّف المستخدم أو بريده}
                            {--export= : مسار ملف JSON للتصدير}
                            {--forget : حذف بيانات المستخدم نهائياً}';

    protected $description = 'تصدير أو حذف كل بيانات مستخدم';

    public function handle(TenantContext $context): int
    {
        $user = $context->withoutScope(fn (): ?User => User::withTrashed()
            ->where('id', $this->argument('user'))
            ->orWhere('email', $this->argument('user'))
            ->first());

        if ($user === null) {
            $this->error('المستخدم مش موجود.');

            return self::FAILURE;
        }

        if (! $this->option('export') && ! $this->option('forget')) {
            $this->error('اختر --export أو --forget.');

            return self::FAILURE;
        }

        if ($this->option('export')) {
            $this->export($user, (string) $this->option('export'));
        }

        if ($this->option('forget')) {
            return $this->forget($user);
        }

        return self::SUCCESS;
    }

    private function export(User $user, string $path): void
    {
        $data = app(ExportUserDataAction::class)->handle($user);

        file_put_contents(
            $path,
            (string) json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        );

        $this->info("اتصدّرت بيانات المستخدم {$user->getKey()} في {$path}");
    }

    private function forget(User $user): int
    {
        $this->warn("هيتحذف نهائياً: #{$user->getKey()} — {$user->email}");
        $this->line('سجل التدقيق هيتجهّل مش هيتحذف.');

        if ($this->input->isInteractive() && ! $this->confirm('أكمّل؟', false)) {
            $this->line('اتلغى.');

            return self::FAILURE;
        }

        $affected = app(ForgetUserAction::class)->handle($user);

        $this->table(
            ['الجدول', 'صفوف'],
            collect($affected)->map(static fn (int $count, string $table): array => [$table, $count])->all(),
        );

        return self::SUCCESS;
    }
}
