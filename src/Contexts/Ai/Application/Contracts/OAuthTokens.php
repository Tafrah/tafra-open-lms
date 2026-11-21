<?php

declare(strict_types=1);

namespace Src\Contexts\Ai\Application\Contracts;

/**
 * خادم OAuth من وجهة نظر التطبيق — الـ Actions مابتعرفش Passport.
 */
interface OAuthTokens
{
    /** بيلغي كل توكنز الوصول والتجديد والأكواد المعلّقة لعميل عند مستخدم. */
    public function revokeFor(int $userId, string $clientId): void;

    /** اسم العميل زي ما سجّل نفسه (Claude، ChatGPT…) — للعرض بس. */
    public function clientName(string $clientId): ?string;
}
