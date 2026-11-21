<?php

declare(strict_types=1);

namespace Src\Support\Presentation\Mcp;

/**
 * العمليات القياسية على سجل — كل واحدة بتتحوّل لأداة واحدة، وكل أداة
 * بتسأل قدرة Policy واحدة (نفس اللي زرار Filament بيسألها).
 */
enum RecordOperation: string
{
    case List = 'list';
    case Show = 'get';
    case Create = 'create';
    case Update = 'update';
    case Delete = 'delete';
    case Restore = 'restore';
    case ForceDelete = 'force_delete';

    /** قدرة الـ Policy اللي العملية بتسألها */
    public function ability(): string
    {
        return match ($this) {
            self::List => 'viewAny',
            self::Show => 'view',
            self::Create => 'create',
            self::Update => 'update',
            self::Delete => 'delete',
            self::Restore => 'restore',
            self::ForceDelete => 'forceDelete',
        };
    }

    public function writes(): bool
    {
        return ! in_array($this, [self::List, self::Show], true);
    }

    /** بتغيّر أو بتمسح بيانات موجودة — العميل لازم يطلب تأكيد بشري. */
    public function destructive(): bool
    {
        return in_array($this, [self::Update, self::Delete, self::ForceDelete], true);
    }

    /** تكرارها بنفس المدخلات مالوش أثر زيادة. */
    public function idempotent(): bool
    {
        return in_array($this, [self::List, self::Show, self::Update, self::Restore], true);
    }

    public function needsRecord(): bool
    {
        return ! in_array($this, [self::List, self::Create], true);
    }
}
