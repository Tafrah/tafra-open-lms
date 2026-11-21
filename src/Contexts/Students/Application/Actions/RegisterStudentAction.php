<?php

declare(strict_types=1);

namespace Src\Contexts\Students\Application\Actions;

use Src\Contexts\Students\Domain\Enums\StudentStatus;
use Src\Contexts\Students\Domain\Exceptions\StudentsException;
use Src\Contexts\Students\Domain\Models\Student;

/**
 * تسجيل طالب جديد من الموقع (أو من اللوحة). كود الطالب بيتولّد تلقائي.
 */
final readonly class RegisterStudentAction
{
    /** @param array<string, mixed> $data */
    public function handle(array $data): Student
    {
        $phone = self::normalizePhone((string) $data['phone']);

        throw_if(Student::query()->withTrashed()->where('phone', $phone)->exists(), StudentsException::because('phone_taken'));

        return Student::query()->create([
            'name' => trim((string) $data['name']),
            'phone' => $phone,
            'parent_phone' => filled($data['parent_phone'] ?? null) ? self::normalizePhone((string) $data['parent_phone']) : null,
            'email' => filled($data['email'] ?? null) ? mb_strtolower(trim((string) $data['email'])) : null,
            'password' => (string) $data['password'],
            'code' => $this->code(),
            'educational_level_id' => $data['educational_level_id'] ?? null,
            'governorate' => $data['governorate'] ?? null,
            'school_type' => $data['school_type'] ?? null,
            'status' => StudentStatus::Active,
        ]);
    }

    /** أرقام عربية/فارسية ← إنجليزي، وشيل المسافات و+20 */
    public static function normalizePhone(string $phone): string
    {
        $phone = strtr($phone, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]);
        $phone = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($phone, '0020')) {
            $phone = '0'.substr($phone, 4);
        } elseif (str_starts_with($phone, '20') && strlen($phone) === 12) {
            $phone = '0'.substr($phone, 2);
        }

        return $phone;
    }

    private function code(): string
    {
        do {
            $code = (string) random_int(100000, 999999);
        } while (Student::query()->withTrashed()->where('code', $code)->exists());

        return $code;
    }
}
