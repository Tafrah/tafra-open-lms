<?php

declare(strict_types=1);

namespace Src\Support\Domain\Authorization;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * هل المستخدم ده مدير عام على الفريق المركزي؟ (docs/24 بند ٢)
 *
 * `Decision` محتاج السؤال ده: القدرات المحمية بـ invariants() بتعدّي
 * `Gate::before` وبتوصل للـ Policy — والمدير العام مالوش أدوار على
 * فريق المؤسسة، فخطوة `permission()` كانت هترفضه قبل ما قاعدة
 * السلامة نفسها تتقيّم.
 */
interface CentralAuthority
{
    public function isSuperAdmin(Authenticatable $user): bool;
}
