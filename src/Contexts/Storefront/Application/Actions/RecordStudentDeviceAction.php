<?php

declare(strict_types=1);

namespace Src\Contexts\Storefront\Application\Actions;

use Illuminate\Support\Facades\DB;
use Src\Contexts\Storefront\Application\Queries\SiteSettings;
use Src\Contexts\Storefront\Domain\Exceptions\StorefrontException;
use Src\Contexts\Students\Domain\Models\Student;
use Src\Contexts\Students\Domain\Models\StudentDevice;
use Src\Support\Domain\ValueObjects\DeviceSignature;

/**
 * «جهاز واحد لكل طالب»: الجهاز اللي الطالب داخل منه بيتسجّل (أو بيتعرف لو
 * متسجّل قبل كده بنفس التوكن). لو الطالب وصل للعدد اللي المدرس حدده، الجهاز
 * الجديد بيترفض برسالة «كلّم المدرس» — المدرس بيفصل الأجهزة من اللوحة.
 *
 * العدد بييجي من «إعدادات الموقع» (`max_devices_per_student`، 0 = مفتوح).
 */
final readonly class RecordStudentDeviceAction
{
    public function __construct(private SiteSettings $site) {}

    public function handle(Student $student, string $token, ?string $userAgent, ?string $ip): StudentDevice
    {
        $hash = StudentDevice::hashToken($token);
        $limit = $this->site->load()->maxDevicesPerStudent;

        return DB::transaction(function () use ($student, $hash, $limit, $userAgent, $ip): StudentDevice {
            // قفل صف الطالب: دخولين في نفس اللحظة من جهازين مايعدّوش الحد سوا
            Student::query()->whereKey($student->getKey())->lockForUpdate()->first();

            $attributes = [
                'device_name' => DeviceSignature::fromUserAgent($userAgent)->name,
                'user_agent' => $userAgent !== null ? mb_substr($userAgent, 0, 512) : null,
                'ip_address' => $ip,
                'last_seen_at' => now(),
            ];

            $known = $student->devices()->where('token_hash', $hash)->first();

            if ($known !== null) {
                $known->update($attributes);

                return $known;
            }

            throw_if(
                $limit > 0 && $student->devices()->count() >= $limit,
                StorefrontException::because('device_limit', ['count' => fc_number($limit)]),
            );

            return $student->devices()->create(['token_hash' => $hash, ...$attributes]);
        });
    }

    /**
     * الجهاز لسه متسجّل للطالب ده؟ (المدرس ممكن يكون فصله). بيحدّث «آخر ظهور»
     * مرة كل `touch_every_seconds` بس عشان مايبقاش فيه كتابة مع كل طلب.
     */
    public function stillConnected(Student $student, int $deviceId, bool $touch = true): bool
    {
        $device = StudentDevice::query()
            ->whereKey($deviceId)
            ->where('student_id', $student->getKey())
            ->first(['id', 'tenant_id', 'student_id', 'last_seen_at']);

        if ($device === null) {
            return false;
        }

        $every = (int) config('storefront.devices.touch_every_seconds', 300);

        if ($touch && ($device->last_seen_at === null || $device->last_seen_at->lt(now()->subSeconds($every)))) {
            $device->forceFill(['last_seen_at' => now()])->saveQuietly();
        }

        return true;
    }
}
