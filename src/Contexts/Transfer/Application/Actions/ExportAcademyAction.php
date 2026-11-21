<?php

declare(strict_types=1);

namespace Src\Contexts\Transfer\Application\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Settings\Application\Services\TenantSettings;
use Src\Contexts\Settings\Domain\Models\TenantSetting;
use Src\Contexts\Transfer\Application\Schema\TafraSchemaMap;
use Src\Contexts\Transfer\Domain\Exceptions\TransferException;
use Src\Support\Application\Contracts\TenantContext;
use ZipArchive;

/**
 * يصدّر أكاديمية كاملة لملف zip بصيغة طفرة (docs/transfer/format.md):
 * manifest.json + settings.json + data/<table>.ndjson + media/<table>/<id>/<collection>/<file>.
 *
 * بيمشي على الجداول بـ chunk (مايحمّلش القاعدة كلها في الذاكرة) — يستحمل الاستضافة المشتركة.
 * التوكنز والجلسات والأجهزة ومستخدمي اللوحة (كلمات السر) مابيتصدّروش.
 */
final readonly class ExportAcademyAction
{
    private const CHUNK = 500;

    public function __construct(
        private TenantContext $context,
        private TenantSettings $settings,
    ) {}

    /** @return string مسار ملف الـ zip */
    public function handle(string $targetPath): string
    {
        $tenantId = $this->context->id();
        throw_if($tenantId === null, TransferException::because('no_academy'));
        throw_unless(class_exists(ZipArchive::class), TransferException::because('zip_missing'));

        $work = storage_path('app/transfer/'.Str::uuid());
        File::ensureDirectoryExists($work.'/data');

        try {
            $counts = [];

            foreach ($this->synthetic() as $file => $rows) {
                $counts[$file] = $this->writeRows($work, $file, $rows);
            }

            $counts['users'] = $this->exportUsers($work, (int) $tenantId);

            foreach (TafraSchemaMap::tables() as $file => $spec) {
                $counts[$file] = $this->exportTable($work, $file, $spec['source'], (int) $tenantId);
            }

            foreach ($this->examStructure((int) $tenantId) as $file => $rows) {
                $counts[$file] = $this->writeRows($work, $file, $rows);
            }

            $mediaCount = $this->exportMedia($work);

            File::put($work.'/settings.json', (string) json_encode(
                TenantSetting::query()->get(['group', 'key', 'value'])
                    ->groupBy('group')
                    ->map(fn ($rows) => $rows->pluck('value', 'key'))
                    ->all(),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
            ));

            $checksums = [];
            foreach (File::allFiles($work.'/data') as $file) {
                $checksums['data/'.$file->getFilename()] = hash_file('sha256', $file->getPathname());
            }

            File::put($work.'/manifest.json', (string) json_encode([
                'format' => TafraSchemaMap::FORMAT,
                'format_version' => TafraSchemaMap::FORMAT_VERSION,
                'source' => 'tafra-open-lms',
                'app_version' => (string) config('app.version'),
                'exported_at' => now()->toIso8601String(),
                'academy' => ['name' => (string) DB::table('tenants')->where('id', $tenantId)->value('name')],
                'student_user_offset' => TafraSchemaMap::STUDENT_USER_OFFSET,
                'counts' => $counts,
                'media_files' => $mediaCount,
                'checksums' => $checksums,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            $this->zip($work, $targetPath);
        } finally {
            File::deleteDirectory($work);
        }

        return $targetPath;
    }

    /**
     * صفوف بتطلبها طفرة ومالهاش مقابل عندنا (تصنيف، مادة، فئة كورس/امتحان، جزء مادة).
     *
     * @return array<string, list<array<string, mixed>>>
     */
    private function synthetic(): array
    {
        $subject = (string) ($this->settings->get('site', 'subject') ?: __('transfer::transfer.defaults.subject'));

        return [
            'educational_categories' => [['id' => 1, 'name' => __('transfer::transfer.defaults.category')]],
            'materials' => [['id' => 1, 'name' => $subject, 'code' => 'MAT-1', 'educational_level_id' => null]],
            'material_parts' => [['id' => 1, 'material_id' => 1, 'name' => $subject]],
            'bootcamp_categories' => [['id' => 1, 'name' => __('transfer::transfer.defaults.bootcamp_category')]],
            'exam_categories' => [['id' => 1, 'name' => __('transfer::transfer.defaults.exam_category')]],
        ];
    }

    /** مستخدمي اللوحة (من غير كلمات سر) + الطلبة كمستخدمين بنوع student */
    private function exportUsers(string $work, int $tenantId): int
    {
        $handle = $this->open($work, 'users');
        $count = 0;

        User::query()
            ->whereHas('tenants', fn ($q) => $q->where('tenants.id', $tenantId))
            ->orderBy('id')
            ->chunk(self::CHUNK, function ($users) use ($handle, &$count): void {
                foreach ($users as $user) {
                    $this->line($handle, [
                        'id' => $user->getKey(),
                        'name' => $user->getAttribute('name'),
                        'email' => $user->getAttribute('email'),
                        'phone' => $user->getAttribute('phone'),
                        'user_type' => 'moderator',
                        'password' => null,
                        'created_at' => $user->getAttribute('created_at')?->toIso8601String(),
                    ]);
                    $count++;
                }
            });

        DB::table('students')->where('tenant_id', $tenantId)->orderBy('id')
            ->chunk(self::CHUNK, function ($students) use ($handle, &$count): void {
                foreach ($students as $student) {
                    $this->line($handle, [
                        'id' => (int) $student->id + TafraSchemaMap::STUDENT_USER_OFFSET,
                        'name' => $student->name,
                        'email' => $student->email,
                        'phone' => $student->phone,
                        'user_type' => 'student',
                        // bcrypt — بيشتغل في طفرة زي ما هو
                        'password' => $student->password,
                        'is_active' => $student->status === 'active',
                        'last_login_at' => $student->last_login_at,
                        'created_at' => $student->created_at,
                        'deleted_at' => $student->deleted_at,
                    ]);
                    $count++;
                }
            });

        fclose($handle);

        return $count;
    }

    private function exportTable(string $work, string $file, string $source, int $tenantId): int
    {
        $renames = TafraSchemaMap::renames()[$file] ?? [];
        $aliases = array_flip(TafraSchemaMap::morphAliases());
        $handle = $this->open($work, $file);
        $count = 0;

        DB::table($source)->where('tenant_id', $tenantId)->orderBy('id')
            ->chunk(self::CHUNK, function ($rows) use ($handle, $file, $renames, $aliases, &$count): void {
                foreach ($rows as $row) {
                    $data = (array) $row;
                    unset($data['tenant_id'], $data['remember_token']);

                    foreach ($renames as $ours => $theirs) {
                        if (array_key_exists($ours, $data)) {
                            $data[$theirs] = $data[$ours];
                            unset($data[$ours]);
                        }
                    }

                    $this->line($handle, $this->shape($file, $data, $aliases));
                    $count++;
                }
            });

        fclose($handle);

        return $count;
    }

    /**
     * تعديلات لكل جدول عشان يطابق طفرة.
     *
     * @param array<string, mixed> $data
     * @param array<class-string, string> $aliases
     * @return array<string, mixed>
     */
    private function shape(string $file, array $data, array $aliases): array
    {
        $studentUser = static fn (mixed $id): ?int => $id === null ? null : (int) $id + TafraSchemaMap::STUDENT_USER_OFFSET;

        // كل عمود بيشاور على طالب بقى user_id في طفرة — بالـ offset
        foreach (TafraSchemaMap::tables()[$file]['fk'] ?? [] as $column => $target) {
            if ($target === 'students' && array_key_exists($column, $data)) {
                $data[$column] = $studentUser($data[$column]);
            }
        }

        return match ($file) {
            'educational_levels' => [...$data, 'educational_category_id' => 1],
            'online_lessons' => [...$data, 'material_id' => 1],
            'bootcamps' => [...$data, 'material_id' => 1, 'bootcamp_category_id' => 1, 'meta_keywords' => '', 'meta_description' => '', 'access_mode' => null],
            'online_exams' => [...$data, 'material_id' => 1, 'exam_category_id' => 1],
            'questions' => [
                ...$data,
                'name' => Str::limit(trim(strip_tags((string) $data['text'])), 250),
                'material_id' => 1,
                'material_parts_id' => 1,
                'questionable_type' => $data['type'] === 'essay' ? 'essay_question' : 'multiple_choices',
                'questionable_id' => $data['id'],
            ],
            'students' => [
                'id' => $data['id'],
                'user_id' => $studentUser($data['id']),
                'parent_phone' => (string) ($data['parent_phone'] ?? ''),
                'code' => $data['code'],
                'educational_level_id' => $data['educational_level_id'],
                'extra_info' => json_encode(array_filter([
                    'governorate' => $data['governorate'] ?? null,
                    'school_type' => $data['school_type'] ?? null,
                    'balance' => $data['balance'] ?? null,
                    ...((array) json_decode((string) ($data['extra_info'] ?? '[]'), true)),
                ], static fn (mixed $v): bool => $v !== null), JSON_UNESCAPED_UNICODE),
                'deleted_at' => $data['deleted_at'] ?? null,
            ],
            'payment_invoices' => [
                ...$data,
                'invoice_id' => $data['number'],
                'invoice_key' => $data['number'],
                'invoice_url' => '',
                'payment_method' => $data['payment_gateway'],
                'due_date' => $data['created_at'],
                'paid_amount' => $data['status'] === 'paid' ? $data['amount'] : 0,
                'source' => 'manual',
            ],
            'money_transactions' => [
                ...$data,
                'transactionable_type' => $aliases[$data['transactionable_type'] ?? ''] ?? $data['transactionable_type'],
            ],
            default => $data,
        };
    }

    /**
     * طفرة بتربط الأسئلة بالامتحان عن طريق بلوكات — بنولّد بلوك واحد لكل امتحان،
     * وصف `multiple_choices` لكل سؤال اختيار.
     *
     * @return array<string, list<array<string, mixed>>>
     */
    private function examStructure(int $tenantId): array
    {
        $blocks = DB::table('online_exams')->where('tenant_id', $tenantId)->orderBy('id')->get(['id', 'name'])
            ->map(fn (object $e): array => ['id' => $e->id, 'name' => $e->name, 'online_exam_id' => $e->id, 'order' => 0])->all();

        $questions = DB::table('questions')->where('tenant_id', $tenantId)->whereNotNull('online_exam_id')->orderBy('id')->get(['id', 'online_exam_id', 'order', 'type']);

        return [
            'online_exam_blocks' => array_values($blocks),
            'online_exam_block_items' => $questions->map(fn (object $q): array => [
                'id' => $q->id, 'online_exam_block_id' => $q->online_exam_id,
                'linkable_item_type' => 'question', 'linkable_item_id' => $q->id, 'order' => $q->order,
            ])->all(),
            'multiple_choices' => $questions->where('type', 'multiple_choice')->map(fn (object $q): array => [
                'id' => $q->id, 'question_id' => $q->id, 'show_only_two_choices' => false,
            ])->values()->all(),
        ];
    }

    private function exportMedia(string $work): int
    {
        $count = 0;
        $index = [];

        foreach (TafraSchemaMap::media() as $file => $spec) {
            /** @var class-string<Model> $model */
            $model = $spec['model'];

            $model::query()->with('media')->orderBy('id')->chunk(200, function ($records) use ($work, $file, $spec, &$count, &$index): void {
                foreach ($records as $record) {
                    if (! $record instanceof HasMedia) {
                        continue;
                    }

                    foreach ($spec['collections'] as $collection) {
                        foreach ($record->getMedia($collection) as $media) {
                            $stream = $media->stream();

                            if (! is_resource($stream)) {
                                continue;
                            }

                            $relative = "media/{$file}/{$record->getKey()}/{$collection}/".basename($media->file_name);
                            File::ensureDirectoryExists(dirname($work.'/'.$relative));
                            $out = fopen($work.'/'.$relative, 'w');

                            if ($out !== false) {
                                stream_copy_to_stream($stream, $out);
                                fclose($out);
                                $index[] = ['path' => $relative, 'table' => $file, 'id' => $record->getKey(), 'collection' => $collection, 'mime' => $media->mime_type];
                                $count++;
                            }

                            fclose($stream);
                        }
                    }
                }
            });
        }

        $this->writeRows($work, 'media_index', $index);

        return $count;
    }

    /** @param list<array<string, mixed>> $rows */
    private function writeRows(string $work, string $file, array $rows): int
    {
        $handle = $this->open($work, $file);

        foreach ($rows as $row) {
            $this->line($handle, $row);
        }

        fclose($handle);

        return count($rows);
    }

    /** @return resource */
    private function open(string $work, string $file)
    {
        $handle = fopen("{$work}/data/{$file}.ndjson", 'w');
        throw_if($handle === false, TransferException::because('write_failed'));

        return $handle;
    }

    /**
     * @param resource $handle
     * @param array<string, mixed> $row
     */
    private function line($handle, array $row): void
    {
        fwrite($handle, json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION)."\n");
    }

    private function zip(string $work, string $target): void
    {
        File::ensureDirectoryExists(dirname($target));
        $zip = new ZipArchive;
        throw_unless($zip->open($target, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true, TransferException::because('write_failed'));

        foreach (File::allFiles($work) as $file) {
            $zip->addFile($file->getPathname(), ltrim(str_replace($work, '', $file->getPathname()), '/'));
        }

        $zip->close();
    }
}
