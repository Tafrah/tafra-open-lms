<?php

declare(strict_types=1);

namespace Src\Contexts\Ai\Application\DataObjects;

use Src\Contexts\Ai\Domain\Enums\AiAccessLevel;

final readonly class AuthorizeAiConnectionData
{
    public function __construct(
        public string $clientId,
        /** `null` = اللوحة المركزية */
        public ?int $tenantId,
        public AiAccessLevel $access = AiAccessLevel::Read,
    ) {}
}
