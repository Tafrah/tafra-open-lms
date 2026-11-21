<?php

declare(strict_types=1);

namespace Src\Support\Presentation\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Support\Presentation\Mcp\McpSession;
use Src\Support\Presentation\Mcp\SystemMap\SystemMap;

/**
 * تفاصيل شاشة واحدة: الوصف كامل، روابط صفحات السجل، والأدوات — عشان
 * النموذج يقدر يشرح للمستخدم يعمل الحاجة بنفسه من اللوحة، أو يعملها هو.
 */
final class GetScreenTool extends FcTool
{
    public function name(): string
    {
        return 'get_screen';
    }

    public function title(): string
    {
        return __('mcp.system.screen.title');
    }

    public function description(): string
    {
        return __('mcp.system.screen.description');
    }

    public function shouldRegister(): bool
    {
        return app(McpSession::class)->isActive();
    }

    /** @return array<string, mixed> */
    public function schema(JsonSchema $schema): array
    {
        $keys = array_column(app(SystemMap::class)->screens(), 'key');

        $key = $schema->string()->required()->description(__('mcp.system.screen.key'));

        return ['key' => $keys === [] ? $key : $key->enum($keys)];
    }

    protected function run(Request $request, User $actor): array
    {
        $map = app(SystemMap::class);

        $input = $request->validate([
            'key' => ['required', 'string', Rule::in(array_column($map->screens(), 'key'))],
        ]);

        $screen = $map->screen((string) $input['key']);

        unset($screen['accessible']);

        return ['screen' => $screen];
    }
}
