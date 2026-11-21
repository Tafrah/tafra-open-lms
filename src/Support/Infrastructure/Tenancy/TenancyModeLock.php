<?php

declare(strict_types=1);

namespace Src\Support\Infrastructure\Tenancy;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Src\Support\Domain\Exceptions\TenancyModeMismatchException;

/**
 * قفل وضع تعدد المستأجرين عند أول نشر.
 *
 * ليه قفل؟ لو حد غيّر `TENANCY_MODE` في `.env` بالغلط بعد الإطلاق:
 * - من single لـ multi → كل مؤسسة بتفتح على قاعدة فاضية (أو مش موجودة)
 * - من multi لـ single → الاستعلامات بتروح للقاعدة المركزية
 * الاتنين صامتين. القفل بيحوّلهم لرفض تشغيل صريح (docs/03 بند ١).
 */
final class TenancyModeLock
{
    private ?string $memo = null;

    private bool $loaded = false;

    public function stored(): ?string
    {
        if ($this->loaded) {
            return $this->memo;
        }

        $this->loaded = true;

        try {
            $connection = $this->connection();

            if (! Schema::connection($connection)->hasTable($this->table())) {
                return $this->memo = null;
            }

            $value = DB::connection($connection)
                ->table($this->table())
                ->where('key', $this->key())
                ->value('value');

            return $this->memo = is_string($value) ? $value : null;
        } catch (\Throwable) {
            // قبل أول `migrate` مفيش جدول ولا اتصال — مفيش حاجة نقارن بيها
            return $this->memo = null;
        }
    }

    public function isLocked(): bool
    {
        return $this->stored() !== null;
    }

    public function lock(string $mode): void
    {
        DB::connection($this->connection())->table($this->table())->updateOrInsert(
            ['key' => $this->key()],
            ['value' => $mode, 'updated_at' => now(), 'created_at' => now()],
        );

        $this->memo = $mode;
        $this->loaded = true;
    }

    /** @throws TenancyModeMismatchException */
    public function assertMatches(): void
    {
        $stored = $this->stored();
        $configured = TenancyMode::current();

        if ($stored !== null && $stored !== $configured) {
            throw new TenancyModeMismatchException($stored, $configured);
        }
    }

    public function forget(): void
    {
        $this->memo = null;
        $this->loaded = false;
    }

    private function connection(): string
    {
        return (string) config('tenancy.database.central_connection');
    }

    private function table(): string
    {
        return (string) config('tenancy.lock.table', 'system_state');
    }

    private function key(): string
    {
        return (string) config('tenancy.lock.key', 'tenancy.mode');
    }
}
