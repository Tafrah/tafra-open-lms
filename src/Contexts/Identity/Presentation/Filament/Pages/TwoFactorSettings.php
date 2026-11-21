<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Presentation\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Src\Contexts\Identity\Domain\Models\User;

/**
 * شاشة 2FA — منطقها من Fortify، وشكلها بتاعنا (docs/12 بند ١).
 */
final class TwoFactorSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'identity::filament.pages.two-factor';

    /**
     * أكواد الاسترجاع بتتعرض مرة واحدة بس بعد التوليد.
     *
     * @var list<string>|null
     */
    public ?array $recoveryCodes = null;

    public static function getSlug(?Panel $panel = null): string
    {
        return 'two-factor';
    }

    public static function canAccess(): bool
    {
        return Gate::allows('access.two_factor');
    }

    public function getTitle(): string
    {
        return __('identity::identity.two_factor.title');
    }

    public function getSubheading(): string
    {
        return __('identity::identity.two_factor.description');
    }

    public function user(): User
    {
        return auth()->user();
    }

    public function isEnabled(): bool
    {
        return $this->user()->two_factor_confirmed_at !== null;
    }

    public function isPending(): bool
    {
        return $this->user()->two_factor_secret !== null && ! $this->isEnabled();
    }

    public function qrCodeSvg(): ?string
    {
        return $this->user()->two_factor_secret !== null ? $this->user()->twoFactorQrCodeSvg() : null;
    }

    public function setupKey(): ?string
    {
        if ($this->user()->two_factor_secret === null) {
            return null;
        }

        return decrypt($this->user()->two_factor_secret);
    }

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('enable')
                ->label(__('identity::identity.two_factor.actions.enable'))
                ->icon('heroicon-o-shield-check')
                ->visible(fn (): bool => ! $this->isEnabled() && ! $this->isPending())
                ->action(function (): void {
                    app(EnableTwoFactorAuthentication::class)($this->user());
                }),

            Action::make('confirm')
                ->label(__('identity::identity.two_factor.actions.confirm'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (): bool => $this->isPending())
                ->schema([
                    TextInput::make('code')
                        ->label(__('identity::identity.two_factor.fields.code'))
                        ->required()
                        ->extraAttributes(['class' => 'fc-code']),
                ])
                ->action(function (array $data): void {
                    try {
                        app(ConfirmTwoFactorAuthentication::class)($this->user(), $data['code']);
                    } catch (ValidationException) {
                        Notification::make()
                            ->danger()
                            ->title(__('identity::identity.two_factor.notifications.invalid_code'))
                            ->send();

                        return;
                    }

                    $this->recoveryCodes = $this->user()->recoveryCodes();

                    activity('security')->performedOn($this->user())->log('two_factor_enabled');

                    Notification::make()
                        ->success()
                        ->title(__('identity::identity.two_factor.notifications.enabled'))
                        ->send();
                }),

            Action::make('regenerate')
                ->label(__('identity::identity.two_factor.actions.regenerate'))
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->visible(fn (): bool => $this->isEnabled())
                ->requiresConfirmation()
                ->action(function (): void {
                    app(GenerateNewRecoveryCodes::class)($this->user());

                    $this->recoveryCodes = $this->user()->fresh()->recoveryCodes();

                    activity('security')->performedOn($this->user())->log('two_factor_codes_regenerated');

                    Notification::make()
                        ->success()
                        ->title(__('identity::identity.two_factor.notifications.regenerated'))
                        ->send();
                }),

            Action::make('disable')
                ->label(__('identity::identity.two_factor.actions.disable'))
                ->icon('heroicon-o-shield-exclamation')
                ->color('danger')
                ->visible(fn (): bool => $this->isEnabled() || $this->isPending())
                ->requiresConfirmation()
                ->schema([
                    TextInput::make('password')
                        ->label(__('identity::identity.two_factor.fields.password'))
                        ->password()
                        ->required(),
                ])
                ->action(function (array $data): void {
                    if (! Hash::check($data['password'], $this->user()->password)) {
                        Notification::make()->danger()->title(__('common.error'))->send();

                        return;
                    }

                    app(DisableTwoFactorAuthentication::class)($this->user());
                    $this->recoveryCodes = null;

                    activity('security')->performedOn($this->user())->log('two_factor_disabled');

                    Notification::make()
                        ->success()
                        ->title(__('identity::identity.two_factor.notifications.disabled'))
                        ->send();
                }),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }
}
