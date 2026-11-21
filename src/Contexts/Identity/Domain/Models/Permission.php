<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Domain\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

final class Permission extends SpatiePermission
{
    use CentralConnection;

    public function label(): string
    {
        return permission_label($this->name);
    }
}
