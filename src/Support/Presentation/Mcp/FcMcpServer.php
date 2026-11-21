<?php

declare(strict_types=1);

namespace Src\Support\Presentation\Mcp;

use Filament\Facades\Filament;
use Laravel\Mcp\Server;
use Src\Support\Presentation\Mcp\Resources\SystemMapResource;

/**
 * خادم MCP الوحيد في التطبيق — بيتبني من جديد مع كل طلب HTTP.
 *
 * مابيعرفش السياقات: الأدوات من `McpRegistry` (بالاكتشاف)، والمستخدم
 * والمؤسسة والمستوى من `McpSession` (من الربط المحفوظ). من غير جلسة =
 * مفيش أدوات خالص، فطلب عدّى من غير `AuthenticateMcpConnection` مابيشوفش حاجة.
 */
final class FcMcpServer extends Server
{
    /** القايمة كلها في صفحة واحدة — مش كل العملاء بتتبع `nextCursor` */
    public int $defaultPaginationLength = 50;

    public int $maxPaginationLength = 100;

    protected function boot(): void
    {
        $this->name = (string) config('ai.server.name');
        $this->version = (string) config('ai.server.version');

        $session = app(McpSession::class);

        if (! $session->isActive()) {
            $this->tools = [];
            $this->resources = [];

            return;
        }

        $this->instructions = __('mcp.server.instructions', [
            'app' => (string) config('app.name'),
            'user' => (string) $session->user()?->name,
            'organization' => (string) (Filament::getTenant()?->getAttribute('name') ?? __('mcp.server.central')),
            'access' => __($session->canWrite() ? 'mcp.server.access_write' : 'mcp.server.access_read'),
        ]);

        $this->tools = app(McpToolFactory::class)->toolsFor($session->panelId());
        $this->resources = [SystemMapResource::class];
    }
}
