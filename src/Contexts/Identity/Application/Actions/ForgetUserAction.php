<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Application\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Support\Application\Contracts\TenantContext;
use Src\Support\Infrastructure\Tenancy\TenancyMode;

/**
 * حق الحذف — مسح بيانات مستخدم عبر كل الجداول والملفات (docs/20 بند ١٣).
 *
 * ⚠️ سجل التدقيق **مابيتحذفش، بيتجهّل**: `causer_id` بيبقى null والاسم
 * بيتشال، فيفضل عندنا إثبات إن العملية حصلت من غير ما نحتفظ بهوية
 * صاحبها. حذف السجل نفسه بيكسر التدقيق، والاحتفاظ بيه بالاسم بيكسر
 * حق الحذف — التجهيل هو اللي بيحقق الاتنين.
 *
 * كل الحذف على القاعدة المركزية في transaction واحدة. جداول المؤسسات
 * (الإعلانات) بتتعدّ بس — المحتوى بيفضل منسوب للمستخدم المجهّل (docs/03 بند ٣).
 */
final readonly class ForgetUserAction
{
    /** جداول مركزية بتتحذف بالكامل — مفيش قيمة تدقيقية في الاحتفاظ بيها. */
    private const PURGED = [
        'user_devices' => 'user_id',
        'user_preferences' => 'user_id',
        'user_password_histories' => 'user_id',
        'notification_preferences' => 'user_id',
        'onboarding_progress' => 'user_id',
        'tenant_user' => 'user_id',
    ];

    public function __construct(private TenantContext $tenants) {}

    /** @return array<string, int> عدد الصفوف المتأثرة في كل جدول */
    public function handle(User $user): array
    {
        $id = $user->getKey();

        // قبل الحذف: مؤسسات المستخدم بتتمسح من `tenant_user` جوه الـ transaction
        $affected = ['announcements' => $this->countAuthoredAnnouncements($user)];

        $central = DB::connection(TenancyMode::centralConnection());

        $central->transaction(function () use ($central, $user, $id, &$affected): void {
            foreach (self::PURGED as $table => $column) {
                $affected[$table] = $central->table($table)->where($column, $id)->delete();
            }

            $affected['notifications'] = $central->table('notifications')
                ->where('notifiable_type', $user::class)
                ->where('notifiable_id', $id)
                ->delete();

            $affected['sessions'] = $central->table((string) config('session.table', 'sessions'))
                ->where('user_id', $id)
                ->delete();

            // الملفات: الحذف عبر الموديل عشان يشيلها من القرص كمان
            $affected['media'] = $user->media()->count();
            $user->clearMediaCollection('avatar');

            // التدقيق: تجهيل مش حذف
            $affected['activity_log'] = $central->table('activity_log')
                ->where('causer_id', $id)
                ->where('causer_type', $user::class)
                ->update(['causer_id' => null, 'causer_type' => null]);

            $user->forceFill([
                'name' => __('identity::identity.user.forgotten'),
                'email' => 'forgotten-'.Str::uuid().'@invalid',
                'phone' => null,
                'password' => bcrypt(Str::password(32)),
                'remember_token' => null,
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
                'is_active' => false,
            ])->save();

            $user->delete();

            activity('security')
                ->withProperties(['forgotten_user_id' => $id, 'affected' => $affected])
                ->log('user_forgotten');
        });

        return $affected;
    }

    /**
     * المحتوى بيفضل منسوب للصف نفسه — والصف بقى مجهّل.
     * `announcements.author_id` مش nullable عن قصد (كل إعلان له كاتب)،
     * والتجهيل على مستوى المستخدم بيغطّي المطلوب من غير ما نكسر القيد.
     */
    private function countAuthoredAnnouncements(User $user): int
    {
        $count = 0;

        foreach ($user->tenants()->pluck('tenants.id') as $tenantId) {
            $count += (int) $this->tenants->forTenant((int) $tenantId, static fn (): int => DB::table('announcements')
                ->where('tenant_id', $tenantId)
                ->where('author_id', $user->getKey())
                ->count());
        }

        return $count;
    }
}
