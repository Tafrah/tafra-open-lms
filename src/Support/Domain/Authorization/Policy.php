<?php

declare(strict_types=1);

namespace Src\Support\Domain\Authorization;

/**
 * الكلاس الأساسي لكل السياسات.
 *
 * المكان الوحيد — مع Decision والـ Gates — اللي مسموح فيه فحص صلاحية مباشر.
 * راجع docs/19-policies.md.
 */
abstract class Policy
{
    /** اسم المورد كما هو في config/authorization.php */
    abstract protected function resource(): string;

    /**
     * قدرات لا يتجاوزها المدير العام.
     *
     * دي مش صلاحيات — دي قواعد سلامة. مثال: «مينفعش تحذف نفسك»
     * لازم تفضل شغالة حتى للـ super_admin، وإلا هيقفل على نفسه.
     *
     * @return list<string>
     */
    public function invariants(): array
    {
        return [];
    }

    /**
     * قدرة «منطقة» مطلوبة قبل أي قدرة تانية على المورد ده.
     *
     * مثال: كل قدرات المؤسسات محتاجة `access.tenancy` — فمستخدم معاه
     * `view_any.tenants` بس (من نمط `view_any.*`) مايوصلش للشاشة.
     * الرفض هنا **بدون رسالة** — المنطقة كلها بتختفي.
     */
    public function area(): ?string
    {
        return null;
    }

    public function isInvariant(string $ability): bool
    {
        return in_array($ability, $this->invariants(), true);
    }

    /** الاسم العام للمورد — بيتستخدم في الاختبارات وفي بناء أسماء الصلاحيات */
    public function resourceName(): string
    {
        return $this->resource();
    }

    /** يبدأ سلسلة فحص جديدة */
    protected function decide(): Decision
    {
        return new Decision;
    }

    /** يبني اسم الصلاحية الكامل: publish + announcements → publish.announcements */
    public function permissionFor(string $action): string
    {
        return $action.config('authorization.separator').$this->resource();
    }
}
