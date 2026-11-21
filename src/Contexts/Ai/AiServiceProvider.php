<?php

declare(strict_types=1);

namespace Src\Contexts\Ai;

use Carbon\CarbonInterval;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Mcp\Server\Registrar;
use Laravel\Passport\AccessToken;
use Laravel\Passport\Passport;
use Src\Contexts\Ai\Application\Contracts\OAuthTokens;
use Src\Contexts\Ai\Infrastructure\OAuth\PassportOAuthTokens;
use Src\Contexts\Ai\Presentation\Http\ConsentScreen;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Support\Infrastructure\Providers\ContextServiceProvider;

/**
 * وصول الذكاء الاصطناعي — خادم MCP وOAuth لـ Claude وChatGPT (docs/23).
 *
 * السياق ده بيملك **الربط** (مين وافق على أنهي تطبيق في أنهي مؤسسة) وشاشة
 * الموافقة وشاشة التطبيقات المربوطة. الأدوات نفسها مش هنا: كل سياق بيعلن
 * موارده في `Presentation/Mcp/`، والمحرّك في `Src\Support\Presentation\Mcp`.
 */
final class AiServiceProvider extends ContextServiceProvider
{
    protected function name(): string
    {
        return 'ai';
    }

    protected function path(): string
    {
        return __DIR__;
    }

    /** شاشة «تطبيقاتي المربوطة» على لوحة المؤسسة بس */
    protected function panelIds(): array
    {
        return ['admin'];
    }

    protected function registerBindings(): void
    {
        $this->app->bind(OAuthTokens::class, PassportOAuthTokens::class);

        // مسار واحد بس: كود التفويض بـ PKCE. Passport بيسجّل مساراته في boot
        // بتاعه (قبلنا) — فالقفل لازم هنا في register، غير كده مسارات الأجهزة بتتسجّل
        Passport::$deviceCodeGrantEnabled = false;

        // مسارات Passport بنسجّلها بنفسنا (`Routes/web.php`): من غير مسار الموافقة
        // بتاعه — الموافقة الوحيدة هي `ai.oauth.consent` اللي بتسأل `connect`
        // وبتسجّل المؤسسة. مساره كان طريق جانبي بيطلع كود من غير الاتنين.
        Passport::ignoreRoutes();
    }

    protected function bootContext(): void
    {
        $this->configurePassport();
        $this->configureRateLimiting();
    }

    private function configurePassport(): void
    {
        // توكن قصير + تجديد بيتلف مع كل استخدام: توكن متسرّب بيموت في ساعة،
        // وتوكن تجديد اتسرق واتستخدم بيكشف نفسه (الأصلي بيترفض)
        Passport::tokensExpireIn(CarbonInterval::minutes((int) config('ai.oauth.access_token_minutes', 60)));
        Passport::refreshTokensExpireIn(CarbonInterval::days((int) config('ai.oauth.refresh_token_days', 30)));
        Passport::$revokeRefreshTokenAfterUse = true;

        Passport::authorizationView(fn (array $parameters) => $this->app->make(ConsentScreen::class)->render($parameters));

        Registrar::ensureMcpScope();
    }

    private function configureRateLimiting(): void
    {
        // لكل ربط مش لكل توكن — التوكن بيتلف كل ساعة، والربط ثابت
        RateLimiter::for('mcp', fn (Request $request): Limit => Limit::perMinute((int) config('ai.rate_limit.per_minute', 60))
            ->by($this->rateKey($request)));

        // التسجيل الديناميكي مفتوح من غير مصادقة (RFC 7591) — حد بالـ IP
        RateLimiter::for('mcp-register', fn (Request $request): Limit => Limit::perMinute((int) config('ai.oauth.registrations_per_minute', 10))
            ->by('mcp-register:'.$request->ip()));
    }

    private function rateKey(Request $request): string
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return 'mcp-ip:'.$request->ip();
        }

        $token = $user->token();
        $client = $token instanceof AccessToken ? (string) $token->oauth_client_id : '';

        return 'mcp:'.$user->getKey().':'.$client;
    }
}
