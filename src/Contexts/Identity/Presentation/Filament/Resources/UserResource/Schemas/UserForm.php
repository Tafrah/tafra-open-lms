<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Presentation\Filament\Resources\UserResource\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Src\Contexts\Identity\Domain\Enums\UserStatus;
use Src\Contexts\Identity\Domain\Models\Role;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Identity\Infrastructure\Rules\NotARecentPassword;
use Src\Support\Application\Contracts\DiskResolver;
use Src\Support\Application\Contracts\TenantContext;
use Src\Support\Infrastructure\Authorization\CentralRoles;

final class UserForm
{
    /** الحقول الحساسة: القدرة على سجل قائم غير القدرة على الإنشاء. */
    public static function mayWriteStatus(?User $record): bool
    {
        $actor = auth()->user();

        if ($actor === null) {
            return false;
        }

        return $record === null
            ? $actor->can('create', User::class)
            : $actor->can('update', $record);
    }

    public static function mayManageRoles(?User $record): bool
    {
        $actor = auth()->user();

        if ($actor === null) {
            return false;
        }

        return $record === null
            ? $actor->can('create', User::class)
            : $actor->can('manageRoles', $record);
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('identity::identity.user.sections.identity'))
                ->description(__('identity::identity.user.sections.identity_help'))
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('name')
                        ->label(__('identity::identity.user.fields.name'))
                        ->required()
                        ->maxLength(120),

                    TextInput::make('email')
                        ->label(__('identity::identity.user.fields.email'))
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(180),

                    TextInput::make('phone')
                        ->label(__('identity::identity.user.fields.phone'))
                        ->tel()
                        ->maxLength(32)
                        ->helperText(__('identity::identity.user.help.phone')),

                    Select::make('locale')
                        ->label(__('common.locale'))
                        ->options(fn (): array => collect((array) config('app.supported_locales'))
                            ->mapWithKeys(fn (string $locale): array => [$locale => __("common.locales.{$locale}")])
                            ->all())
                        ->default(config('app.locale'))
                        ->native(false),

                    SpatieMediaLibraryFileUpload::make('avatar')
                        ->label(__('identity::identity.user.fields.avatar'))
                        ->collection('avatar')
                        ->disk(fn (): string => app(DiskResolver::class)->for('avatar'))
                        ->image()
                        ->imageEditor()
                        ->imageEditorAspectRatios(['1:1'])
                        ->maxSize(2048)
                        // قائمة MIME بيضاء — والتحقق بيتكرر على السيرفر
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->columnSpanFull(),
                ]),

            Section::make(__('identity::identity.user.sections.access'))
                ->description(__('identity::identity.user.sections.access_help'))
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('password')
                        ->label(__('identity::identity.user.fields.password'))
                        ->password()
                        ->revealable()
                        ->rule(Password::default())
                        // منع إعادة استخدام آخر N كلمة مرور
                        ->rule(fn (?User $record): NotARecentPassword => new NotARecentPassword($record))
                        ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Hash::make($state) : null)
                        ->dehydrated(fn (?string $state): bool => filled($state))
                        ->required(fn (string $operation): bool => $operation === 'create')
                        ->helperText(__('identity::identity.user.help.password')),

                    Select::make('status')
                        ->label(__('common.status'))
                        ->options(UserStatus::class)
                        ->default(UserStatus::Invited)
                        ->required()
                        ->native(false)
                        // حقل حساس: الإخفاء UX، ومنع الحفظ هو الأمان
                        // القدرة بتتغيّر حسب السياق: إنشاء سجل جديد ≠ تعديل سجل قائم
                        ->visible(fn (?User $record): bool => UserForm::mayWriteStatus($record))
                        ->saved(fn (?User $record): bool => UserForm::mayWriteStatus($record)),

                    Toggle::make('is_active')
                        ->label(__('identity::identity.user.fields.is_active'))
                        ->default(true)
                        ->visible(fn (?User $record): bool => UserForm::mayWriteStatus($record))
                        ->saved(fn (?User $record): bool => UserForm::mayWriteStatus($record)),

                    Select::make('roles')
                        ->label(__('identity::identity.user.fields.roles'))
                        ->multiple()
                        ->native(false)
                        ->preload()
                        // أدوار فريق التشغيل مركزية — مابتتسندش من شاشة مؤسسة (docs/24 بند ٢)
                        ->options(fn (): array => Role::query()
                            ->whereNotIn('name', CentralRoles::catalog())
                            ->where(fn ($query) => $query
                                ->whereNull('roles.tenant_id')
                                ->orWhere('roles.tenant_id', app(TenantContext::class)->id()))
                            ->orderBy('name')
                            ->pluck('name', 'name')
                            ->map(static fn (string $name): string => __("authorization.roles.{$name}"))
                            ->all())
                        ->afterStateHydrated(function (Select $component, ?User $record): void {
                            $component->state($record?->getRoleNames()->all() ?? []);
                        })
                        // ⚠️ الإخفاء لوحده مش أمان — القيمة بتوصل في الـ request.
                        // saved(false) بيمنع وصولها للـ Page أصلاً.
                        ->visible(fn (?User $record): bool => UserForm::mayManageRoles($record))
                        ->saved(fn (?User $record): bool => UserForm::mayManageRoles($record))
                        ->helperText(__('identity::identity.user.help.roles'))
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
