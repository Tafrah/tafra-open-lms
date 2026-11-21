<?php

declare(strict_types=1);

namespace Src\Contexts\Transfer\Presentation\Console;

use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;

/** الأوامر بتشتغل على أكاديمية: `--academy=ID`، أو الوحيدة في وضع single. */
trait ResolvesAcademy
{
    private function enterAcademy(): ?Tenant
    {
        $id = $this->option('academy');
        $tenant = filled($id)
            ? Tenant::query()->find((int) $id)
            : Tenant::query()->orderBy('id')->first();

        if ($tenant === null) {
            $this->components->error(__('transfer::transfer.errors.no_academy'));

            return null;
        }

        app(TenantContext::class)->set($tenant->getKey());

        return $tenant;
    }
}
