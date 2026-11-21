<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Src\Contexts\Content\Domain\Enums\AnnouncementSeverity;
use Src\Contexts\Content\Domain\Enums\AnnouncementStatus;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;

/**
 * بيانات ضخمة لاختبار أداء الجداول من يوم ١ (docs/00 بند ٦).
 *
 * الإدخال بـ `insert` على دفعات — لو دخّلنا ٥٠ ألف سجل بالموديل
 * هياخد دقايق. لاحظ إن الحقول المترجمة لازم تتكتب JSON بإيدنا
 * لأن `insert()` بيتخطى الـ casts.
 */
final class DemoSeeder extends Seeder
{
    private const BATCH = 1000;

    public function run(): void
    {
        $count = (int) config('seeding.demo_rows');

        $tenant = Tenant::query()->where('slug', 'acme')->firstOrFail();
        $author = User::query()->where('email', 'admin@futurecode.test')->firstOrFail();

        app(TenantContext::class)->set($tenant->getKey());

        $this->seedUsers($tenant, 200);
        $this->seedAnnouncements($tenant, $author, $count);

        $this->command->info("تم توليد {$count} إعلان و200 مستخدم في مؤسسة acme.");
    }

    private function seedUsers(Tenant $tenant, int $count): void
    {
        $now = now();
        $rows = [];
        $password = bcrypt('Password!2026');

        // المستخدمين مركزيين في الوضعين — في multi الاتصال الافتراضي هنا
        // بقى قاعدة المؤسسة، والإعلانات بس هي اللي مكانها هناك
        $central = DB::connection((string) config('tenancy.database.central_connection'));

        $start = (int) $central->table('users')->max('id') + 1;

        for ($i = 0; $i < $count; $i++) {
            $rows[] = [
                'code' => sprintf('USR-%06d', $start + $i),
                'name' => 'مستخدم تجريبي '.($i + 1),
                'email' => "demo{$i}@futurecode.test",
                'password' => $password,
                'status' => 'active',
                'is_active' => true,
                'locale' => 'ar',
                'email_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, self::BATCH) as $chunk) {
            $central->table('users')->insertOrIgnore($chunk);
        }

        $ids = $central->table('users')->whereIn('email', array_column($rows, 'email'))->pluck('id');

        $central->table('tenant_user')->insertOrIgnore(
            $ids->map(fn (int $id): array => [
                'tenant_id' => $tenant->getKey(),
                'user_id' => $id,
                'joined_at' => $now,
            ])->all(),
        );
    }

    private function seedAnnouncements(Tenant $tenant, User $author, int $count): void
    {
        $now = now();
        $statuses = array_column(AnnouncementStatus::cases(), 'value');
        $severities = array_column(AnnouncementSeverity::cases(), 'value');

        for ($offset = 0; $offset < $count; $offset += self::BATCH) {
            $rows = [];
            $size = min(self::BATCH, $count - $offset);

            for ($i = 0; $i < $size; $i++) {
                $index = $offset + $i + 1;
                $status = $statuses[$index % count($statuses)];

                $rows[] = [
                    'tenant_id' => $tenant->getKey(),
                    'author_id' => $author->getKey(),
                    // insert() بيتخطى الـ casts — الحقول المترجمة JSON بإيدنا
                    'title' => json_encode([
                        'ar' => "إعلان تجريبي رقم {$index}",
                        'en' => "Demo announcement #{$index}",
                    ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                    'body' => json_encode([
                        'ar' => "<p>نص الإعلان التجريبي رقم {$index}.</p>",
                        'en' => "<p>Body of demo announcement #{$index}.</p>",
                    ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                    'status' => $status,
                    'severity' => $severities[$index % count($severities)],
                    'is_pinned' => false,
                    'published_at' => $status === 'published' ? $now->copy()->subMinutes($index) : null,
                    'created_at' => $now->copy()->subMinutes($index),
                    'updated_at' => $now,
                ];
            }

            DB::table('announcements')->insert($rows);
        }
    }
}
