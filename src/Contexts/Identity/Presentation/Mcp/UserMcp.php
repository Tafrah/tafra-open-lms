<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Presentation\Mcp;

use Illuminate\Database\Eloquent\Model;
use Src\Contexts\Identity\Domain\Enums\UserStatus;
use Src\Contexts\Identity\Presentation\Filament\Resources\UserResource;
use Src\Support\Presentation\Mcp\McpField;
use Src\Support\Presentation\Mcp\McpResource;

/**
 * المستخدمين — قراءة بس عن طريق AI (`ai.read_only_resources`).
 *
 * دعوة حساب أو تعديل بريده أو حذفه بيغيّر **مين يقدر يدخل**: بريد اتغيّر
 * بنداء من نموذج = استيلاء على الحساب بإعادة تعيين كلمة المرور. ده بيفضل
 * في اللوحة، والشخص شايف بالظبط هو بيدّي إيه.
 */
final class UserMcp extends McpResource
{
    private const LABELS = 'identity::identity.user.fields.';

    public static function filamentResource(): string
    {
        return UserResource::class;
    }

    public function fields(): array
    {
        return [
            McpField::string('name')->readOnly()->sortable()->label(self::LABELS.'name'),
            McpField::email('email')->readOnly()->label(self::LABELS.'email'),
            McpField::enum('status', UserStatus::class)->readOnly()->filterable()->label('common.status'),
            McpField::boolean('is_active')->readOnly()->filterable()->label(self::LABELS.'is_active'),
        ];
    }

    public function omitted(): array
    {
        return [
            'create' => 'mcp.omitted.user_identity',
            'update' => 'mcp.omitted.user_identity',
            'delete' => 'mcp.omitted.user_identity',
            'restore' => 'mcp.omitted.user_identity',
            'forceDelete' => 'mcp.omitted.user_identity',
            'export' => 'mcp.omitted.export',
            'impersonate' => 'mcp.omitted.impersonate',
            'resetPassword' => 'mcp.omitted.reset_password',
            'forceLogout' => 'mcp.omitted.force_logout',
            'manageRoles' => 'mcp.omitted.manage_roles',
        ];
    }

    public function with(): array
    {
        return ['roles:id,name'];
    }

    public function extra(Model $record): array
    {
        return [
            'roles' => $record->getRelationValue('roles')?->pluck('name')->values()->all() ?? [],
        ];
    }
}
