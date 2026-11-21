<?php

declare(strict_types=1);

namespace Src\Support\Domain\Authorization;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

/**
 * سلسلة فحص تفويض. بتقف عند أول رفض وبترجّع سببه.
 * الترتيب مقصود: الصلاحية الأول، بعدين قواعد الأعمال.
 */
final class Decision
{
    private ?Response $denial = null;

    /**
     * الفحص الأول دايماً: هل معاه الصلاحية أصلاً؟
     *
     * الرفض هنا **بدون رسالة** عن قصد — Filament بيخفي الإجراء بالكامل،
     * لأن اللي مالوش الصلاحية مالوش دعوة يعرف إن الميزة موجودة.
     * الرفض بسبب قاعدة أعمال (rule) بيرجّع رسالة → الإجراء بيظهر معطّل بالسبب.
     * راجع docs/19-policies.md بند ٨.
     */
    public function permission(
        Authenticatable $user,
        Policy $policy,
        string $action,
    ): self {
        if ($this->denial !== null) {
            return $this;
        }

        $permission = $policy->permissionFor($action);

        if (! $this->userHasPermission($user, $permission)) {
            $this->denial = Response::deny();
        }

        return $this;
    }

    /**
     * فحص «المنطقة»: قدرة صفحة عامة لازم تعدّي قبل أي قدرة على المورد.
     * الرفض بدون رسالة — المنطقة كلها بتختفي زي الصلاحية الناقصة.
     */
    public function area(Authenticatable $user, Policy $policy): self
    {
        if ($this->denial !== null) {
            return $this;
        }

        $area = $policy->area();

        if ($area !== null && ! Gate::forUser($user)->allows($area)) {
            $this->denial = Response::deny();
        }

        return $this;
    }

    /**
     * قاعدة أعمال. الرسالة بتوصل للمستخدم فعلاً — خليها مفيدة.
     *
     * @param array<string, string|int|float> $replace
     */
    public function rule(bool $passes, string $messageKey, array $replace = []): self
    {
        if ($this->denial !== null || $passes) {
            return $this;
        }

        $this->denial = Response::deny(__("authorization.denied.{$messageKey}", $replace));

        return $this;
    }

    /**
     * نفس rule() بس بتقييم كسول — للفحوصات اللي فيها استعلام.
     *
     * @param array<string, string|int|float> $replace
     */
    public function ruleUsing(callable $passes, string $messageKey, array $replace = []): self
    {
        if ($this->denial !== null) {
            return $this;
        }

        return $this->rule((bool) $passes(), $messageKey, $replace);
    }

    /**
     * رفض بإخفاء وجود السجل (404 بدل 403).
     * استخدمها لما مجرد معرفة إن السجل موجود يعتبر تسريب.
     *
     * @param array<string, string|int|float> $replace
     */
    public function ruleOrNotFound(bool $passes, string $messageKey, array $replace = []): self
    {
        if ($this->denial !== null || $passes) {
            return $this;
        }

        $this->denial = Response::denyAsNotFound(__("authorization.denied.{$messageKey}", $replace));

        return $this;
    }

    public function response(): Response
    {
        return $this->denial ?? Response::allow();
    }

    /**
     * هل معاه الصلاحية اللي ورا القدرة دي **أصلاً** — المنطقة والصلاحية بس،
     * من غير قواعد الأعمال اللي محتاجة سجل.
     *
     * ده نفس اللي بيخلّي Filament يخفي الزرار (رفض بدون رسالة)، ومستخدم
     * لإظهار أدوات MCP بنفس المنطق (`shouldRegister()`). مش أمان: الأداة
     * نفسها بتسأل الـ Policy كاملة على السجل قبل التنفيذ. اسم الصلاحية من
     * اسم القدرة بالاصطلاح: `forceDelete` ← `force_delete.{resource}` — وقدرة
     * بتسأل عن صلاحية باسم تاني (`activate` ← `suspend`) بتبعته في `$action`.
     */
    public static function holds(Authenticatable $user, Policy $policy, string $ability, ?string $action = null): bool
    {
        $decision = (new self)
            ->area($user, $policy)
            ->permission($user, $policy, $action ?? Str::snake($ability));

        return $decision->denial === null;
    }

    /**
     * الفحص المباشر الوحيد المسموح في التطبيق كله.
     *
     * صلاحية مش متسجّلة في قاعدة البيانات = رفض، مش استثناء —
     * عشان صلاحية جديدة في الكونفيج قبل ما `authorization:sync` يتنفّذ
     * ما تكسرش اللوحة كلها.
     */
    private function userHasPermission(Authenticatable $user, string $permission): bool
    {
        if (! method_exists($user, 'hasPermissionTo')) {
            return false;
        }

        // المدير العام معاه كل الصلاحيات على الفريق المركزي — مهما كان
        // الفريق الحالي. قواعد الأعمال بعدها بتتقيّم عادي.
        if (app(CentralAuthority::class)->isSuperAdmin($user)) {
            return true;
        }

        try {
            return (bool) $user->hasPermissionTo($permission, config('authorization.guard'));
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }
}
