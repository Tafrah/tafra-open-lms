<?php

declare(strict_types=1);

namespace Src\Contexts\Ai\Application\Services;

use Illuminate\Support\Facades\Gate;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Settings\Application\Services\TenantSettings;
use Src\Contexts\Settings\Domain\Settings\SecuritySettings;
use Src\Support\Application\Contracts\TenantContext;
use Throwable;

/**
 * هل وصول الذكاء الاصطناعي مفتوح؟ تلات مفاتيح، والتلاتة لازم يكونوا مفتوحين:
 *
 *  1. البيئة   — `ai.enabled` (كونفيج، للطوارئ وقت الديبلوي)
 *  2. النظام   — `SecuritySettings::mcp_enabled` (من اللوحة المركزية، من غير ديبلوي)
 *  3. المؤسسة  — `ai.mcp_enabled` في إعدادات المؤسسة (من مدير المؤسسة)
 *
 * بيتسأل في شاشة الموافقة **وفي كل نداء MCP** — القفل بيفصل فوراً من غير
 * ما يلغي الروابط، فالفتح تاني بيرجّعها زي ما كانت.
 */
final readonly class AiAvailability
{
    public function __construct(
        private TenantContext $context,
        private TenantSettings $settings,
    ) {}

    public function enabledGlobally(): bool
    {
        if (! (bool) config('ai.enabled', true)) {
            return false;
        }

        try {
            return app(SecuritySettings::class)->mcp_enabled;
        } catch (Throwable) {
            // الإعداد لسه ماتعملوش migrate — المفتاح البيئي هو الحكم
            return true;
        }
    }

    /** `null` = اللوحة المركزية — مقفولة إلا لو `ai.central_enabled`. */
    public function enabledFor(?int $tenantId): bool
    {
        if (! $this->enabledGlobally()) {
            return false;
        }

        if ($tenantId === null) {
            return (bool) config('ai.central_enabled', false);
        }

        return (bool) $this->tenantSetting($tenantId, 'mcp_enabled', true);
    }

    /** المؤسسة ممكن تقفل الكتابة للكل حتى لو العضو اختار «قراءة وتعديل». */
    public function writesAllowedFor(?int $tenantId): bool
    {
        if ($tenantId === null) {
            return true;
        }

        return (bool) $this->tenantSetting($tenantId, 'allow_writes', true);
    }

    /**
     * `access.ai` **على فريق المؤسسة اللي بيتربط عليها** — مش الفريق الحالي.
     *
     * شاشة الموافقة برا اللوحة، فالفريق الحالي هناك هو المركزي (أو فاضي):
     * السؤال من غير `forTenant()` كان بيرفض كل مستخدم أدواره على مؤسسة بس،
     * وبيسأل مستخدم عنده دورين في مؤسستين عن الدور الغلط (`docs/21` بند ٣١).
     */
    public function userMayConnect(User $user, ?int $tenantId): bool
    {
        return (bool) $this->context->forTenant(
            $tenantId,
            static fn (): bool => Gate::forUser($user)->allows('access.ai'),
        );
    }

    private function tenantSetting(int $tenantId, string $key, bool $default): mixed
    {
        if ($this->context->id() === $tenantId) {
            return $this->settings->get('ai', $key, $default);
        }

        return $this->context->forTenant(
            $tenantId,
            fn (): mixed => $this->settings->get('ai', $key, $default),
        );
    }
}
