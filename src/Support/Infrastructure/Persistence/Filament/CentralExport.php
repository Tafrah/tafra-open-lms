<?php

declare(strict_types=1);

namespace Src\Support\Infrastructure\Persistence\Filament;

use Filament\Actions\Exports\Models\Export;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * موديل التصدير بتاع Filament على الاتصال المركزي.
 *
 * جدول `exports` مركزي (مربوط بـ `users`)، والموديل الأصلي بياخد الاتصال
 * الافتراضي — واللي في وضع multi بيبقى قاعدة المؤسسة وقت ما المستخدم
 * بيضغط «تصدير» من جوه اللوحة (docs/03 بند ٣).
 *
 * مش في `Domain/Models`: ده مش موديل دومين، ده تثبيت اتصال لموديل باكدج.
 * Filament بيعمله `app(Export::class)` — فالربط في AppServiceProvider بيغطّي
 * كل المسارات: الإنشاء، والـ Job، ورابط التنزيل (route model binding).
 */
final class CentralExport extends Export
{
    use CentralConnection;

    // اسم الجدول بيتشتق من اسم الكلاس (`central_exports`) — لازم يتثبت على جدول Filament الأصلي
    protected $table = 'exports';
}
