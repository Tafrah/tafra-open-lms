<?php

declare(strict_types=1);

namespace Src\Contexts\Tenancy\Presentation\Filament\Central\Resources\TenantResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Src\Contexts\Tenancy\Presentation\Filament\Central\Resources\TenantResource;
use Src\Support\Infrastructure\Theming\ThemeColorResolver;

final class EditTenant extends EditRecord
{
    protected static string $resource = TenantResource::class;

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            RestoreAction::make(),
            ForceDeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        // اللون اتغيّر — كاش متغيّرات العلامة بقى بايت
        ThemeColorResolver::flushCache();
    }
}
