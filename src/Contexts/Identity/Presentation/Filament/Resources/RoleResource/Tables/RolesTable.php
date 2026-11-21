<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Presentation\Filament\Resources\RoleResource\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Src\Contexts\Identity\Domain\Models\Role;

final class RolesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('identity::identity.role.fields.name'))
                    ->formatStateUsing(fn (string $state): string => __("authorization.roles.{$state}"))
                    ->description(fn (Role $record): string => $record->name)
                    ->weight(FontWeight::Medium)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('permissions_count')
                    ->label(__('identity::identity.role.fields.permissions_count'))
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn (int $state): string => fc_number($state)),

                TextColumn::make('users_count')
                    ->label(__('identity::identity.role.fields.users_count'))
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (int $state): string => fc_number($state)),

                IconColumn::make('protected')
                    ->label(__('identity::identity.role.fields.protected'))
                    ->alignCenter()
                    ->getStateUsing(fn (Role $record): bool => $record->isProtected())
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('')
                    ->trueColor('warning')
                    ->tooltip(fn (Role $record): ?string => $record->isProtected()
                        ? __('authorization.denied.role.protected')
                        : null),
            ])
            ->defaultSort('name')
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()->authorizationTooltip(),
                    DeleteAction::make()->authorizationTooltip(),
                ])
                    ->label(__('common.actions'))
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->button()
                    ->color('gray'),
            ])
            ->headerActions([CreateAction::make()])
            ->emptyStateHeading(__('identity::identity.role.empty.heading'))
            ->emptyStateDescription(__('identity::identity.role.empty.description'))
            ->emptyStateIcon('heroicon-o-shield-check');
    }
}
