<?php

declare(strict_types=1);

namespace Src\Contexts\Installer\Application\DataObjects;

/**
 * كل اللي التثبيت محتاجه.
 *
 * `database = null` معناه «استخدم الاتصال الحالي» — Docker/Coolify بيدّي
 * القيم كمتغيرات بيئة، ومفيش `.env` يتكتب (`writeEnvironment = false`).
 */
final readonly class InstallationData
{
    public function __construct(
        public AcademyData $academy,
        public string $appUrl,
        public ?DatabaseCredentials $database = null,
        public bool $writeEnvironment = true,
        public bool $secureCookies = false,
    ) {}
}
