<?php

declare(strict_types=1);

namespace Src\Support\Presentation\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Support\Application\Actions\Records\ShowRecordAction;
use Src\Support\Domain\Authorization\Decision;
use Src\Support\Presentation\Mcp\McpResource;
use Src\Support\Presentation\Mcp\McpSession;
use Src\Support\Presentation\Mcp\SystemMap\SystemMap;

/**
 * أداة متولّدة من إعلان مورد — اسمها ووصفها والـ schema بتاعها من الإعلان.
 *
 * `shouldRegister()` = إخفاء، مش أمان — زي `shouldRegisterNavigation()`
 * بالظبط: الأداة مابتظهرش لمستخدم مالوش الصلاحية أصلاً، والتنفيذ نفسه
 * بيسأل الـ Policy كاملة على السجل (docs/23 بند ٣).
 */
abstract class ResourceTool extends FcTool
{
    public function __construct(protected readonly McpResource $declaration) {}

    /** قدرة الـ Policy اللي الأداة بتمثّلها */
    abstract public function ability(): string;

    /** اسم الأداة بعد اسم المورد: `list`، `publish`… */
    abstract protected function suffix(): string;

    /** الصلاحية لو مختلفة عن اسم القدرة (`activate` ← `suspend`) */
    protected function permissionAction(): ?string
    {
        return null;
    }

    public function declaration(): McpResource
    {
        return $this->declaration;
    }

    public function name(): string
    {
        return Str::limit($this->declaration->key().'_'.$this->suffix(), 64, '');
    }

    public function shouldRegister(): bool
    {
        $session = app(McpSession::class);
        $actor = $session->user();
        $policy = $this->declaration->policy();

        if (! $session->isActive() || $actor === null || $policy === null) {
            return false;
        }

        if ($this->writes() && ! $session->canWrite()) {
            return false;
        }

        return Decision::holds($actor, $policy, $this->ability(), $this->permissionAction());
    }

    /** وصف المورد نفسه (من خريطة النظام) — بيتضاف لوصف كل أداة. */
    protected function resourceContext(): string
    {
        $description = app(SystemMap::class)->describeResource($this->declaration::filamentResource());

        return $description === '' ? '' : ' '.__('mcp.tools.context', ['description' => Str::limit($description, 400)]);
    }

    protected function idSchema(JsonSchema $schema): Type
    {
        $type = $this->keyIsInteger() ? $schema->integer()->min(1) : $schema->string()->max(64);

        return $type->required()->description(__('mcp.fields.id', ['resource' => $this->declaration->singularLabel()]));
    }

    /** @return list<string> */
    protected function idRules(): array
    {
        return ['required', $this->keyIsInteger() ? 'integer' : 'string', $this->keyIsInteger() ? 'min:1' : 'max:64'];
    }

    protected function findRecord(Request $request, User $actor, string $ability, bool $withTrashed = false): Model
    {
        $id = $request->get('id');

        return app(ShowRecordAction::class)->handle(
            $actor,
            $this->declaration->query(),
            is_numeric($id) ? (int) $id : (string) $id,
            $ability,
            $withTrashed,
        );
    }

    private function keyIsInteger(): bool
    {
        $model = $this->declaration->model();

        return in_array((new $model)->getKeyType(), ['int', 'integer'], true);
    }
}
