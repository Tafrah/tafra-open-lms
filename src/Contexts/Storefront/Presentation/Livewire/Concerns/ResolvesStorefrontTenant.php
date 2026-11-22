<?php

declare(strict_types=1);

namespace Src\Contexts\Storefront\Presentation\Livewire\Concerns;

use Illuminate\Support\Facades\Auth;
use Src\Contexts\Storefront\Presentation\Support\StorefrontTenantResolver;
use Src\Contexts\Storefront\Presentation\Support\StudentDeviceSession;
use Src\Contexts\Students\Domain\Models\Student;

/**
 * مكوّنات الموقع بتحدد المؤسسة بنفسها في كل طلب Livewire.
 *
 * ليه مش persistent middleware؟ لوحات Filament هنا معلّمة ستاك `web` كله
 * (EncryptCookies/StartSession) كـ persistent عالميًا، فإعادة تشغيله على
 * طلب Livewire من صفحات الموقع بتفك تشفير كوكي متفكّكة أصلًا ← جلسة جديدة
 * ← الطالب بيتطلع برا. فالمكوّنات دي بتطلع من آلية الـ persistent middleware
 * (`StorefrontServiceProvider`) وبتعمل الخطوتين المهمين هنا.
 */
trait ResolvesStorefrontTenant
{
    public function bootResolvesStorefrontTenant(): void
    {
        $resolver = app(StorefrontTenantResolver::class);
        $tenant = $resolver->resolve(request());

        abort_if($tenant === null, 503);

        $resolver->apply($tenant);

        $student = Auth::guard('student')->user();

        // حساب اتوقف وهو داخل — نفس `AuthenticateStudent`
        abort_if($student instanceof Student && $student->isBlocked(), 403);

        // جهاز اتفصل من اللوحة — نفس `AuthenticateStudent` (الصفحة الجاية هتطلّعه برا)
        abort_if($student instanceof Student && app(StudentDeviceSession::class)->disconnected(request(), $student), 403);
    }
}
