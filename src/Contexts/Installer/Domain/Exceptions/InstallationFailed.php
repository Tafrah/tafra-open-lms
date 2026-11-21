<?php

declare(strict_types=1);

namespace Src\Contexts\Installer\Domain\Exceptions;

use RuntimeException;
use Throwable;

/** خطوة من خطوات التثبيت فشلت — `step` بيقول أنهي واحدة. */
final class InstallationFailed extends RuntimeException
{
    public function __construct(
        public readonly string $step,
        public readonly string $details = '',
        ?Throwable $previous = null,
    ) {
        parent::__construct((string) __('installer::installer.errors.step_failed', [
            'step' => __('installer::installer.steps.'.$step),
        ]), 0, $previous);
    }
}
