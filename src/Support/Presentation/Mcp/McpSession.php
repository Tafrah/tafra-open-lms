<?php

declare(strict_types=1);

namespace Src\Support\Presentation\Mcp;

use Src\Contexts\Identity\Domain\Models\User;

/**
 * مين بينادي MCP دلوقتي، ومن أنهي مؤسسة، وبأي مستوى.
 *
 * بيتملى مرة واحدة في `AuthenticateMcpConnection` من الربط المحفوظ — مش من
 * أي مدخل في الطلب. الخادم والأدوات بيقروا منه بس، فمفيش أداة تقدر تاخد
 * `tenant_id` أو تغيّر المستوى (docs/23 بند ١ و٤).
 *
 * `scoped`: بيتصفّر مع كل طلب/Job — مايعيشش لعمر العامل.
 */
final class McpSession
{
    private ?User $user = null;

    private ?int $tenantId = null;

    private bool $canWrite = false;

    private ?string $clientName = null;

    private ?int $connectionId = null;

    private bool $active = false;

    public function start(
        User $user,
        ?int $tenantId,
        bool $canWrite,
        ?string $clientName = null,
        ?int $connectionId = null,
    ): void {
        $this->user = $user;
        $this->tenantId = $tenantId;
        $this->canWrite = $canWrite;
        $this->clientName = $clientName;
        $this->connectionId = $connectionId;
        $this->active = true;
    }

    public function end(): void
    {
        $this->user = null;
        $this->tenantId = null;
        $this->canWrite = false;
        $this->clientName = null;
        $this->connectionId = null;
        $this->active = false;
    }

    public function isActive(): bool
    {
        return $this->active && $this->user !== null;
    }

    public function user(): ?User
    {
        return $this->user;
    }

    public function tenantId(): ?int
    {
        return $this->tenantId;
    }

    /** لوحة Filament اللي الجلسة شايفاها — المؤسسة أو المركزية. */
    public function panelId(): string
    {
        return $this->tenantId === null ? 'central' : 'admin';
    }

    /** المستوى المتفق عليه في الموافقة ∩ مفتاح المؤسسة. الـ Policy بعده. */
    public function canWrite(): bool
    {
        return $this->active && $this->canWrite;
    }

    public function clientName(): ?string
    {
        return $this->clientName;
    }

    public function connectionId(): ?int
    {
        return $this->connectionId;
    }

    /** مفتاح لحدود المعدّل: الربط نفسه، مش التوكن (التوكن بيتلف كل ساعة). */
    public function rateLimitKey(): string
    {
        return 'mcp:'.($this->connectionId ?? 'user-'.($this->user?->getKey() ?? 'guest'));
    }
}
