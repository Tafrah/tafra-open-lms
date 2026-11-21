<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Src\Contexts\Media\Domain\Models\MediaItem;

/**
 * نقل الوسائط بين الديسكات على دفعات، مع تحقق من الـ checksum
 * قبل تحديث `disk_name` — ومفيش حذف من المصدر إلا بطلب صريح
 * (docs/04 بند ٩).
 */
final class MoveMediaCommand extends Command
{
    protected $signature = 'media:move
                            {--from= : الديسك المصدر}
                            {--to= : الديسك الهدف}
                            {--collection= : مجموعة واحدة بس}
                            {--tenant= : مؤسسة واحدة بس}
                            {--chunk=100 : حجم الدفعة}
                            {--delete-source : حذف الملف من المصدر بعد التأكد}
                            {--dry : عرض بدون تنفيذ}';

    protected $description = 'نقل ملفات الوسائط من ديسك لديسك';

    public function handle(): int
    {
        $from = (string) $this->option('from');
        $to = (string) $this->option('to');

        if ($from === '' || $to === '') {
            $this->error('لازم تحدد --from و --to.');

            return self::FAILURE;
        }

        foreach ([$from, $to] as $disk) {
            if (! array_key_exists($disk, (array) config('filesystems.disks'))) {
                $this->error("الديسك [{$disk}] مش معرّف في config/filesystems.php.");

                return self::FAILURE;
            }
        }

        $query = MediaItem::query()->where('disk', $from);

        if (filled($collection = $this->option('collection'))) {
            $query->where('collection_name', $collection);
        }

        if (filled($tenant = $this->option('tenant'))) {
            $query->where('custom_properties->tenant_id', (int) $tenant);
        }

        $total = (clone $query)->count();
        $this->info("هينتقل {$total} ملف من [{$from}] لـ [{$to}].");

        if ($this->option('dry')) {
            return self::SUCCESS;
        }

        $moved = 0;
        $failed = 0;
        $bar = $this->output->createProgressBar($total);

        $query->chunkById((int) $this->option('chunk'), function ($items) use ($from, $to, &$moved, &$failed, $bar): void {
            foreach ($items as $media) {
                $path = $media->getPathRelativeToRoot();

                try {
                    $contents = Storage::disk($from)->get($path);

                    Storage::disk($to)->put($path, $contents);

                    // التحقق قبل تحديث السجل — نسخ ناقص أسوأ من عدم النقل
                    if (md5((string) Storage::disk($to)->get($path)) !== md5((string) $contents)) {
                        throw new \RuntimeException("checksum mismatch: {$path}");
                    }

                    $media->forceFill(['disk' => $to])->saveQuietly();

                    if ($this->option('delete-source')) {
                        Storage::disk($from)->delete($path);
                    }

                    $moved++;
                } catch (\Throwable $exception) {
                    report($exception);
                    $failed++;
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("اتنقل {$moved} ملف · فشل {$failed}.");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
