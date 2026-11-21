<?php

declare(strict_types=1);

namespace Src\Contexts\Installer\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Src\Contexts\Installer\Infrastructure\InstallationState;
use Symfony\Component\HttpFoundation\Response;

/**
 * قبل التثبيت: أي طلب بيروح لـ `/install`.
 *
 * ميدلوير عام (أول واحد) — قبل الجلسة والكوكيز، اللي ممكن يكونوا لسه
 * على قاعدة مش موجودة، وقبل ستاك لوحات Filament (مابيعدّيش على `web`).
 * الأصول ملفات ثابتة والسيرفر بيقدّمها قبل Laravel. المستثنى: المثبّت
 * نفسه، فحص الصحة (`/up`، `/health`)، والملفات العامة (`/uploads`).
 *
 * التحويل نسبي (`/install`): في الإنتاج `URL::forceScheme('https')`،
 * واستضافة لسه من غير شهادة كانت هتتحوّل لعنوان مابيفتحش.
 */
final readonly class RedirectToInstaller
{
    public function __construct(private InstallationState $state) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->state->isInstalled() || $request->is('install', 'install/*', 'up', 'health', config('installer.public_files.prefix', 'uploads').'/*')) {
            return $next($request);
        }

        return new RedirectResponse(route('installer.index', [], false));
    }
}
