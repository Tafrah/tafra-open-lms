<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Presentation\Mcp;

use Illuminate\Database\Eloquent\Model;
use Src\Contexts\Identity\Presentation\Filament\Resources\RoleResource;
use Src\Support\Presentation\Mcp\McpField;
use Src\Support\Presentation\Mcp\McpResource;

/**
 * الأدوار — قراءة بس. الذكاء الاصطناعي يقدر يشرح مين يقدر يعمل إيه، بس
 * عمره ما يغيّر صلاحية (docs/23 بند ٥: «تغيير الأدوار أو الصلاحيات»).
 */
final class RoleMcp extends McpResource
{
    public static function filamentResource(): string
    {
        return RoleResource::class;
    }

    public function fields(): array
    {
        return [
            McpField::string('name')->readOnly()->sortable()->label('identity::identity.role.fields.name'),
        ];
    }

    public function omitted(): array
    {
        return [
            'create' => 'mcp.omitted.roles_write',
            'update' => 'mcp.omitted.roles_write',
            'delete' => 'mcp.omitted.roles_write',
        ];
    }

    public function searchable(): array
    {
        return ['name'];
    }

    public function extra(Model $record): array
    {
        return [
            'label' => __('authorization.roles.'.$record->getAttribute('name')),
            'permissions_count' => $record->getAttribute('permissions_count'),
            'users_count' => $record->getAttribute('users_count'),
        ];
    }
}
