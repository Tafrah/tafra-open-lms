<?php

declare(strict_types=1);

namespace Src\Contexts\Settings\Domain\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * إعداد خاص بمؤسسة واحدة. الجدول ده الطبقة التانية فوق
 * spatie/laravel-settings العامة (docs/05 بند ٤).
 *
 * الموديل ده **مش** بيستخدم BelongsToTenant عن قصد: القراءة بتحصل
 * بمفتاح مستأجر صريح من TenantSettings، وبتحتاج تشتغل كمان
 * في سياق إداري بيقرا لكل المستأجرين.
 */
final class TenantSetting extends Model
{
    /** @var list<string> */
    protected $fillable = ['tenant_id', 'group', 'key', 'value'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['value' => 'array'];
    }
}
