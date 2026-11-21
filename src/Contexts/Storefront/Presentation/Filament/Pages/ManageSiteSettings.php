<?php

declare(strict_types=1);

namespace Src\Contexts\Storefront\Presentation\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Gate;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Settings\Presentation\Filament\Clusters\TenantSettingsCluster;
use Src\Contexts\Storefront\Application\Actions\UpdateSiteSettingsAction;
use Src\Contexts\Storefront\Application\Queries\SiteSettings;
use Src\Support\Application\Contracts\DiskResolver;

/**
 * «إعدادات الموقع» — تبويب في إعدادات المؤسسة (مش عنصر سايدبار، CLAUDE.md).
 * كل حقل هنا ليه قارئ في `SiteSettingsData` والموقع العام بيستخدمه.
 * شارة «مطوّر بواسطة طفرة» مش إعداد — ثابتة على كل صفحة.
 *
 * @property-read Schema $form
 */
final class ManageSiteSettings extends Page
{
    private const HEX = '/^#[0-9A-Fa-f]{6}$/';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $cluster = TenantSettingsCluster::class;

    protected static ?string $slug = 'site';

    protected static ?int $navigationSort = 40;

    public static function getNavigationLabel(): string
    {
        return __('storefront::storefront.settings.page');
    }

    public function getTitle(): string
    {
        return __('storefront::storefront.settings.page');
    }

    public function getSubheading(): string
    {
        return __('storefront::storefront.settings.subheading');
    }

    public static function canAccess(): bool
    {
        return Gate::allows('access.system') && Gate::allows('access.site_settings');
    }

    public function mount(): void
    {
        $this->form->fill(app(SiteSettings::class)->values());
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        $t = static fn (string $key): string => __("storefront::storefront.settings.{$key}");

        return $schema->components([
            Section::make($t('sections.identity'))
                ->description($t('sections.identity_body'))
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('platform_name')->label($t('fields.platform_name'))->helperText($t('help.platform_name'))->maxLength(80),
                    TextInput::make('teacher_name')->label($t('fields.teacher_name'))->maxLength(80),
                    TextInput::make('subject')->label($t('fields.subject'))->placeholder($t('placeholders.subject'))->maxLength(60),
                    FileUpload::make('teacher_photo')
                        ->label($t('fields.teacher_photo'))
                        ->helperText($t('help.teacher_photo'))
                        ->image()
                        ->disk(fn (): string => app(DiskResolver::class)->for('branding'))
                        ->directory('site')
                        ->visibility('public')
                        // SVG ممنوع: XML بيقبل سكربتات (docs/20 بند ٧)
                        ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                        ->maxSize(2048),
                ]),

            Section::make($t('sections.hero'))
                ->columns(3)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('hero_headline')->label($t('fields.hero_headline'))->helperText($t('help.hero_headline'))->maxLength(120)->columnSpanFull(),
                    Textarea::make('hero_description')->label($t('fields.hero_description'))->rows(3)->maxLength(300)->columnSpanFull(),
                    TextInput::make('hero_chip_1')->label($t('fields.hero_chip_1'))->maxLength(40),
                    TextInput::make('hero_chip_2')->label($t('fields.hero_chip_2'))->maxLength(40),
                    TextInput::make('hero_chip_3')->label($t('fields.hero_chip_3'))->maxLength(40),
                    Textarea::make('about_text')->label($t('fields.about_text'))->rows(4)->maxLength(1500)->columnSpanFull(),
                    Toggle::make('show_stats')->label($t('fields.show_stats'))->helperText($t('help.show_stats'))->columnSpanFull(),
                ]),

            Section::make($t('sections.contact'))
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('facebook_url')->label($t('fields.facebook_url'))->url()->maxLength(255),
                    TextInput::make('youtube_url')->label($t('fields.youtube_url'))->url()->maxLength(255),
                    TextInput::make('tiktok_url')->label($t('fields.tiktok_url'))->url()->maxLength(255),
                    TextInput::make('instagram_url')->label($t('fields.instagram_url'))->url()->maxLength(255),
                    TextInput::make('telegram_url')->label($t('fields.telegram_url'))->url()->maxLength(255),
                    TextInput::make('whatsapp_number')->label($t('fields.whatsapp_number'))->helperText($t('help.whatsapp_number'))->tel()->regex('/^\+?[0-9\s-]{8,20}$/')->maxLength(20),
                    Toggle::make('floating_whatsapp')->label($t('fields.floating_whatsapp'))->columnSpanFull(),
                ]),

            Section::make($t('sections.look'))
                ->columns(3)
                ->columnSpanFull()
                ->schema([
                    ColorPicker::make('primary_color')->label($t('fields.primary_color'))->hex()->regex(self::HEX)->required(),
                    ColorPicker::make('secondary_color')->label($t('fields.secondary_color'))->hex()->regex(self::HEX)->required(),
                    Select::make('font_family')
                        ->label($t('fields.font_family'))
                        ->options(array_combine(
                            array_keys((array) config('storefront.fonts')),
                            array_keys((array) config('storefront.fonts')),
                        ))
                        ->native(false)
                        ->required(),
                ]),

            Section::make($t('sections.registration'))
                ->description($t('sections.registration_body'))
                ->columns(3)
                ->columnSpanFull()
                ->schema([
                    Toggle::make('parent_phone_required')->label($t('fields.parent_phone_required')),
                    Toggle::make('ask_governorate')->label($t('fields.ask_governorate')),
                    Toggle::make('ask_school_type')->label($t('fields.ask_school_type')),
                ]),

            Section::make($t('sections.footer'))
                ->columnSpanFull()
                ->schema([
                    Textarea::make('footer_text')->label($t('fields.footer_text'))->rows(2)->maxLength(300),
                    Text::make($t('help.tafra_credit')),
                ]),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')
                            ->label(__('common.save'))
                            ->submit('save')
                            ->keyBindings(['mod+s']),
                        Action::make('view_site')
                            ->label(__('storefront::storefront.settings.view_site'))
                            ->url(fn (): string => route('site.home'))
                            ->openUrlInNewTab()
                            ->color('gray'),
                    ])->key('form-actions'),
                ]),
        ]);
    }

    public function save(): void
    {
        $actor = auth()->user();

        abort_unless($actor instanceof User, 403);

        /** @var array<string, mixed> $state */
        $state = $this->form->getState();

        app(UpdateSiteSettingsAction::class)->handle($state, $actor);

        Notification::make()
            ->success()
            ->title(__('settings::settings.saved'))
            ->body(__('storefront::storefront.settings.saved_body'))
            ->send();
    }
}
