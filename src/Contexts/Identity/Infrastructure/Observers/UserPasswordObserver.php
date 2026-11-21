<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Infrastructure\Observers;

use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Identity\Domain\Models\UserPasswordHistory;
use Src\Contexts\Identity\Infrastructure\Notifications\PasswordChangedNotification;
use Src\Contexts\Settings\Domain\Settings\SecuritySettings;

/**
 * تغيير كلمة المرور = حدث أمني.
 *
 * بيعمل تلاتة في مكان واحد: يسجّل الهاش القديم لمنع إعادة الاستخدام،
 * يحدّث `password_changed_at`، ويبعت تنبيه إجباري (docs/12 بند ٤).
 */
final class UserPasswordObserver
{
    public function updating(User $user): void
    {
        if (! $user->isDirty('password')) {
            return;
        }

        $previous = $user->getOriginal('password');

        if (is_string($previous) && $previous !== '') {
            UserPasswordHistory::query()->create([
                'user_id' => $user->getKey(),
                'password' => $previous,
            ]);
        }

        $user->password_changed_at = now();

        $this->prune($user);
    }

    public function updated(User $user): void
    {
        if (! $user->wasChanged('password')) {
            return;
        }

        $user->notify(new PasswordChangedNotification);
    }

    /** بنحتفظ بالعدد المحدد في الإعدادات بس — الباقي مالوش لازمة. */
    private function prune(User $user): void
    {
        $keep = $this->historySize();

        if ($keep <= 0) {
            UserPasswordHistory::query()->where('user_id', $user->getKey())->delete();

            return;
        }

        $obsolete = UserPasswordHistory::query()
            ->where('user_id', $user->getKey())
            ->orderByDesc('id')
            ->skip($keep)
            ->take(100)
            ->pluck('id');

        if ($obsolete->isNotEmpty()) {
            UserPasswordHistory::query()->whereIn('id', $obsolete)->delete();
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
