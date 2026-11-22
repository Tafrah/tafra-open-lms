<?php

declare(strict_types=1);

namespace Src\Contexts\Installer\Application\Actions;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Src\Contexts\Installer\Infrastructure\InstallationState;
use Throwable;

/**
 * تطبيق تحديث بعد رفع ملفات إصدار جديد — من غير SSH.
 *
 * على الاستضافة المشتركة المدرس بيرفع الـ zip الجديد فوق القديم، ومفيش حد
 * يشغّل `php artisan migrate`. فأول طلب بعد ما رقم الإصدار (`VERSION`)
 * يختلف عن اللي في `installed.json` بيطبّق الميجريشنز ويزامن الصلاحيات
 * ويمسح كاش الواجهات، وبعدين يسجّل الإصدار الجديد.
 *
 * قفل ملف (مش كاش القاعدة — جدول الكاش نفسه ممكن يكون من الميجريشنز
 * الجديدة): طلب واحد بس بيحدّث، والباقيين بياخدوا «جاري التحديث».
 */
final readonly class ApplyUpdateAction
{
    public const UPDATED = 'updated';

    public const BUSY = 'busy';

    public const NOT_NEEDED = 'not_needed';

    public function __construct(private InstallationState $state) {}

    public function needed(): bool
    {
        if (! $this->state->isInstalled()) {
            return false;
        }

        $installed = $this->state->marker()['version'] ?? null;

        return is_string($installed) && $installed !== $this->state->version();
    }

    /** @return self::UPDATED|self::BUSY|self::NOT_NEEDED */
    public function handle(): string
    {
        $lockPath = storage_path('framework/update.lock');
        $handle = @fopen($lockPath, 'c');

        if ($handle === false) {
            throw new \RuntimeException("Cannot open update lock at {$lockPath}");
        }

        if (! flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);

            return self::BUSY;
        }

        try {
            // طلب تاني ممكن يكون خلّص التحديث وإحنا مستنيين القفل
            if (! $this->needed()) {
                return self::NOT_NEEDED;
            }

            $from = (string) ($this->state->marker()['version'] ?? '');
            $to = $this->state->version();

            Artisan::call('migrate', ['--force' => true]);
            Artisan::call('authorization:sync');
            Artisan::call('view:clear');

            $this->state->markInstalled([
                ...($this->state->marker() ?? []),
                'version' => $to,
                'updated_at' => now()->toIso8601String(),
                'updated_from' => $from,
            ]);

            Log::info('lms.updated', ['from' => $from, 'to' => $to]);

            return self::UPDATED;
        } catch (Throwable $e) {
            Log::error('lms.update_failed', ['exception' => $e]);

            throw $e;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
