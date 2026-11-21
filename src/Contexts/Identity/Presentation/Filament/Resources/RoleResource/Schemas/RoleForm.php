<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Presentation\Filament\Resources\RoleResource\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Src\Contexts\Identity\Domain\Models\Role;
use Src\Support\Infrastructure\Authorization\PermissionBuilder;

final class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('identity::identity.role.sections.identity'))
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('name')
                        ->label(__('identity::identity.role.fields.name'))
                        ->required()
                        ->maxLength(60)
                        ->unique(ignoreRecord: true)
                        ->disabled(fn (?Role $record): bool => $record?->isProtected() ?? false)
                        ->saved(fn (?Role $record): bool => ! ($record?->isProtected() ?? false))
                        ->extraAttributes(['class' => 'fc-code']),

                    TextInput::make('guard_name')
                        ->label(__('identity::identity.role.fields.label'))
                        ->default(config('authorization.guard'))
                        ->disabled()
                        ->saved(false),
                ]),

            Section::make(__('identity::identity.role.sections.permissions'))
                ->description(__('identity::identity.role.sections.permissions_help'))
                ->columnSpanFull()
                ->schema(self::permissionGroups()),
        ]);
    }

    /** @return array<int, CheckboxList> */
    private static function permissionGroups(): array
    {
        $builder = app(PermissionBuilder::class);
        $components = [];

        foreach ($builder->groups() as $group => $resources) {
            $options = [];

            foreach ($resources as $resource => $permissions) {
                foreach ($permissions as $permission) {
                    $options[$permission] = permission_label($permission);
                }
            }

            if ($options === []) {
                continue;
            }

            $components[] = CheckboxList::make("permission_groups.{$group}")
                ->label(__("authorization.groups.{$group}"))
                ->options($options)
                ->columns(3)
                ->bulkToggleable()
                ->searchable()
                ->columnSpanFull()
                // الحقل ده مش عمود على الموديل — الحفظ في الصفحة عبر Action
                ->saved(false)
                ->dehydrated(fn (?Role $record): bool => ! ($record?->isProtected() ?? false));
        }

        return $components;
    }
}
