<?php

declare(strict_types=1);

namespace Src\Support\Presentation\Mcp;

use Laravel\Mcp\Server\Tool;
use Src\Support\Presentation\Mcp\Tools\DescribeSystemTool;
use Src\Support\Presentation\Mcp\Tools\GetScreenTool;
use Src\Support\Presentation\Mcp\Tools\RecordActionTool;
use Src\Support\Presentation\Mcp\Tools\RecordTool;
use Src\Support\Presentation\Mcp\Tools\ResourceTool;

/**
 * بيحوّل الإعلانات لأدوات — والممنوع بيتشال هنا حتى لو حد أعلنه.
 *
 * `ai.forbidden_abilities` (انتحال، أدوار، تصدير جماعي…) مابتبقاش أداة في
 * أي مورد، و`ai.read_only_resources` مالهاش أدوات كتابة أصلاً. ده الحاجز
 * وقت التشغيل؛ `McpCoverageTest` بيمسك نفس الحاجة قبل الدمج.
 */
final readonly class McpToolFactory
{
    public function __construct(private McpRegistry $registry) {}

    /**
     * كل أدوات اللوحة — قبل فلترة `shouldRegister()` (الخادم بيفلتر).
     *
     * @return list<Tool>
     */
    public function toolsFor(string $panelId): array
    {
        $tools = [app(DescribeSystemTool::class), app(GetScreenTool::class)];

        foreach ($this->registry->resourcesFor($panelId) as $class) {
            $tools = [...$tools, ...$this->toolsForDeclaration(app($class))];
        }

        foreach ($this->registry->toolsFor($panelId) as $class) {
            $tools[] = app($class);
        }

        return $tools;
    }

    /** @return list<ResourceTool> */
    public function toolsForDeclaration(McpResource $declaration): array
    {
        $tools = [];

        foreach ($declaration->operations() as $operation) {
            if (self::permits($declaration, $operation->ability(), $operation->writes())) {
                $tools[] = new RecordTool($declaration, $operation);
            }
        }

        foreach ($declaration->actions() as $action) {
            if (self::permits($declaration, $action->ability(), writes: true)) {
                $tools[] = new RecordActionTool($declaration, $action);
            }
        }

        return $tools;
    }

    /**
     * الأدوات اللي المستخدم الحالي شايفها فعلاً لمورد.
     *
     * @return list<ResourceTool>
     */
    public function registeredFor(McpResource $declaration): array
    {
        return array_values(array_filter(
            $this->toolsForDeclaration($declaration),
            static fn (ResourceTool $tool): bool => $tool->shouldRegister(),
        ));
    }

    public static function permits(McpResource $declaration, string $ability, bool $writes): bool
    {
        if (in_array($ability, (array) config('ai.forbidden_abilities', []), true)) {
            return false;
        }

        return ! ($writes && in_array($declaration->key(), (array) config('ai.read_only_resources', []), true));
    }
}
