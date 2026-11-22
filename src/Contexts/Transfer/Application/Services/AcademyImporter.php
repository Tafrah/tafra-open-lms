<?php

declare(strict_types=1);

namespace Src\Contexts\Transfer\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Src\Contexts\Academy\Domain\Enums\LessonType;
use Src\Contexts\Settings\Application\Services\TenantSettings;
use Src\Contexts\Students\Application\Actions\RegisterStudentAction;
use Src\Contexts\Transfer\Application\Actions\ExportAcademyAction;
use Src\Contexts\Transfer\Application\DataObjects\ImportReport;
use Src\Contexts\Transfer\Application\Schema\TafraSchemaMap;
use Src\Contexts\Transfer\Domain\Exceptions\TransferException;
use Src\Support\Application\Contracts\DiskResolver;
use Src\Support\Application\Contracts\TenantContext;
use Throwable;
use ZipArchive;

/**
 * يستورد ملف تصدير (من المنصة المجانية أو من طفرة) في الأكاديمية الحالية.
 *
 * - الـ IDs بتتعاد ترقيمها: كل جدول بيتسجّل فيه «القديم ← الجديد» والمفاتيح الأجنبية بتترجم.
 * - مستخدمي اللوحة مابيتستوردوش (حسابات دخول) — أي إشارة ليهم بتروح للي بيستورد.
 * - طالب برقم موبايل موجود = نفس الطالب (دمج)، مش نسخة تانية.
 * - التجربة (dry run) = استيراد كامل جوه transaction بيترجع في الآخر — التقرير حقيقي.
 *
 * كلاس بحالة (خرايط الـ IDs) — نسخة جديدة لكل استيراد، من ImportAcademyAction.
 */
final class AcademyImporter
{
    private const SUPPORTED_LESSON_TYPES = ['YoutubeVideo', 'Pdf', 'Text'];

    /** @var array<string, array<int|string, int>> */
    private array $map = [];

    /** @var array<string, list<string>> */
    private array $columns = [];

    /** @var array<int, array<string, mixed>> users.ndjson للطلبة بس، بالـ id */
    private array $studentUsers = [];

    /** @var array<int, int> سؤال ← امتحان (من بلوكات طفرة) */
    private array $questionExam = [];

    /** @var array<int, float> رصيد الطلبة الجداد من extra_info */
    private array $balances = [];

    /** @var list<int> الطلبة اللي اتضافوا فعلًا (مش اللي اتدمجوا) — رصيدهم بس اللي بيتحسب */
    private array $newStudents = [];

    private int $tenantId = 0;

    private ?int $actorId = null;

    public function __construct(
        private readonly TenantContext $context,
        private readonly TenantSettings $settings,
        private readonly DiskResolver $disks,
    ) {}

    public function run(string $zipPath, bool $dryRun = false, ?int $actorId = null): ImportReport
    {
        $tenantId = $this->context->id();
        throw_if($tenantId === null, TransferException::because('no_academy'));
        throw_unless(class_exists(ZipArchive::class), TransferException::because('zip_missing'));

        $this->reset((int) $tenantId, $actorId);
        $work = storage_path('app/transfer/import-'.Str::uuid());
        $report = new ImportReport($dryRun);

        try {
            $this->extract($zipPath, $work);
            $manifest = $this->manifest($work);
            $fingerprint = hash_file('sha256', $work.'/manifest.json') ?: '';

            throw_if(
                DB::table('academy_imports')->where('tenant_id', $this->tenantId)->where('manifest_hash', $fingerprint)->exists(),
                TransferException::because('already_imported'),
            );

            DB::beginTransaction();

            try {
                DB::table('academy_imports')->insert([
                    'tenant_id' => $this->tenantId,
                    'manifest_hash' => $fingerprint,
                    'source' => mb_substr((string) ($manifest['source'] ?? ''), 0, 64),
                    'exported_at' => isset($manifest['exported_at']) ? date('Y-m-d H:i:s', (int) strtotime((string) $manifest['exported_at'])) : null,
                    'imported_by' => $this->actorId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->loadLookups($work);

                foreach (TafraSchemaMap::tables() as $file => $spec) {
                    $this->importFile($work, $file, $spec['source'], $spec['fk'], $report);
                }

                $this->recalculateBalances();
                $this->importSettings($work);

                if ($dryRun) {
                    DB::rollBack();
                } else {
                    DB::commit();
                    $report->media = $this->importMedia($work, $report);
                }
            } catch (Throwable $e) {
                DB::rollBack();

                throw $e;
            }

            $report->warnings = [...$this->manifestWarnings($manifest), ...$report->warnings];
        } finally {
            File::deleteDirectory($work);
        }

        return $report;
    }

    private function reset(int $tenantId, ?int $actorId): void
    {
        $this->tenantId = $tenantId;
        $this->actorId = $actorId;
        $this->map = [];
        $this->studentUsers = [];
        $this->questionExam = [];
        $this->balances = [];
        $this->newStudents = [];
    }

    private function extract(string $zipPath, string $work): void
    {
        throw_unless(is_file($zipPath), TransferException::because('file_missing'));

        $zip = new ZipArchive;
        throw_unless($zip->open($zipPath) === true, TransferException::because('not_a_zip'));

        // حماية من zip-slip: مفيش مسار مطلق ولا ".."
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);

            if (str_starts_with($name, '/') || str_contains($name, '..') || str_contains($name, '\\')) {
                $zip->close();

                throw TransferException::because('unsafe_zip');
            }
        }

        File::ensureDirectoryExists($work);
        $zip->extractTo($work);
        $zip->close();
    }

