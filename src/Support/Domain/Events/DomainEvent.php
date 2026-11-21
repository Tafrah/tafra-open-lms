<?php

declare(strict_types=1);

namespace Src\Support\Domain\Events;

/**
 * الأساس لكل أحداث الدومين.
 *
 * الأحداث هي وسيلة التواصل الوحيدة (مع الـ Application API العامة)
 * بين السياقات — راجع docs/01-architecture-ddd.md.
 */
abstract readonly class DomainEvent
{
    public function __construct(public ?int $tenantId = null) {}
}
