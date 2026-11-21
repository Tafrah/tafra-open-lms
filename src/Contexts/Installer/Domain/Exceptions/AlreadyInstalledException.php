<?php

declare(strict_types=1);

namespace Src\Contexts\Installer\Domain\Exceptions;

use RuntimeException;

/** التثبيت مابيتعادش — حتى لو حد بعت الطلب بإيده. */
final class AlreadyInstalledException extends RuntimeException
{
    public static function make(): self
    {
        return new self((string) __('installer::installer.errors.already_installed'));
    }
}