    /** @return array<string, mixed> */
    private function manifest(string $work): array
    {
        $path = $work.'/manifest.json';
        throw_unless(is_file($path), TransferException::because('no_manifest'));

        $manifest = json_decode((string) file_get_contents($path), true);
        throw_unless(is_array($manifest) && ($manifest['format'] ?? null) === TafraSchemaMap::FORMAT, TransferException::because('wrong_format'));
        throw_if(
            (int) ($manifest['format_version'] ?? 0) > TafraSchemaMap::FORMAT_VERSION,
            TransferException::because('newer_format', ['version' => (int) $manifest['format_version']]),
        );

        foreach ((array) ($manifest['checksums'] ?? []) as $relative => $hash) {
            $file = $work.'/'.ltrim((string) $relative, '/');
            throw_unless(is_file($file) && hash_equals((string) $hash, (string) hash_file('sha256', $file)), TransferException::because('checksum', ['file' => (string) $relative]));
        }

        return $manifest;
    }

    /**
     * @param array<string, mixed> $manifest
     * @return list<string>
     */
    private function manifestWarnings(array $manifest): array
    {
        $known = [...array_keys(TafraSchemaMap::tables()), 'users', 'educational_categories', 'materials', 'material_parts',
            'bootcamp_categories', 'exam_categories', 'online_exam_blocks', 'online_exam_block_items', 'multiple_choices', 'media_index'];

        return collect(array_keys((array) ($manifest['counts'] ?? [])))
            ->reject(fn (string $table): bool => in_array($table, $known, true))
            ->map(fn (string $table): string => __('transfer::transfer.warnings.unsupported_table', ['table' => $table]))
            ->values()
            ->all();
    }

    /** الملفات المساعدة: الطلبة في users، والأسئلة في البلوكات */
    private function loadLookups(string $work): void
    {
        foreach ($this->rows($work, 'users') as $user) {
            if (($user['user_type'] ?? null) === 'student') {
                $this->studentUsers[(int) $user['id']] = $user;
            }
        }

        $blocks = [];
        foreach ($this->rows($work, 'online_exam_blocks') as $block) {
            $blocks[(int) $block['id']] = (int) ($block['online_exam_id'] ?? $block['examable_id'] ?? 0);
        }

        foreach ($this->rows($work, 'online_exam_block_items') as $item) {
            $type = (string) ($item['linkable_item_type'] ?? '');

            if (str_contains(strtolower($type), 'question') && isset($blocks[(int) $item['online_exam_block_id']])) {
                $this->questionExam[(int) $item['linkable_item_id']] = $blocks[(int) $item['online_exam_block_id']];
            }
        }
    }

