<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

/*
|--------------------------------------------------------------------------
| شروط القبول الأمنية كاختبارات آلية (docs/20 بند ١٧)
|--------------------------------------------------------------------------
*/

it('لا يوجد $guarded فاضي', function (): void {
    // لازم تعريف خاصية فعلي — مش ذكر `$guarded = []` في تعليق
    expect(grepAll('/protected\s+(?:static\s+)?\$guarded\s*=\s*\[\s*\]/', [src_path(), app_path()]))->toBeEmpty();
});

it('كل موديل دومين له $fillable صريح', function (): void {
    $violations = [];

    foreach (allDomainModels() as $model) {
        /** @var Model $instance */
        $instance = new $model;

        // الموديلات اللي بتورث باكدج خارجي (Role/Permission/Media/Activity)
        // بتاخد fillable من الباكدج — بنتخطاها بوعي.
        if (! str_starts_with((new ReflectionClass($model))->getParentClass()->getName(), 'Illuminate\\')) {
            continue;
        }

        if ($instance->getFillable() === []) {
            $violations[] = $model;
        }
    }

    expect($violations)->toBeEmpty('موديلات من غير $fillable: '.implode(', ', $violations));
});

it('لا يوجد whereRaw أو orderByRaw بمتغيّر مباشر', function (): void {
    $violations = grepAll('/(whereRaw|orderByRaw|havingRaw)\([^)]*\$(?!query)/', [src_path(), app_path()]);

    expect($violations)->toBeEmpty(
        "استعلام خام بمدخل متغيّر — لازم قائمة بيضاء:\n".implode("\n", $violations),
    );
});

it('لا يوجد {!! !!} على محتوى غير منقّى', function (): void {
    $violations = [];

    foreach (File::allFiles(resource_path('views')) as $file) {
        if (! str_ends_with($file->getFilename(), '.blade.php')) {
            continue;
        }

        if (! preg_match_all('/\{!!(.+?)!!\}/s', $file->getContents(), $found)) {
            continue;
        }

        foreach ($found[1] as $expression) {
            // مسموح: ناتج مكوّن Filament، أو خرج HtmlSanitizer صراحةً
            $isSafe = str_contains($expression, 'HtmlSanitizer')
                || str_contains($expression, '->render()')
                || str_contains($expression, 'qrCodeSvg')
                || str_contains($expression, '$scale');

            if (! $isSafe) {
                $violations[] = "{$file->getRelativePathname()}: {!!{$expression}!!}";
            }
        }
    }

    expect($violations)->toBeEmpty("عرض خام غير منقّى:\n".implode("\n", $violations));
});

it('كل قناة بث خاصة', function (): void {
    $channels = (string) file_get_contents(base_path('routes/channels.php'));

    expect($channels)->not->toContain('=> true')
        ->and($channels)->toContain('Broadcast::channel');
});

it('لا يوجد سر مكتوب في الكود', function (): void {
    $patterns = [
        '/[\'"]?(AWS_SECRET_ACCESS_KEY|APP_KEY)[\'"]?\s*=>\s*[\'"][A-Za-z0-9\/+=]{16,}[\'"]/',
        '/[\'"]sk_live_[A-Za-z0-9]+[\'"]/',
        '/-----BEGIN (RSA |EC )?PRIVATE KEY-----/',
    ];

    $violations = [];

    foreach ($patterns as $pattern) {
        $violations = [...$violations, ...grepAll($pattern, [src_path(), app_path(), config_path(), database_path()])];
    }

    expect($violations)->toBeEmpty("أسرار في الكود:\n".implode("\n", $violations));
});

it('لا يوجد قرص تخزين مكتوب مباشرة خارج الكونفيج والـ DiskResolver', function (): void {
    $violations = [];

    foreach (allPhpFiles([src_path(), app_path()]) as $file) {
        if (str_contains($file->getPathname(), 'SettingsDrivenDiskResolver')
            || str_contains($file->getPathname(), 'MoveMediaCommand')) {
            continue;
        }

        if (preg_match("/Storage::disk\(\s*'(s3|local|public)'/", phpCodeWithoutComments($file->getContents()), $found)) {
            $violations[] = "{$file->getRelativePathname()}: {$found[0]}";
        }
    }

    expect($violations)->toBeEmpty("ديسك مكتوب مباشرة:\n".implode("\n", $violations));
});

/**
 * جدول المصائد (docs/12 بند ٧) — المصيدة رقم ٤.
 *
 * `->authorize()` على إجراء جماعي **بيعدّل** بيفحص القدرة مرة واحدة على
 * المورد، فلو المستخدم يقدر يحذف سجل واحد بيحذف الخمسين المحددين كلهم.
 * `authorizeIndividualRecords()` بتفلتر السجلات بالـ Policy واحد واحد.
 *
 * التصدير مستثنى عن قصد: `export` قدرة على المورد مش على السجل،
 * والمُصدِّر بيتفحص بـ `->authorize('export')` زي `docs/08` بند ٦.
 */
it('كل إجراء جماعي معدِّل بيفحص كل سجل على حدة', function (): void {
    $exempt = ['ExportBulkAction'];
    $violations = [];

    foreach (allPhpFiles([src_path()]) as $file) {
        $contents = phpCodeWithoutComments($file->getContents());

        preg_match_all('/\b(\w*BulkAction)::make\(/', $contents, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[1] as $index => [$class, $_]) {
            if ($class === 'BulkActionGroup' || in_array($class, $exempt, true)) {
                continue;
            }

            $start = $matches[0][$index][1];
            $chain = substr($contents, $start, 900);

            if (preg_match('/\b\w*BulkAction::make\(/', substr($chain, 20), $next, PREG_OFFSET_CAPTURE)) {
                $chain = substr($chain, 0, $next[0][1] + 20);
            }

            if (! str_contains($chain, 'authorizeIndividualRecords')) {
                $violations[] = "{$file->getRelativePathname()}: {$class}";
            }
        }
    }

    expect($violations)->toBeEmpty(
        "إجراءات جماعية من غير authorizeIndividualRecords():\n".implode("\n", $violations),
    );
});

/**
 * جدول المصائد (docs/12 بند ٧) — المصيدة رقم ٩.
 *
 * رفع من غير قائمة MIME بيضاء = أخطر مدخل في التطبيق (docs/20 بند ٧).
 * `->image()` مقبولة لأنها بتقيّد على أنواع الصور.
 */
it('كل حقل رفع عليه قائمة MIME بيضاء', function (): void {
    $violations = [];

    foreach (allPhpFiles([src_path(), app_path()]) as $file) {
        $contents = phpCodeWithoutComments($file->getContents());

        preg_match_all('/\b(SpatieMediaLibraryFileUpload|FileUpload)::make\(/', $contents, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[0] as $index => [$_, $start]) {
            $chain = substr($contents, $start, 1400);

            if (preg_match('/\b(SpatieMediaLibraryFileUpload|FileUpload)::make\(/', substr($chain, 20), $next, PREG_OFFSET_CAPTURE)) {
                $chain = substr($chain, 0, $next[0][1] + 20);
            }

            $guarded = str_contains($chain, 'acceptedFileTypes')
                || str_contains($chain, 'acceptsMimeTypes')
                || str_contains($chain, '->image()');

            if (! $guarded) {
                $violations[] = "{$file->getRelativePathname()}: {$matches[1][$index][0]}";
            }
        }
    }

    expect($violations)->toBeEmpty("حقول رفع من غير قائمة MIME:\n".implode("\n", $violations));
});
