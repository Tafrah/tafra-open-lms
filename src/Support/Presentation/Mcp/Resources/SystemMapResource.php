<?php

declare(strict_types=1);

namespace Src\Support\Presentation\Mcp\Resources;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Resource;
use Src\Support\Presentation\Mcp\SystemMap\SystemOverview;

/**
 * خريطة النظام كمورد MCP — للعملاء اللي بتقرا موارد كسياق (Claude).
 * نفس محتوى أداة `describe_system`.
 */
final class SystemMapResource extends Resource
{
    protected string $uri = 'fc://system/map';

    protected string $mimeType = 'application/json';

    public function name(): string
    {
        return 'system-map';
    }

    public function title(): string
    {
        return __('mcp.system.describe.title');
    }

    public function description(): string
    {
        return __('mcp.system.describe.description');
    }

    public function handle(Request $request): Response
    {
        return Response::json(app(SystemOverview::class)->toArray());
    }
}
