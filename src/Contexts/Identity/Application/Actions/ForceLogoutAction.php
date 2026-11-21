<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Application\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Identity\Domain\Models\UserDevice;
use Src\Support\Infrastructure\Tenancy\TenancyMode;

/**
 * إنهاء جلسات مستخدم. بيتطلب `SESSION_DRIVER=database` عشان نقدر
 * نمسح جلسة محددة (docs/12 بند ٢).
 */
final readonly class ForceLogoutAction
{
    public function handle(User $user, ?string $exceptSessionId = null, ?User $actor = null): int
    {
        // الحاجز الأخير — الـ Action ممكن يتنادى من API أو Command
        if ($actor !== null) {
            Gate::forUser($actor)->authorize('forceLogout', $user);
        }

        $count = $this->deleteSessions($user, $exceptSessionId);

        $devices = UserDevice::query()->where('user_id', $user->getKey());

        if ($exceptSessionId !== null) {
            $devices->where(function ($query) use ($exceptSessionId): void {
                $query->whereNull('session_id')->orWhere('session_id', '!=', $exceptSessionId);
            });
        }

        $devices->update(['session_id' => null]);

        activity('security')
            ->performedOn($user)
            ->withProperties(['sessions_terminated' => $count])
            ->log('force_logout');

        return $count;
    }

    /**
     * إنهاء جلسة **جهاز واحد**.
     *
     * منفصلة عن `handle()` عن قصد: `handle()` بتنهي كل الجلسات ما عدا
     * واحدة — لو استخدمناها هنا، زرار «إنهاء الجلسة» على جهاز واحد
     * بيطلّع المستخدم من كل أجهزته التانية كمان. الاتنين شرطين مختلفين
     * في `docs/12` بند ٢.
     */
    public function device(User $user, UserDevice $device): bool
    {
        // الجهاز لازم يكون بتاع المستخدم — الجدول مفلتر، بس ده الحاجز الأخير
        if ((int) $device->user_id !== (int) $user->getKey()) {
            return false;
        }

        $sessionId = $device->session_id;

        if ($sessionId !== null && (string) config('session.driver') === 'database') {
            DB::connection(TenancyMode::centralConnection())->table((string) config('session.table', 'sessions'))
                ->where('user_id', $user->getKey())
                ->where('id', $sessionId)
                ->delete();
        }

        $device->delete();

        activity('security')
            ->performedOn($user)
            ->withProperties(['device_id' => $device->getKey(), 'session_id' => $sessionId])
            ->log('device_terminated');

        return true;
    }

    private function deleteSessions(User $user, ?string $exceptSessionId): int
    {
        if ((string) config('session.driver') !== 'database') {
            return 0;
        }

        // الجلسات مركزية — في وضع multi الاتصال الافتراضي بيبقى قاعدة المؤسسة (docs/03 بند ٣)
        $query = DB::connection(TenancyMode::centralConnection())
            ->table((string) config('session.table', 'sessions'))
            ->where('user_id', $user->getKey());

        if ($exceptSessionId !== null) {
            $query->where('id', '!=', $exceptSessionId);
        }

        return $query->delete();
    }
}