    /** @param array<string, string> $foreignKeys */
    private function importFile(string $work, string $file, string $table, array $foreignKeys, ImportReport $report): void
    {
        $renames = array_flip(TafraSchemaMap::renames()[$file] ?? []);

        foreach ($this->rows($work, $file) as $row) {
            $report->read($file);
            $oldId = $row['id'] ?? null;

            foreach ($renames as $theirs => $ours) {
                if (array_key_exists($theirs, $row)) {
                    $row[$ours] = $row[$theirs];
                    unset($row[$theirs]);
                }
            }

            $prepared = $this->prepare($file, $row, $report);

            if ($prepared === null) {
                continue;
            }

            foreach ($foreignKeys as $column => $target) {
                $ourColumn = $renames[$column] ?? $column;

                if (! array_key_exists($ourColumn, $prepared) || $prepared[$ourColumn] === null) {
                    continue;
                }

                $mapped = $this->resolve($target, $prepared[$ourColumn]);

                if ($mapped === null && ! $this->nullable($table, $ourColumn)) {
                    $report->skipped($file, 'missing_parent', ['column' => $ourColumn]);

                    continue 2;
                }

                $prepared[$ourColumn] = $mapped;
            }

            if ($file === 'money_transactions') {
                $prepared = $this->morph($prepared);
            }

            // الـ IDs المخزّنة جوه JSON (ترتيب الأسئلة والاختيار المختار) بتتترجم برضه
            if ($file === 'online_passed_exams') {
                $prepared['question_order'] = $this->remapJsonList($prepared['question_order'] ?? null, 'questions');
            }

            if ($file === 'student_exam_answers') {
                $prepared['answer'] = $this->remapChoice($prepared['answer'] ?? null);
            }

            // عنصر بيشاور على درس/امتحان اتساب (نوع مش مدعوم) = مالوش لازمة
            if ($file === 'bootcamp_section_items'
                && (($prepared['type'] ?? null) === 'lesson' ? ($prepared['online_lesson_id'] ?? null) : ($prepared['online_exam_id'] ?? null)) === null) {
                $report->skipped($file, 'missing_parent', ['column' => ($prepared['type'] ?? null) === 'lesson' ? 'online_lesson_id' : 'online_exam_id']);

                continue;
            }

            $existing = $this->existing($file, $prepared);

            if ($existing !== null) {
                if ($oldId !== null) {
                    $this->map[$file][$oldId] = $existing;
                }

                $report->merged($file);

                continue;
            }

            $prepared = $this->uniquify($file, $prepared);

            if ($prepared === null) {
                $report->skipped($file, 'duplicate');

                continue;
            }

            $newId = (int) DB::table($table)->insertGetId($this->onlyColumns($table, [...$prepared, 'tenant_id' => $this->tenantId]));

            if ($oldId !== null) {
                $this->map[$file][$oldId] = $newId;
            }

            if ($file === 'students') {
                $this->newStudents[] = $newId;
                $this->map['students:user'][(int) $prepared['__user_id']] = $newId;

                if (isset($prepared['__balance'])) {
                    $this->balances[$newId] = (float) $prepared['__balance'];
                }
            }

            $report->imported($file);
        }
    }

