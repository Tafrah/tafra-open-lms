<?php

declare(strict_types=1);

namespace Src\Contexts\Tenancy\Presentation\Mcp\Central;

use Illuminate\Database\Eloquent\Model;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Tenancy\Application\Actions\ActivateTenantAction;
use Src\Contexts\Tenancy\Application\Actions\SuspendTenantAction;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Contexts\Tenancy\Presentation\Filament\Central\Resources\TenantResource;
use Src\Support\Presentation\Mcp\McpField;
use Src\Support\Presentation\Mcp\McpRecordAction;
use Src\Support\Presentation\Mcp\McpResource;

/**
 * المؤسسات على اللوحة المركزية — عرض وإيقاف وإعادة تفعيل.
 *
 * الخادم المركزي مقفول افتراضياً (`ai.central_enabled`). إنشاء مؤسسة أو
 * حذفها بيجهّز قاعدة أو يمسحها — من اللوحة بس.
 */
final class TenantMcp extends McpResource
{
    private const LABELS = 'tenancy::tenancy.tenant.';

    public static function filamentResource(): string
    {
        return TenantResource::class;
    }

    public function fields(): array
    {
        return [
            McpField::string('name')->readOnly()->label(self::LABELS.'fields.name'),
            McpField::string('slug')->readOnly()->label(self::LABELS.'fields.domain'),
            McpField::boolean('is_active')->readOnly()->filterable()->label('common.active'),
            McpField::dateTime('suspended_at')->readOnly()->label(self::LABELS.'status.suspended'),
            McpField::string('provisioning_status')->readOnly()->filterable()->label(self::LABELS.'fields.provisioning_status'),
        ];
    }

    public function actions(): array
    {
        return [
            McpRecordAction::make('suspend')
                ->label(self::LABELS.'actions.suspend')
                ->description('tenancy::tenancy.tenant.mcp.suspend')
                ->fields([
                    McpField::text('reason')->required()->max(255)->label(self::LABELS.'fields.suspension_reason'),
                ])
                ->using(fn (Model $record, array $input, User $actor): Tenant => app(SuspendTenantAction::class)
                    ->handle($this->tenant($record), (string) $input['reason'], $actor)),

            McpRecordAction::make('activate')
                ->permission('suspend')
                ->label(self::LABELS.'actions.activate')
                ->description('tenancy::tenancy.tenant.mcp.activate')
                ->safe()
                ->using(fn (Model $record, array $input, User $actor): Tenant => app(ActivateTenantAction::class)
                    ->handle($this->tenant($record), $actor)),
        ];
    }

    public function omitted(): array
    {
        return [
            'create' => 'mcp.omitted.tenant_lifecycle',
            'update' => 'mcp.omitted.tenant_lifecycle',
            'delete' => 'mcp.omitted.tenant_lifecycle',
            'deactivate' => 'mcp.omitted.tenant_lifecycle',
            'restore' => 'mcp.omitted.tenant_lifecycle',
            'forceDelete' => 'mcp.omitted.tenant_lifecycle',
            'impersonate' => 'mcp.omitted.impersonate',
            'export' => 'mcp.omitted.export',
        ];
    }

    private function tenant(Model $record): Tenant
    {
        // السجل جاي من استعلام المورد نفسه — أي نوع تاني = خطأ برمجي
        throw_unless($record instanceof Tenant, \LogicException::class);

        return $record;
    }
}
