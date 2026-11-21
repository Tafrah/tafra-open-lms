<?php

declare(strict_types=1);

namespace Src\Contexts\Installer\Domain\Exceptions;

use RuntimeException;
use Throwable;

/**
 * فشل الاتصال بالقاعدة، بسبب مفهوم لصاحب المنصة.
 *
 * `reason` مفتاح ترجمة تحت `installer::installer.database.errors` —
 * الرسالة الخام من PDO بتتعرض كتفاصيل بس، ومفيهاش كلمة السر أبداً.
 */
final class DatabaseConnectionFailed extends RuntimeException
{
    public function __construct(
        public readonly string $reason,
        public readonly string $details = '',
        ?Throwable $previous = null,
    ) {
        parent::__construct((string) __('installer::installer.database.errors.'.$reason), 0, $previous);
    }
}
