<?php

declare(strict_types=1);

namespace Src\Contexts\Installer\Application\DataObjects;

use Src\Contexts\Installer\Domain\Enums\MediaServing;

final readonly class InstallationResult
{
    public function __construct(
        public int $tenantId,
        public string $tenantSlug,
        public int $adminId,
        public MediaServing $mediaServing,
        public string $version,
    ) {}
}
