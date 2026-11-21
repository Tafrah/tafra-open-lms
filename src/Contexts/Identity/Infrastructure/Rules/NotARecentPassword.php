<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Infrastructure\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Hash;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Identity\Domain\Models\UserPasswordHistory;
use Src\Contexts\Settings\Domain\Settings\SecuritySettings;

/** بيمنع إعادة استخدام آخر N كلمة مرور (docs/12 بند ٤). */
final readonly class NotARecentPassword implements ValidationRule
{
    public function __construct(
        private ?User $user,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->user === null || ! is_string($value) || $value === '') {
            return;
        }

        $keep = $this->historySize();

        if ($keep <= 0) {
            return;
        }

        $hashes = UserPasswordHistory::query()
            ->where('user_id', $this->user->getKey())
            ->orderByDesc('id')
            ->take($keep)
            ->pluck('password')
            ->push($this->user->password)
            ->filter()
            ->all();

        foreach ($hashes as $hash) {
            if (Hash::check($value, (string) $hash)) {
                $fail(__('identity::identity.password.validation.recently_used', ['count' => fc_number($keep)]));

                return;
            }
        }
    }

    private function historySize(): int
    {
        try {
            return app(SecuritySettings::class)->password_history_size;
        } catch (\Throwable) {
            return (int) config('security.password.history_size', 5);
        }
    }
}
