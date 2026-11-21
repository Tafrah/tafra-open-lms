<?php

declare(strict_types=1);

namespace Src\Contexts\Ai\Presentation\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\AccessToken;
use Src\Contexts\Ai\Application\Contracts\OAuthTokens;
use Src\Contexts\Ai\Application\Services\AiAvailability;
use Src\Contexts\Ai\Domain\Models\AiConnection;
use Src\Contexts\Identity\Domain\Enums\UserStatus;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;
use Src\Support\Infrastructure\Authorization\CentralRoles;
use Src\Support\Presentation\Mcp\McpSession;
use Symfony\Component\HttpFoundation\Response;

/**
 * الحاجز بين التوكن والأدوات — بيشتغل بعد `auth:api` وقبل الخادم.
 *
 * التوكن لوحده مش كفاية: لازم يكون ليه **ربط** نشط (المستخدم وافق على
 * العميل ده لمؤسسة محددة)، والمستخدم لسه نشط وعضو فيها، والمؤسسة شغالة،
 * ومفاتيح الإيقاف التلاتة مفتوحة. بعدها بيضبط المؤسسة من الربط — **مش من
 * أي مدخل** — زي `InitializeTenantContext` في اللوحة (docs/23 بند ٤).
 *
 * 401 = الربط اتلغى أو مش موجود → العميل بيطلب موافقة جديدة.
 * 403 = الربط موجود بس الوصول مقفول دلوقتي (مفتاح، عضوية، إيقاف).
 */
final class AuthenticateMcpConnection
{
    public function __construct(
        private readonly AiAvailability $availability,
        private readonly TenantContext $tenants,
        private readonly McpSession $session,
        private readonly OAuthTokens $oauth,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $token = $user instanceof User ? $user->token() : null;

        if (! $user instanceof User || ! $token instanceof AccessToken) {
            return $this->deny(401, 'unauthenticated');
        }

        $connection = AiConnection::query()
            ->active()
            ->where('user_id', $user->getKey())
            ->where('oauth_client_id', (string) $token->oauth_client_id)
            ->first();

        if ($connection === null) {
            return $this->deny(401, 'connection_revoked');
        }

        if (! $user->is_active || $user->status !== UserStatus::Active || $user->trashed()) {
            return $this->deny(403, 'inactive_account');
        }

        $tenant = $connection->tenant_id === null ? null : Tenant::query()->find($connection->tenant_id);

        if (! $this->belongs($user, $connection, $tenant)) {
            return $this->deny(403, 'access_closed');
        }

        // المؤسسة من الربط — قبل أي Policy أو إعداد (فريق spatie كمان). ضبطها
        // قبل فحص المفاتيح بيخلّي قراية إعدادات المؤسسة من غير تبديل تاني
        $tenantId = $tenant === null ? null : (int) $tenant->getKey();
        $this->tenants->set($tenantId);

        try {
            if (! $this->stillAllowed($user, $tenantId)) {
                return $this->deny(403, 'access_closed');
            }

            $this->enter($user, $connection, $tenant);

            return $next($request);
        } finally {
            $this->session->end();
            $this->tenants->set(null);
        }
    }

    /** عضو لسه في المؤسسة الشغّالة — أو من فريق التشغيل لربط مركزي. */
    private function belongs(User $user, AiConnection $connection, ?Tenant $tenant): bool
    {
        if ($connection->tenant_id === null) {
            return app(CentralRoles::class)->isStaff($user);
        }

        if ($tenant === null || ! $tenant->is_active || $tenant->suspended_at !== null) {
            return false;
        }

        return $user->tenants()->whereKey($tenant->getKey())->exists();
    }

    /**
     * المفاتيح مفتوحة **ولسه معاه `access.ai`** — شيل الصلاحية من الدور بيفصل
     * الروابط القائمة فوراً، مش بعد ما توكن التجديد يخلص.
     */
    private function stillAllowed(User $user, ?int $tenantId): bool
    {
        return $this->availability->enabledFor($tenantId)
            && $this->availability->userMayConnect($user, $tenantId);
    }

    private function enter(User $user, AiConnection $connection, ?Tenant $tenant): void
    {
        Filament::setCurrentPanel($tenant === null ? 'central' : 'admin');
        Filament::setTenant($tenant, isQuiet: true);

        // Filament بيسأل حارس اللوحة (`web`) — من غير ده كل `canAccess()`
        // و`canViewAny()` كانوا هيشوفوا ضيف. مفيش جلسة على المسار، فمفيش حاجة بتتحفظ.
        Auth::guard((string) config('authorization.guard', 'web'))->setUser($user);

        $locale = $user->preferredLocale();

        if (in_array($locale, (array) config('app.supported_locales'), true)) {
            app()->setLocale($locale);
        }

        $this->touch($connection);

        $this->session->start(
            user: $user,
            tenantId: $tenant === null ? null : (int) $tenant->getKey(),
            canWrite: $connection->access->allowsWrites()
                && $this->availability->writesAllowedFor($tenant === null ? null : (int) $tenant->getKey()),
            clientName: $this->oauth->clientName($connection->oauth_client_id),
            connectionId: (int) $connection->getKey(),
        );
    }

    /** `last_used_at` مرة كل دقيقة — مش كتابة مع كل نداء. */
    private function touch(AiConnection $connection): void
    {
        $interval = (int) config('ai.touch_interval_seconds', 60);

        if ($connection->last_used_at !== null && $connection->last_used_at->diffInSeconds(now(), true) < $interval) {
            return;
        }

        AiConnection::query()->whereKey($connection->getKey())->update(['last_used_at' => now()]);
    }

    private function deny(int $status, string $reason): JsonResponse
    {
        return new JsonResponse([
            'error' => $status === 401 ? 'invalid_token' : 'access_denied',
            'error_description' => __("ai::ai.errors.{$reason}"),
        ], $status);
    }
}
