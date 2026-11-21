<?php

declare(strict_types=1);

namespace Src\Contexts\Settings\Presentation\Filament\Central\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Src\Contexts\Settings\Domain\Settings\MailSettings;
use Src\Contexts\Settings\Presentation\Filament\Central\Clusters\SettingsCluster;

final class ManageMail extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

    protected static string $settings = MailSettings::class;

    protected static ?string $cluster = SettingsCluster::class;

    protected static ?string $slug = 'mail';

    protected static ?int $navigationSort = 40;

    public static function getNavigationLabel(): string
    {
        return __('settings::settings.pages.mail');
    }

    public function getTitle(): string
    {
        return __('settings::settings.pages.mail');
    }

    public static function canAccess(): bool
    {
        return Gate::allows('access.system') && Gate::allows('manage_mail.settings');
    }

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('send_test')
                ->label(__('settings::settings.actions.send_test_mail'))
                ->icon('heroicon-o-paper-airplane')
                ->color('gray')
                ->action(function (): void {
                    try {
                        Mail::raw(__('settings::settings.notifications.test_mail_body'), function ($message): void {
                            $message->to(auth()->user()->email)
                                ->subject(__('settings::settings.notifications.test_mail_subject'));
                        });

                        Notification::make()
                            ->success()
                            ->title(__('settings::settings.notifications.mail_sent'))
                            ->send();
                    } catch (\Throwable $exception) {
                        report($exception);

                        Notification::make()
                            ->danger()
                            ->title(__('settings::settings.notifications.mail_failed'))
                            ->body($exception->getMessage())
                            ->persistent()
                            ->send();
                    }
                }),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('settings::settings.sections.smtp'))
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Select::make('driver')
                        ->label(__('settings::settings.fields.mail_driver'))
                        ->options(['smtp' => 'SMTP', 'log' => 'Log', 'array' => 'Array'])
                        ->native(false)
                        ->required()
                        ->live(),

                    TextInput::make('host')
                        ->label(__('settings::settings.fields.mail_host'))
                        ->required(fn (Get $get): bool => $get('driver') === 'smtp'),

                    TextInput::make('port')
                        ->label(__('settings::settings.fields.mail_port'))
                        ->numeric()
                        ->required(),

                    Select::make('encryption')
                        ->label(__('settings::settings.fields.mail_encryption'))
                        ->options(['tls' => 'TLS', 'ssl' => 'SSL', 'none' => __('common.none')])
                        ->native(false)
                        ->required(),

                    TextInput::make('username')
                        ->label(__('settings::settings.fields.mail_username')),

                    TextInput::make('password')
                        ->label(__('settings::settings.fields.mail_password'))
                        ->password()
                        ->revealable()
                        // القيمة مشفّرة في قاعدة البيانات — spatie/settings encrypted()
                        ->helperText(__('settings::settings.help.mail_password')),
                ]),

            Section::make(__('settings::settings.sections.sender'))
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('from_address')
                        ->label(__('settings::settings.fields.mail_from_address'))
                        ->email()
                        ->required(),

                    ...LocalizedFields::text('from_name', __('settings::settings.fields.mail_from_name'), required: true),
                ]),
        ]);
    }
}
