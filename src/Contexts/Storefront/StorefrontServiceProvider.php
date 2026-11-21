<?php

declare(strict_types=1);

namespace Src\Contexts\Storefront;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\View\View as ViewInstance;
use Livewire\Livewire;
use Livewire\Mechanisms\HandleComponents\ComponentContext;

use function Livewire\on;

use Src\Contexts\Storefront\Application\Queries\CourseCatalog;
use Src\Contexts\Storefront\Presentation\Livewire\ExamRunner;
use Src\Contexts\Storefront\Presentation\Livewire\PurchaseCard;
use Src\Contexts\Storefront\Presentation\Livewire\RedeemCode;
use Src\Support\Infrastructure\Providers\ContextServiceProvider;

/**
 * الموقع العام للطلبة: الواجهة، الدخول، الكورسات، الدفع، المشغّل، الامتحانات.
 * في اللوحة: تبويب «إعدادات الموقع» جوه إعدادات المؤسسة بس.
 */
final class StorefrontServiceProvider extends ContextServiceProvider
{
    /** route من غير أي ميدلوير — بيتشاور عليه في memo مكوّنات Livewire بتاعتنا بس */
    public const LIVEWIRE_CONTEXT_PATH = '_storefront/livewire-context';

    protected function name(): string
    {
        return 'storefront';
    }

    protected function path(): string
    {
        return __DIR__;
    }

    /** @return list<string> */
    protected function panelIds(): array
    {
        return ['admin'];
    }

    protected function bootContext(): void
    {
        RateLimiter::for('storefront-forms', static fn (Request $request): Limit => Limit::perMinute(
            (int) config('storefront.limits.forms_per_minute', 30),
        )->by('storefront-forms:'.$request->ip()));

        // الصفوف في قايمة الناف بار — بس لو المؤسسة اتحددت (الميدلوير قبلها)
        View::composer('storefront::partials.navbar', static function (ViewInstance $view): void {
            $view->with('navLevels', app(CourseCatalog::class)->levels());
        });

        Livewire::component('storefront.purchase-card', PurchaseCard::class);
        Livewire::component('storefront.redeem-code', RedeemCode::class);
        Livewire::component('storefront.exam-runner', ExamRunner::class);

        /*
         * مكوّنات الموقع مابتعيدش ستاك `web` في طلبات Livewire.
         *
         * لوحات Filament (AdminPanelProvider/CentralPanelProvider) بتسجّل
         * EncryptCookies وStartSession كـ «persistent middleware» على مستوى
         * Livewire كله. صفحاتنا على ستاك `web` اللي فيه نفس الكلاسات، فـ Livewire
         * كان بيعيد تشغيلهم على `/livewire/update`: الكوكي اتفك تشفيرها مرة،
         * فالمرة التانية بتفشل ← جلسة جديدة مابتتحفظش ← الطالب بيطلع برا بعد
         * أول ضغطة. فبنوجّه الـ memo لـ route فاضي من غير ميدلوير، والمكوّن
         * بيحدد المؤسسة ويتأكد من الطالب بنفسه (`ResolvesStorefrontTenant`).
         */
        on('dehydrate', static function (object $component, ComponentContext $context): void {
            if (str_starts_with($component::class, 'Src\\Contexts\\Storefront\\')) {
                $context->addMemo('path', self::LIVEWIRE_CONTEXT_PATH);
                $context->addMemo('method', 'GET');
            }
        });
    }
}
