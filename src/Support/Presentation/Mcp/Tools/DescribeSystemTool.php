<?php

declare(strict_types=1);

namespace Src\Support\Presentation\Mcp\Tools;

use Laravel\Mcp\Request;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Support\Presentation\Mcp\McpSession;
use Src\Support\Presentation\Mcp\SystemMap\SystemOverview;

/**
 * نقطة البداية: المستخدم والمؤسسة ومستوى الوصول وكل شاشة يقدر يفتحها —
 * باسمها ومسارها ووصفها والأدوات اللي بتشتغل عليها.
 */
final class DescribeSystemTool extends FcTool
{
    public function name(): string
    {
        return 'describe_system';
    }

    public function title(): string
    {
        return __('mcp.system.describe.title');
    }

    public function description(): string
    {
        return __('mcp.system.describe.description');
    }

    public function shouldRegister(): bool
    {
        return app(McpSession::class)->isActive();
    }

    protected function run(Request $request, User $actor): array
    {
        return app(SystemOverview::class)->toArray();
    }
}
