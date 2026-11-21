<?php

declare(strict_types=1);

namespace Src\Contexts\Settings\Presentation\Filament\Central\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Src\Contexts\Settings\Domain\Settings\StorageSettings;
use Src\Contexts\Settings\Presentation\Filament\Central\Clusters\SettingsCluster;

final class ManageStorage extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-server-stack';

    protected static string $settings = StorageSettings::class;

    protected static ?string $cluster = SettingsCluster::class;

    protected static ?string $slug = 'storage';

    protected static ?int $navigationSort = 30;

    public static function getNavigationLabel(): string
    {
        return __('settings::settings.pages.storage');
    }

    public function getTitle(): string
    {
        return __('settings::settings.pages.storage');
    }

    public static function canAccess(): bool
    {
        return Gate::allows('access.system') && Gate::allows('manage_storage.settings');
    }

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('test_connection')
                ->label(__('settings::settings.actions.test_connection'))
                ->icon('heroicon-o-signal')
                ->color('gray')
                ->action(function (): void {
                    $disk = $this->data['default_disk'] ?? 'public';
                    $path = 'health/'.Str::uuid().'.txt';

                    try {
                        Storage::disk($disk)->put($path, 'fc-admin connectivity check');
                        $exists = Storage::disk($disk)->exists($path);
                        Storage::disk($disk)->delete($path);

                        if (! $exists) {
                            throw new \RuntimeException('write succeeded but read failed');
                        }

                        Notification::make()
                            ->success()
                            ->title(__('settings::settings.notifications.storage_ok', ['disk' => $disk]))
                            ->send();
                    } catch (\Throwable $exception) {
                        report($exception);

                        Notification::make()
                            ->danger()
                            ->title(__('settings::settings.notifications.storage_failed', ['disk' => $disk]))
                            ->body($exception->getMessage())
                            ->persistent()
                            ->send();
                    }
                }),
        ];
    }

    public function form(Schema $schema): Schema
    {
        $disks = collect(array_keys((array) config('filesystems.disks')))
            ->mapWithKeys(fn (string $disk): array => [$disk => $disk])
            ->all();

        return $schema->components([
            Section::make(__('settings::settings.sections.disks'))
                ->description(__('settings::settings.sections.disks_help'))
                ->columns(3)
                ->columnSpanFull()
                ->schema([
                    Select::make('default_disk')
                        ->label(__('settings::settings.fields.default_disk'))
                        ->options($disks)
                        ->native(false)
                        ->required(),

                    Select::make('private_disk')
                        ->label(__('settings::settings.fields.private_disk'))
                        ->options($disks)
                        ->native(false)
                        ->required()
                        ->helperText(__('settings::settings.help.private_disk')),

                    Select::make('conversions_disk')
                        ->label(__('settings::settings.fields.conversions_disk'))
                        ->options($disks)
                        ->native(false)
                        ->required(),
                ]),

            Section::make(__('settings::settings.sections.collections'))
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TagsInput::make('private_collections')
                        ->label(__('settings::settings.fields.private_collections'))
                        ->helperText(__('settings::settings.help.private_collections')),

                    KeyValue::make('collection_disks')
                        ->label(__('settings::settings.fields.collection_disks'))
                        ->keyLabel(__('settings::settings.fields.collection'))
                        ->valueLabel(__('settings::settings.fields.disk'))
                        ->helperText(__('settings::settings.help.collection_disks')),
                ]),

            Section::make(__('settings::settings.sections.limits'))
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('max_upload_size_kb')
                        ->label(__('settings::settings.fields.max_upload_size'))
                        ->numeric()
                        ->minValue(64)
                        ->maxValue(1048576)
                        ->required()
                        ->suffix('KB'),

                    TagsInput::make('allowed_mimes')
                        ->label(__('settings::settings.fields.allowed_mimes'))
                        ->helperText(__('settings::settings.help.allowed_mimes')),
                ]),
        ]);
    }
}