    /**
     * تحويل صف من صيغة طفرة لصيغة جدولنا. null = الصف ده مش مدعوم (بيتسجّل في التقرير).
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>|null
     */
    private function prepare(string $file, array $row, ImportReport $report): ?array
    {
        unset($row['id'], $row['tenant_id']);

        switch ($file) {
            case 'online_lessons':
                if (! in_array((string) ($row['type'] ?? ''), self::SUPPORTED_LESSON_TYPES, true)) {
                    $report->skipped($file, 'lesson_type', ['type' => (string) ($row['type'] ?? '?')]);

                    return null;
                }

                $row['type'] = LessonType::from((string) $row['type'])->value;
                break;

            case 'questions':
                $type = (string) ($row['type'] ?? '');

                if ($type === '') {
                    $type = str_contains(strtolower((string) ($row['questionable_type'] ?? '')), 'essay') ? 'essay' : 'multiple_choice';
                }

                $row['type'] = $type;
                $row['online_exam_id'] ??= $this->questionExam[(int) ($row['questionable_id'] ?? 0)] ?? null;
                $row['mark'] ??= 1;
                $row['text'] = (string) ($row['text'] ?? $row['name'] ?? '');
                break;

            case 'students':
                $user = $this->studentUsers[(int) ($row['user_id'] ?? 0)] ?? null;

                if ($user === null || blank($user['phone'] ?? null)) {
                    $report->skipped($file, 'student_without_user');

                    return null;
                }

                $extra = is_array($row['extra_info'] ?? null) ? $row['extra_info'] : (array) json_decode((string) ($row['extra_info'] ?? '[]'), true);

                $row = [
                    '__user_id' => (int) $row['user_id'],
                    'name' => (string) $user['name'],
                    'phone' => RegisterStudentAction::normalizePhone((string) $user['phone']),
                    'email' => $user['email'] ?? null,
                    // الهاش زي ما هو (bcrypt) — مابيتعادش تشفيره
                    'password' => filled($user['password'] ?? null) ? (string) $user['password'] : Hash::make(Str::password(32)),
                    'parent_phone' => filled($row['parent_phone'] ?? null) ? (string) $row['parent_phone'] : null,
                    'code' => (string) ($row['code'] ?? ''),
                    'educational_level_id' => $row['educational_level_id'] ?? null,
                    'governorate' => $extra['governorate'] ?? null,
                    'school_type' => $extra['school_type'] ?? null,
                    'status' => ($user['is_active'] ?? true) ? 'active' : 'blocked',
                    'created_at' => $user['created_at'] ?? now(),
                    'updated_at' => now(),
                    'deleted_at' => $row['deleted_at'] ?? null,
                    '__balance' => $extra['balance'] ?? null,
                ];
                break;

            case 'online_exams':
                // طفرة ممكن تستخدم 0 = مفتوح
                if (isset($row['allowed_chances']) && (int) $row['allowed_chances'] <= 0) {
                    $row['allowed_chances'] = null;
                }
                break;

            case 'payment_invoices':
                $row['number'] ??= (string) ($row['invoice_id'] ?? Str::upper(Str::random(10)));
                $row['payment_gateway'] = in_array($row['payment_gateway'] ?? null, ['manual_transfer', 'wallet', 'recharge_card', 'free'], true)
                    ? $row['payment_gateway']
                    : 'manual_transfer';
                $row['subtotal'] ??= $row['amount'] ?? 0;
                $row['discount_amount'] ??= 0;
                break;
        }

        if (array_key_exists('extra_info', $row) && is_array($row['extra_info'])) {
            $row['extra_info'] = json_encode($row['extra_info'], JSON_UNESCAPED_UNICODE);
        }

        return $row;
    }

    private function resolve(string $target, mixed $oldId): ?int
    {
        if ($target === 'users') {
            // مستخدمي اللوحة مابيتنقلوش — اللي بيستورد هو صاحب السجل
            return $this->actorId;
        }

        if ($target === 'students') {
            return $this->map['students:user'][(int) $oldId] ?? null;
        }

        return $this->map[$target][$oldId] ?? null;
    }

    /**
     * سجل موجود بالفعل = دمج مش تكرار (الطالب بالموبايل، الصف بالكود).
     *
     * @param array<string, mixed> $row
     */
    private function existing(string $file, array $row): ?int
    {
        $id = match ($file) {
            'students' => DB::table('students')->where('tenant_id', $this->tenantId)->where('phone', $row['phone'])->value('id'),
            'educational_levels' => DB::table('educational_levels')->where('tenant_id', $this->tenantId)->where('code', $row['code'] ?? '')->value('id'),
            // إعادة استيراد نفس الملف: الحركة/الفاتورة/المحاولة/الاشتراك الموجودين مايتكرروش
            'money_transactions' => filled($row['uuid'] ?? null)
                ? DB::table('money_transactions')->where('tenant_id', $this->tenantId)->where('uuid', $row['uuid'])->value('id')
                : null,
            'payment_invoices' => DB::table('payment_invoices')->where('tenant_id', $this->tenantId)
                ->where('number', $row['number'] ?? '')->where('student_id', $row['student_id'] ?? 0)->value('id'),
            'bootcamp_enrolled_students_histories' => DB::table('bootcamp_enrolled_students_histories')->where('tenant_id', $this->tenantId)
                ->where('student_id', $row['student_id'] ?? 0)->where('bootcamp_id', $row['bootcamp_id'] ?? 0)
                ->where('type', $row['type'] ?? 'enroll')->where('created_at', $row['created_at'] ?? null)->value('id'),
            'online_passed_exams' => DB::table('online_passed_exams')->where('tenant_id', $this->tenantId)
                ->where('student_id', $row['student_id'] ?? 0)->where('online_exam_id', $row['online_exam_id'] ?? 0)
                ->where('start_at', $row['start_at'] ?? null)->value('id'),
            'student_exam_answers' => DB::table('student_exam_answers')->where('tenant_id', $this->tenantId)
                ->where('online_passed_exam_id', $row['online_passed_exam_id'] ?? 0)->where('question_id', $row['question_id'] ?? 0)->value('id'),
            'section_item_views' => DB::table('section_item_views')->where('tenant_id', $this->tenantId)
                ->where('student_id', $row['student_id'] ?? 0)->where('bootcamp_section_item_id', $row['bootcamp_section_item_id'] ?? 0)->value('id'),
            'coupon_redemptions' => DB::table('coupon_redemptions')->where('tenant_id', $this->tenantId)
                ->where('coupon_id', $row['coupon_id'] ?? 0)->where('student_id', $row['student_id'] ?? 0)
                ->where('payment_invoice_id', $row['payment_invoice_id'] ?? 0)->value('id'),
            default => null,
        };

        if ($id !== null && $file === 'students') {
            $this->map['students:user'][(int) $row['__user_id']] = (int) $id;
        }

        return $id !== null ? (int) $id : null;
    }

