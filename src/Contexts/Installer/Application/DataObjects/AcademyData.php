<?php

declare(strict_types=1);

namespace Src\Contexts\Installer\Application\DataObjects;

use Illuminate\Validation\Rules\Password;

/** المنصة وصاحبها — المدرّس هو المدير وهو مالك التثبيت. */
final readonly class AcademyData
{
    public function __construct(
        public string $platformName,
        public string $teacherName,
        public string $subject,
        public string $adminEmail,
        public ?string $adminPhone,
        #[\SensitiveParameter]
        public string $adminPassword,
    ) {}

    /**
     * ⚠️ مش `Password::defaults()`: في الإنتاج بيضيف `uncompromised()` —
     * طلب لـ HaveIBeenPwned من جوه المثبّت، وكتير من الاستضافات قافلة الخروج.
     *
     * `$headless`: كلمة السر جاية من سكربت (Coolify `SERVICE_PASSWORD_*`،
     * `install.sh`) — عشوائية وطويلة، بس مش مضمون فيها رقم وحرف كبير. شرط
     * الطول بس هنا، وإلا التثبيت كان بيفشل مرة كل كام نشر.
     *
     * @return array<string, list<mixed>>
     */
    public static function rules(bool $confirmPassword = true, bool $headless = false): array
    {
        $password = $headless
            ? ['required', 'string', 'min:12', 'max:255']
            : ['required', 'string', 'max:255', Password::min(8)->letters()->mixedCase()->numbers()];

        if ($confirmPassword) {
            $password[] = 'confirmed';
        }

        return [
            'platform_name' => ['required', 'string', 'max:120'],
            'teacher_name' => ['required', 'string', 'max:120'],
            'subject' => ['required', 'string', 'max:120'],
            'admin_email' => ['required', 'string', 'email:rfc', 'max:255'],
            'admin_phone' => ['nullable', 'string', 'regex:/^\+?[0-9][0-9\s\-]{6,19}$/'],
            'admin_password' => $password,
        ];
    }

    /** @param array<string, mixed> $input */
    public static function fromArray(array $input): self
    {
        $phone = trim((string) ($input['admin_phone'] ?? ''));

        return new self(
            platformName: trim((string) ($input['platform_name'] ?? '')),
            teacherName: trim((string) ($input['teacher_name'] ?? '')),
            subject: trim((string) ($input['subject'] ?? '')),
            adminEmail: mb_strtolower(trim((string) ($input['admin_email'] ?? ''))),
            adminPhone: $phone !== '' ? $phone : null,
            adminPassword: (string) ($input['admin_password'] ?? ''),
        );
    }

    /** @return array<string, mixed> */
    public function __debugInfo(): array
    {
        return [
            'platformName' => $this->platformName,
            'teacherName' => $this->teacherName,
            'subject' => $this->subject,
            'adminEmail' => $this->adminEmail,
            'adminPhone' => $this->adminPhone,
            'adminPassword' => '********',
        ];
    }
}
