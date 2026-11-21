<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Application\DataObjects;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final readonly class InviteUserData
{
    /**
     * كلمة مرور المدعو.
     *
     * مش قابلة لأن تكون فاضية عن قصد: المدعو هيحطّ بتاعته من رابط
     * الدعوة، لكن الصف لازم يتكتب بقيمة صالحة. لو مجاش هاش من الفورم
     * بنولّد واحدة عشوائية طويلة — محدش بيعرفها ومحدش محتاجها.
     *
     * لو الهاش جه من الفورم، الـ `hashed` cast بيعديه زي ما هو
     * (مابيعيدش تشفيره) — متحقّق منه.
     */
    public string $password;

    public function __construct(
        public string $name,
        public string $email,
        public int $tenantId,
        public string $role,
        public ?string $phone = null,
        public ?string $locale = null,
        ?string $password = null,
    ) {
        $this->password = $password ?? Hash::make(Str::password(32));
    }

    /** @param  array<string, mixed>  $data */
    public static function fromArray(array $data, int $tenantId): self
    {
        return new self(
            name: (string) $data['name'],
            email: (string) $data['email'],
            tenantId: $tenantId,
            role: (string) ($data['role'] ?? 'viewer'),
            phone: $data['phone'] ?? null,
            locale: $data['locale'] ?? null,
            password: $data['password'] ?? null,
        );
    }
}
