<?php

declare(strict_types=1);

namespace Src\Contexts\Storefront\Presentation\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Src\Contexts\Storefront\Application\Actions\RecordStudentDeviceAction;
use Src\Contexts\Students\Domain\Models\Student;

/**
 * الربط بين الجلسة/الكوكي و`RecordStudentDeviceAction`.
 *
 * التوكن بيتشال في كوكي طويلة العمر (بتعرّف المتصفح) وفي الجلسة كمان (لو الكوكي
 * اتمسحت والجلسة لسه شغالة مانعدّهوش جهاز جديد). الجلسة بتشيل رقم الجهاز
 * ورقم الطالب: لو المدرس فصل الأجهزة، أول طلب بعدها بيلاقي الجهاز مش موجود ← خروج.
 */
final readonly class StudentDeviceSession
{
    public const SESSION_KEY = 'storefront.device';

    public function __construct(private RecordStudentDeviceAction $devices) {}

    /** تسجيل الجهاز بعد الدخول/التسجيل — بيرمي `StorefrontException` لو الحد اتملى */
    public function register(Request $request, Student $student): void
    {
        $token = $this->token($request);
        $device = $this->devices->handle($student, $token, $request->userAgent(), $request->ip());

        $request->session()->put(self::SESSION_KEY, [
            'student_id' => (int) $student->getKey(),
            'device_id' => (int) $device->getKey(),
            'token' => $token,
        ]);

        Cookie::queue(Cookie::make(
            (string) config('storefront.devices.cookie', 'sf_device'),
            $token,
            (int) config('storefront.devices.cookie_minutes', 60 * 24 * 365 * 5),
        ));
    }

    /**
     * كل طلب في منطقة الطالب: `false` = الجهاز اتفصل (لازم يخرج).
     * جلسة من غير جهاز (دخول بـ «افتكرني» أو جلسة أقدم من الميزة) بتتسجّل
     * دلوقتي بنفس القواعد — وممكن ترمي `StorefrontException` لو الحد اتملى.
     */
    public function ensure(Request $request, Student $student): bool
    {
        $deviceId = $this->deviceIdFor($request, $student);

        if ($deviceId === null) {
            $this->register($request, $student);

            return true;
        }

        return $this->devices->stillConnected($student, $deviceId);
    }

    /** للـ Livewire: فحص بس من غير تسجيل ولا كتابة */
    public function disconnected(Request $request, Student $student): bool
    {
        if (! $request->hasSession()) {
            return false;
        }

        $deviceId = $this->deviceIdFor($request, $student);

        return $deviceId !== null && ! $this->devices->stillConnected($student, $deviceId, touch: false);
    }

    public function forget(Request $request): void
    {
        if ($request->hasSession()) {
            $entry = (array) $request->session()->get(self::SESSION_KEY, []);
            // التوكن بيفضل (نفس المتصفح) — رقم الجهاز والطالب بس اللي بيتشالوا
            $request->session()->put(self::SESSION_KEY, array_filter(['token' => $entry['token'] ?? null]));
        }
    }

    private function deviceIdFor(Request $request, Student $student): ?int
    {
        $entry = (array) $request->session()->get(self::SESSION_KEY, []);

        return isset($entry['device_id'], $entry['student_id']) && (int) $entry['student_id'] === (int) $student->getKey()
            ? (int) $entry['device_id']
            : null;
    }

    private function token(Request $request): string
    {
        $cookie = $request->cookie((string) config('storefront.devices.cookie', 'sf_device'));
        $session = ((array) $request->session()->get(self::SESSION_KEY, []))['token'] ?? null;

        foreach ([$cookie, $session] as $candidate) {
            if (is_string($candidate) && preg_match('/^[A-Za-z0-9]{40}$/', $candidate) === 1) {
                return $candidate;
            }
        }

        return Str::random(40);
    }
}
