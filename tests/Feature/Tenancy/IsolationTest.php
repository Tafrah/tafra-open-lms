<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Src\Contexts\Ai\Domain\Models\AiConnection;
use Src\Contexts\Content\Domain\Models\Announcement;
use Src\Contexts\Identity\Domain\Models\Role;
use Src\Contexts\Identity\Domain\Models\UserPreference;
use Src\Contexts\Notifications\Domain\Models\NotificationPreference;
use Src\Contexts\Settings\Domain\Models\TenantSetting;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;
use Src\Support\Domain\Exceptions\CrossTenantQueryException;
use Src\Support\Domain\Exceptions\MissingTenantContextException;
use Src\Support\Infrastructure\Persistence\Concerns\BelongsToTenant;
use Src\Support\Infrastructure\Tenancy\TenancyMode;

it('لا يسرّب بيانات بين المؤسسات', function (): void {
    $a = Tenant::factory()->create();
    $b = Tenant::factory()->create();

    $context = app(TenantContext::class);

    $context->set($a->getKey());
    Announcement::factory()->count(2)->create();

    $context->set($b->getKey());
    Announcement::factory()->count(5)->create();

    $context->set($a->getKey());
    expect(Announcement::count())->toBe(2);

    $context->set($b->getKey());
    expect(Announcement::count())->toBe(5);
});

it('يرفض إنشاء سجل بدون سياق مؤسسة', function (): void {
    app(TenantContext::class)->set(null);

    expect(fn () => Announcement::factory()->create())
        ->toThrow(MissingTenantContextException::class);
});

it('يرفض الاستعلام بدون سياق مؤسسة بدل إرجاع كل الصفوف', function (): void {
    $tenant = Tenant::factory()->create();
    app(TenantContext::class)->set($tenant->getKey());
    Announcement::factory()->count(3)->create();

    app(TenantContext::class)->set(null);

    expect(fn () => Announcement::count())->toThrow(MissingTenantContextException::class);
});

it('لا يسرّب أدوار بين المؤسسات في نفس الطلب', function (): void {
    $a = Tenant::factory()->create();
    $b = Tenant::factory()->create();

    $user = userWithRole('admin', $a);
    $user->tenants()->syncWithoutDetaching([$b->getKey() => ['joined_at' => now()]]);

    app(TenantContext::class)->set($b->getKey());

    expect($user->fresh()->hasRole('admin'))->toBeFalse();
});

it('كل موديل فيه tenant_id عليه الـ trait', function (): void {
    $violations = [];

    foreach (allDomainModels() as $model) {
        /** @var Model $instance */
        $instance = new $model;

        if (! Schema::hasColumn($instance->getTable(), 'tenant_id')) {
            continue;
        }

        // الموديلات اللي `tenant_id` فيها nullable ومش مملوكة لمؤسسة
        // (تفضيلات، إعدادات مؤسسة) مستثناة بوعي وموثّقة في الكلاس نفسه.
        if (in_array($model, [
            // spatie/permission بيضيف tenant_id على جدول الأدوار عشان الأدوار
            // الخاصة بمؤسسة — بس تعريفاتنا كلها عامة (tenant_id = null)،
            // والعزل بيحصل على الـ pivot مش على التعريف.
            Role::class,
            TenantSetting::class,
            NotificationPreference::class,
            UserPreference::class,
            // ربط تطبيق AI: بيتقرا قبل ما نعرف المؤسسة (هو اللي بيحددها)،
            // و`null` = اللوحة المركزية. العزل بالمالك وبالـ Policy (docs/23 بند ٤)
            AiConnection::class,
        ], true)) {
            continue;
        }

        if (! in_array(BelongsToTenant::class, class_uses_recursive($model), true)) {
            $violations[] = $model;
        }
    }

    expect($violations)->toBeEmpty('موديلات ناقصها BelongsToTenant: '.implode(', ', $violations));
});

it('يعزل كاش الصلاحيات بين المؤسسات', function (): void {
    $a = Tenant::factory()->create();
    $b = Tenant::factory()->create();

    $context = app(TenantContext::class);

    $context->set($a->getKey());
    $prefixA = config('cache.prefix');

    $context->set($b->getKey());
    $prefixB = config('cache.prefix');

    expect($prefixA)->not->toBe($prefixB)
        ->and($prefixA)->toContain((string) $a->getKey())
        ->and($prefixB)->toContain((string) $b->getKey());
});

it('يستطيع تجاوز الـ scope صراحةً للأوامر الإدارية', function (): void {
    $a = Tenant::factory()->create();
    $b = Tenant::factory()->create();

    $context = app(TenantContext::class);

    $context->set($a->getKey());
    Announcement::factory()->count(2)->create();

    $context->set($b->getKey());
    Announcement::factory()->count(3)->create();

    $context->set(null);

    $total = $context->withoutScope(fn (): int => Announcement::count());

    expect($total)->toBe(5);
})->skip(fn (): bool => TenancyMode::isMulti(), 'في multi كل مؤسسة في قاعدة — الاختبار اللي بعده بيغطّي السلوك');

/*
 * في multi، الصفوف في قواعد مختلفة: تجاوز الـ scope مايقدرش يجمعها، ولو
 * سيبناه يعدّي كان هيستعلم على القاعدة المركزية ويرجّع صفر بصمت.
 * الرفض الصريح + `forEachTenant()` هو الطريق الوحيد (docs/03 بند ٣).
 */
it('في وضع قاعدة لكل مؤسسة بيرفض تجاوز الـ scope ويجمع عبر forEachTenant', function (): void {
    $a = Tenant::factory()->create();
    $b = Tenant::factory()->create();

    $context = app(TenantContext::class);

    $context->set($a->getKey());
    Announcement::factory()->count(2)->create();

    $context->set($b->getKey());
    Announcement::factory()->count(3)->create();

    $context->set(null);

    expect(fn (): int => $context->withoutScope(fn (): int => Announcement::count()))
        ->toThrow(CrossTenantQueryException::class);

    $total = 0;
    $context->forEachTenant(function () use (&$total): void {
        $total += Announcement::count();
    });

    expect($total)->toBe(5)
        ->and($context->id())->toBeNull();
})->skip(fn (): bool => ! TenancyMode::isMulti(), 'خاص بوضع multi');
