<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Infrastructure\Exports;

use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Notifications\Infrastructure\Exports\BaseExporter;

/**
 * التصدير في الطابور دايماً — المستخدم بياخد إشعار بالرابط لما يخلص
 * (docs/08 بند ٦).
 */
final class UserExporter extends BaseExporter
{
    protected static ?string $model = User::class;

    public static function resourceLabel(): string
    {
        return __('identity::identity.user.plural');
    }

    /** @return array<int, ExportColumn> */
    public static function getColumns(): array
    {
        return [
            ExportColumn::make('code')->label(__('identity::identity.user.fields.code')),
            ExportColumn::make('name')->label(__('identity::identity.user.fields.name')),
            ExportColumn::make('email')->label(__('identity::identity.user.fields.email')),
            ExportColumn::make('phone')->label(__('identity::identity.user.fields.phone')),
            ExportColumn::make('status')->label(__('common.status')),
            ExportColumn::make('last_login_at')->label(__('identity::identity.user.fields.last_login')),
            ExportColumn::make('created_at')->label(__('common.created_at')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return trans_choice('identity::identity.user.export.completed', $export->successful_rows, [
            'count' => fc_number($export->successful_rows),
        ]);
    }
}
