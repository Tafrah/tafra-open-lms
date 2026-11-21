<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Domain\Models;

use Spatie\Permission\Models\Role as SpatieRole;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * الأدوار **عامة** (tenant_id = null) — التعريف واحد لكل التثبيت،
 * والربط بالمستخدم هو اللي بيتحدد بالمستأجر (docs/03 بند ٣).
 */
final class Role extends SpatieRole
{
    use CentralConnection;

    public function label(): string
    {
        $key = "authorization.roles.{$this->name}";
        $translated = __($key);

        return $translated === $key ? $this->name : $translated;
    }

    /**
     * محمي = مصدره الكونفيج: الأدوار الافتراضية عامة لكل المؤسسات، وتعديلها من
     * لوحة مؤسسة كان بيغيّر صلاحيات كل المؤسسات (و`authorization:sync` بيرجّعها).
     */
    public function isProtected(): bool
    {
        $configOwned = [
            ...(array) config('authorization.protected_roles', []),
            ...array_keys((array) config('authorization.roles', [])),
            ...array_keys((array) config('authorization.central_roles', [])),
        ];

        return in_array($this->name, $configOwned, true);
    }
}