    /**
     * القيم الفريدة: الرابط ورقم الفاتورة بياخدوا لاحقة، الكود المكرر بيتساب.
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>|null
     */
    private function uniquify(string $file, array $row): ?array
    {
        $taken = fn (string $table, string $column, mixed $value): bool => DB::table($table)
            ->where('tenant_id', $this->tenantId)->where($column, $value)->exists();

        switch ($file) {
            case 'bootcamps':
                $slug = (string) ($row['slug'] ?? Str::random(8));
                while ($taken('bootcamps', 'slug', $slug)) {
                    $slug = $row['slug'].'-'.Str::lower(Str::random(4));
                }
                $row['slug'] = $slug;
                break;
            case 'payment_invoices':
                while ($taken('payment_invoices', 'number', $row['number'])) {
                    $row['number'] .= '-'.Str::upper(Str::random(3));
                }
                break;
            case 'money_transactions':
                $row['uuid'] = filled($row['uuid'] ?? null) && ! $taken('money_transactions', 'uuid', $row['uuid']) ? $row['uuid'] : (string) Str::uuid();
                break;
            case 'students':
                if ($row['code'] === '' || $taken('students', 'code', $row['code'])) {
                    $row['code'] = (string) random_int(100000, 999999).Str::upper(Str::random(2));
                }
                break;
            case 'coupons':
                if ($taken('coupons', 'code', $row['code'])) {
                    return null;
                }
                break;
            case 'recharge_cards':
                if ($taken('recharge_cards', 'code', $row['code'])) {
                    return null;
                }
                break;
        }

        return $row;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function morph(array $row): array
    {
        $alias = (string) ($row['transactionable_type'] ?? '');
        $class = TafraSchemaMap::morphAliases()[$alias] ?? null;
        $table = match ($alias) {
            'recharge_card' => 'recharge_cards',
            'payment_invoice' => 'payment_invoices',
            default => null,
        };

        if ($class === null || $table === null) {
            return [...$row, 'transactionable_type' => null, 'transactionable_id' => null];
        }

        return [
            ...$row,
            'transactionable_type' => (new $class)->getMorphClass(),
            'transactionable_id' => $this->map[$table][$row['transactionable_id'] ?? 0] ?? null,
        ];
    }

    /** الرصيد = من extra_info لو موجود، وإلا مجموع الحركات المستوردة */
    private function recalculateBalances(): void
    {
        foreach ($this->newStudents as $newId) {
            $balance = $this->balances[$newId] ?? null;

            if ($balance === null) {
                $deposits = (float) DB::table('money_transactions')->where('student_id', $newId)->where('type', 'deposit')->sum('amount');
                $withdrawals = (float) DB::table('money_transactions')->where('student_id', $newId)->where('type', 'withdraw')->sum('amount');
                $balance = $deposits - $withdrawals;
            }

            DB::table('students')->where('id', $newId)->update(['balance' => round(max(0.0, $balance), 2)]);
        }
    }

    private function importSettings(string $work): void
    {
        $path = $work.'/settings.json';

        if (! is_file($path)) {
            return;
        }

        foreach ((array) json_decode((string) file_get_contents($path), true) as $group => $values) {
            // الإعدادات اللي بتتنقل: الموقع والمظهر بس (مفاتيح الذكاء الاصطناعي والإشعارات لأ)
            if (! in_array($group, ['site', 'appearance'], true) || ! is_array($values)) {
                continue;
            }

            foreach ($values as $key => $value) {
                if ($group === 'site' && in_array($key, ExportAcademyAction::BRANDING_KEYS, true)) {
                    $value = $this->importBrandingFile($work, (string) $key, $value);
                }

                $this->settings->set((string) $group, (string) $key, $value);
            }
        }
    }

    /** الملف بيتنسخ لقرص الهوية بمسار جديد — ولو مش في الملف، الإعداد بيتفضّى بدل مسار مكسور. */
    private function importBrandingFile(string $work, string $key, mixed $path): ?string
    {
        if (! is_string($path) || $path === '') {
            return null;
        }

        $source = $work.'/branding/'.$key.'_'.basename($path);

        if (! is_file($source)) {
            return null;
        }

        $extension = strtolower(pathinfo($source, PATHINFO_EXTENSION));

        if (! in_array($extension, ['png', 'jpg', 'jpeg', 'webp'], true)) {
            return null;
        }

        $target = 'site/'.Str::random(32).'.'.$extension;
        Storage::disk(app(DiskResolver::class)->for('branding'))
            ->put($target, (string) file_get_contents($source), 'public');

        return $target;
    }

    private function importMedia(string $work, ImportReport $report): int
    {
        $count = 0;
        $specs = TafraSchemaMap::media();

        foreach ($this->rows($work, 'media_index') as $entry) {
            $table = (string) ($entry['table'] ?? '');
            $spec = $specs[$table] ?? null;
            $newId = $this->map[$table][$entry['id'] ?? 0] ?? null;
            $relative = (string) ($entry['path'] ?? '');
            $file = realpath($work.'/'.ltrim($relative, '/'));
            $collection = (string) ($entry['collection'] ?? '');

            // المسار لازم يفضل جوه مجلد الاستيراد — `..` كان ممكن ينسخ أي ملف على السيرفر
            if ($file === false || str_contains($relative, '..') || ! str_starts_with($file, (string) realpath($work).DIRECTORY_SEPARATOR)) {
                continue;
            }

            if ($spec === null || $newId === null || ! is_file($file) || ! in_array($collection, $spec['collections'], true)) {
                continue;
            }

            $model = $spec['model']::query()->find($newId);

            if ($model === null) {
                continue;
            }

            try {
                $model->addMedia($file)->preservingOriginal()->toMediaCollection($collection, $this->disks->for($collection));
                $count++;
            } catch (Throwable) {
                $report->skipped($table, 'media_failed', ['file' => basename($file)]);
            }
        }

        return $count;
    }

    /** @return iterable<array<string, mixed>> */
    private function rows(string $work, string $file): iterable
    {
        $path = "{$work}/data/{$file}.ndjson";

        if (! is_file($path)) {
            return;
        }

        $handle = fopen($path, 'r');

        if ($handle === false) {
            return;
        }

        try {
            while (($line = fgets($handle)) !== false) {
                $line = trim($line);

                if ($line === '') {
                    continue;
                }

                $row = json_decode($line, true);

                if (is_array($row)) {
                    yield $row;
                }
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function onlyColumns(string $table, array $row): array
    {
        $this->columns[$table] ??= Schema::getColumnListing($table);

        return array_intersect_key($row, array_flip($this->columns[$table]));
    }

    private function nullable(string $table, string $column): bool
    {
        foreach (Schema::getColumns($table) as $definition) {
            if ($definition['name'] === $column) {
                return (bool) $definition['nullable'];
            }
        }

        return true;
    }

    private function remapJsonList(mixed $value, string $table): ?string
    {
        $ids = is_array($value) ? $value : json_decode((string) $value, true);

        if (! is_array($ids)) {
            return null;
        }

        return (string) json_encode(array_values(array_filter(
            array_map(fn (mixed $id): ?int => $this->map[$table][$id] ?? null, $ids),
            static fn (?int $id): bool => $id !== null,
        )));
    }

    private function remapChoice(mixed $value): ?string
    {
        $answer = is_array($value) ? $value : json_decode((string) $value, true);

        if (! is_array($answer)) {
            return null;
        }

        if (array_key_exists('choice_id', $answer) && $answer['choice_id'] !== null) {
            $answer['choice_id'] = $this->map['choices'][$answer['choice_id']] ?? null;
        }

        return (string) json_encode($answer, JSON_UNESCAPED_UNICODE);
    }
}
