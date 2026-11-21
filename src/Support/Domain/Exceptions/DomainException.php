<?php

declare(strict_types=1);

namespace Src\Support\Domain\Exceptions;

use RuntimeException;

/** الأساس لكل استثناءات الدومين — بتحمل رسالة مترجمة للمستخدم. */
abstract class DomainException extends RuntimeException {}
