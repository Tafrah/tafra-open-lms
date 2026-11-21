# ٠٥ — الإعدادات الديناميكية

## المبدأ

أي قيمة ممكن مالك المنتج يعوز يغيّرها من غير ديبلوي = **إعداد**، مش كونفيج.

- اسم التطبيق، اللوجو، اللون الأساسي → إعداد
- سلسلة الاتصال بقاعدة البيانات → كونفيج
- الديسك الافتراضي → إعداد
- الـ PSR-4 namespace → كونفيج

---

## ١. التثبيت

```bash
composer require spatie/laravel-settings:^3.9
composer require filament/spatie-laravel-settings-plugin:"^5.7"
php artisan vendor:publish --provider="Spatie\LaravelSettings\LaravelSettingsServiceProvider" --tag="migrations"
php artisan migrate
```

في `config/settings.php`:

```php
'settings' => [
    Src\Contexts\Settings\Domain\Settings\GeneralSettings::class,
    Src\Contexts\Settings\Domain\Settings\AppearanceSettings::class,
    Src\Contexts\Settings\Domain\Settings\StorageSettings::class,
    Src\Contexts\Settings\Domain\Settings\MailSettings::class,
    Src\Contexts\Settings\Domain\Settings\NotificationSettings::class,
    Src\Contexts\Settings\Domain\Settings\SecuritySettings::class,
],

'cache' => [
    'enabled' => true,
    'store'   => 'redis',
    'prefix'  => 'fc_settings',
    'ttl'     => null,        // بدون انتهاء — بيتمسح عند الحفظ
],

'repositories' => [
    'database' => [
        'type'       => DatabaseSettingsRepository::class,
        'model'      => null,
        'table'      => null,
        'connection' => null,
    ],
],
```

---

## ٢. كلاسات الإعدادات

```php
namespace Src\Contexts\Settings\Domain\Settings;

use Spatie\LaravelSettings\Settings;

final class GeneralSettings extends Settings
{
    public array  $app_name;          // {"ar": "...", "en": "..."}
    public array  $app_description;
    public string $support_email;
    public string $support_phone;
    public string $default_locale;
    public string $timezone;
    public bool   $maintenance_mode;
    public array  $maintenance_message;

    public static function group(): string
    {
        return 'general';
    }

    /** التشفير للحقول الحساسة */
    public static function encrypted(): array
    {
        return [];
    }
}
```

```php
final class AppearanceSettings extends Settings
{
    public string  $primary_color;        // #12454F
    public ?string $logo_light_path;
    public ?string $logo_dark_path;
    public ?string $favicon_path;
    public string  $default_theme;        // light | dark | system
    public bool    $allow_theme_switch;
    public string  $sidebar_default;      // expanded | collapsed
    public array   $footer_text;          // نص الحقوق مترجم
    public string  $font_family;

    public static function group(): string { return 'appearance'; }
}
```

```php
final class StorageSettings extends Settings
{
    public string $default_disk;
    public string $private_disk;
    public string $conversions_disk;
    public array  $private_collections;   // ['documents', 'contracts']
    public array  $collection_disks;      // ['avatar' => 's3', ...]
    public int    $max_upload_size_kb;
    public array  $allowed_mimes;

    public static function group(): string { return 'storage'; }
}
```

```php
final class MailSettings extends Settings
{
    public string  $driver;
    public string  $host;
    public int     $port;
    public string  $username;
    public string  $password;      // ← مشفّر
    public string  $encryption;
    public string  $from_address;
    public array   $from_name;

    public static function group(): string { return 'mail'; }

    public static function encrypted(): array
    {
        return ['password'];
    }
}
```

> **مهم:** أي إعداد فيه سر (كلمة مرور SMTP، مفتاح API) لازم يكون في `encrypted()`.

---

## ٣. الميجريشنز

```php
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('appearance.primary_color', '#12454F');
        $this->migrator->add('appearance.default_theme', 'system');
        $this->migrator->add('appearance.allow_theme_switch', true);
        $this->migrator->add('appearance.sidebar_default', 'expanded');
        $this->migrator->add('appearance.footer_text', [
            'ar' => '© ٢٠٢٦ كود المستقبل — جميع الحقوق محفوظة',
            'en' => '© 2026 Future Code — All rights reserved',
        ]);
        $this->migrator->add('appearance.font_family', 'IBM Plex Sans Arabic');
        $this->migrator->add('appearance.logo_light_path', null);
        $this->migrator->add('appearance.logo_dark_path', null);
        $this->migrator->add('appearance.favicon_path', null);
    }
};
```

---

## ٤. الإعدادات على مستوى المستأجر

`spatie/laravel-settings` مفيهوش دعم مستأجرين جاهز. الحل عندنا **طبقتين**:

1. **إعدادات عامة** — بـ `spatie/laravel-settings` زي ما هي (تخص التثبيت كله)
2. **إعدادات المستأجر** — جدول `tenant_settings` بسيط + كلاس `TenantSettings` بيقرا منه مع fallback للعام

```php
Schema::create('tenant_settings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
    $table->string('group');
    $table->string('key');
    $table->json('value')->nullable();
    $table->timestamps();

    $table->unique(['tenant_id', 'group', 'key']);
});
```

```php
final class TenantSettings
{
    public function __construct(
        private readonly TenantContext $context,
        private readonly CacheRepository $cache,
    ) {}

    public function get(string $group, string $key, mixed $default = null): mixed
    {
        $tenantId = $this->context->id();

        if ($tenantId === null) {
            return $this->global($group, $key, $default);
        }

        return $this->cache
            ->tags(['settings', "tenant:{$tenantId}"])
            ->rememberForever("settings:{$tenantId}:{$group}:{$key}", function () use ($tenantId, $group, $key, $default) {
                $row = TenantSetting::query()
                    ->where('tenant_id', $tenantId)
                    ->where('group', $group)
                    ->where('key', $key)
                    ->first();

                return $row?->value ?? $this->global($group, $key, $default);
            });
    }

    public function set(string $group, string $key, mixed $value): void
    {
        TenantSetting::updateOrCreate(
            ['tenant_id' => $this->context->id(), 'group' => $group, 'key' => $key],
            ['value' => $value],
        );

        $this->cache->tags(["tenant:{$this->context->id()}"])->flush();
    }
}
```

**القاعدة:** الإعدادات اللي المستأجر يقدر يغيّرها = المظهر والإشعارات بس. التخزين والبريد والأمان **عامة** (يديرها الـ super_admin).

**التنفيذ (`docs/24` بند ٦):** الإعدادات العامة كلها في `SettingsCluster` باللوحة
المركزية `/central`. لوحة المؤسسة فيها `TenantSettingsCluster` (مظهر + إشعارات)
بيكتب في `tenant_settings` عبر `UpdateTenantSettingsAction` — والـ Policy بترفض
أي مجموعة مش في `TENANT_EDITABLE_GROUPS` حتى للمدير العام. كل حقل في صفحة
المؤسسة ليه قارئ بيعدّي على `TenantSettings::get()` (الخط وتبديل الثيم في
`AdminPanelProvider`، القنوات في `NotificationChannelResolver`).

---

## ٥. صفحات الإعدادات في Filament

### التبويبات: عنصر واحد في السايدبار

ست صفحات إعدادات = ست عناصر في مجموعة «النظام»، وكل مشروع بيزوّد مجموعات
جديدة. عشان كده **الإعدادات عنصر واحد في السايدبار**، وجوّاه تبويب لكل مجموعة.

الأداة هي `Cluster` بتاع Filament مع `SubNavigationPosition::Top` — التبويبات
بتظهر فوق الصفحة (وبتتحوّل لقائمة منسدلة على الموبايل):

```php
namespace Src\Contexts\Settings\Presentation\Filament\Central\Clusters;

final class SettingsCluster extends Cluster
{
    protected static ?string $slug = 'settings';

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getNavigationLabel(): string
    {
        return __('settings::settings.cluster');
    }

    public static function getClusterBreadcrumb(): string
    {
        return __('settings::settings.cluster');     // الافتراضي بيتولّد من اسم الكلاس — نص مكتوب
    }

    public static function getNavigationGroup(): string
    {
        return NavigationGroup::System->getLabel();
    }

    // الـ Cluster الافتراضي بيخفي اللينك بس — ده اللي بيقفل الرابط (docs/21 بند ٢٩)
    public static function canAccess(): bool
    {
        return Gate::allows('access.system') && self::canAccessClusteredComponents();
    }
}
```

**ليه Cluster ومش `Tabs` في صفحة واحدة؟** كل مجموعة إعدادات ليها كلاس
`Settings` خاص، وصلاحية خاصة، وزرار حفظ خاص، وأزرار رأس خاصة («اختبار
الاتصال»، «رسالة تجريبية»). صفحة واحدة بـ `Tabs` كانت هتخلط ست حالات حفظ
في فورم واحد، وتخلّي التبويب المخفي مجرد إخفاء في الواجهة. مع الـ Cluster:

- كل تبويب **صفحة مستقلة** برابطها (`/settings/mail`) — روابط الأونبوردنج شغّالة زي ما هي
- كل تبويب بيتفلتر بـ `canAccess()` بتاع صفحته — المستخدم بيشوف اللي من حقه بس
- الرابط المباشر لتبويب مش من حقه = 403، مش صفحة فاضية

### إضافة تبويب جديد

```php
final class ManagePayments extends SettingsPage
{
    protected static ?string $cluster = SettingsCluster::class;   // ← ده اللي بيحطها تبويب

    protected static ?string $slug = 'payments';                  // مش getSlug() — الـ cluster بيزوّد `settings/` لوحده

    protected static ?int $navigationSort = 70;                   // ترتيب التبويب

    // ⚠️ من غير getNavigationGroup() — لو رجّعت مجموعة، التبويب بيتحط تحت عنوان فرعي

    public static function canAccess(): bool
    {
        return Gate::allows('access.system') && Gate::allows('manage_payments.settings');
    }
}
```

والصلاحية `manage_payments` في `extra` بتاع `settings` في `config/authorization.php` + ترجمتها.

### الصفحة نفسها

```php
namespace Src\Contexts\Settings\Presentation\Filament\Central\Pages;

use Filament\Pages\SettingsPage;
use Filament\Schemas\Schema;

final class ManageAppearance extends SettingsPage
{
    protected static ?string $navigationIcon = 'heroicon-o-paint-brush';

    protected static string $settings = AppearanceSettings::class;

    protected static ?string $cluster = SettingsCluster::class;

    protected static ?string $slug = 'appearance';

    public static function getNavigationLabel(): string
    {
        return __('settings.pages.appearance');
    }

    public static function canAccess(): bool
    {
        return Gate::allows('settings.manage_appearance');   // Gate معرّف من الكونفيج — docs/19 بند ٧
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('settings.sections.brand'))
                ->description(__('settings.sections.brand_help'))
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    ColorPicker::make('primary_color')
                        ->label(__('settings.fields.primary_color'))
                        ->hex()
                        ->required()
                        ->helperText(__('settings.help.primary_color')),

                    Select::make('font_family')
                        ->label(__('settings.fields.font'))
                        ->options([
                            'IBM Plex Sans Arabic' => 'IBM Plex Sans Arabic',
                            'Noto Sans Arabic'     => 'Noto Sans Arabic',
                            'Cairo'                => 'Cairo',
                        ])
                        ->required(),

                    FileUpload::make('logo_light_path')
                        ->label(__('settings.fields.logo_light'))
                        ->image()
                        ->directory('branding')
                        ->disk(fn () => app(DiskResolver::class)->for('branding')),

                    FileUpload::make('logo_dark_path')
                        ->label(__('settings.fields.logo_dark'))
                        ->image()
                        ->directory('branding')
                        ->disk(fn () => app(DiskResolver::class)->for('branding')),
                ]),

            Section::make(__('settings.sections.layout'))
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Select::make('default_theme')
                        ->label(__('settings.fields.default_theme'))
                        ->options([
                            'light'  => __('settings.themes.light'),
                            'dark'   => __('settings.themes.dark'),
                            'system' => __('settings.themes.system'),
                        ]),

                    Toggle::make('allow_theme_switch')
                        ->label(__('settings.fields.allow_theme_switch')),

                    Select::make('sidebar_default')
                        ->label(__('settings.fields.sidebar_default'))
                        ->options([
                            'expanded'  => __('settings.sidebar.expanded'),
                            'collapsed' => __('settings.sidebar.collapsed'),
                        ]),
                ]),

            Section::make(__('settings.sections.footer'))
                ->columnSpanFull()
                ->schema([
                    KeyValue::make('footer_text')
                        ->label(__('settings.fields.footer_text'))
                        ->keyLabel(__('common.locale'))
                        ->valueLabel(__('common.text'))
                        ->addable(false)
                        ->deletable(false),
                ]),
        ]);
    }

    protected function afterSave(): void
    {
        // مسح كاش الثيم والإعدادات
        cache()->tags(['settings', 'theme'])->flush();

        Notification::make()
            ->success()
            ->title(__('settings.saved'))
            ->body(__('settings.saved_body'))
            ->send();
    }
}
```

> ⚠️ في Filament v4/v5 لازم `->columnSpanFull()` صريح على `Section` و`Grid` و`Fieldset` — مبقاش افتراضي زي v3.

---

## ٦. حقن الإعدادات في الكونفيج وقت التشغيل

بعض الإعدادات لازم تروّح لكونفيج Laravel (البريد مثلاً). في مزوّد خدمة:

```php
final class DynamicConfigServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // متشتغلش أثناء الميجريشن أو قبل ما الجدول يتعمل
        if ($this->app->runningInConsole() && ! $this->settingsTableExists()) {
            return;
        }

        $mail = app(MailSettings::class);

        config([
            'mail.default'                     => $mail->driver,
            'mail.mailers.smtp.host'           => $mail->host,
            'mail.mailers.smtp.port'           => $mail->port,
            'mail.mailers.smtp.username'       => $mail->username,
            'mail.mailers.smtp.password'       => $mail->password,
            'mail.mailers.smtp.encryption'     => $mail->encryption,
            'mail.from.address'                => $mail->from_address,
            'mail.from.name'                   => $mail->from_name[app()->getLocale()] ?? config('app.name'),
        ]);
    }
}
```

> ⚠️ **مصيدة:** المزوّد ده بيعمل استعلام DB في كل طلب. الكاش (`settings.cache.enabled = true`) هو اللي بيخلّيه مقبول. تأكد إنه مفعّل على Redis.
>
> ⚠️ **مصيدة تانية:** لو الجدول مش موجود (أول `migrate`)، المزوّد هيكسر كل الأوامر. الحارس `settingsTableExists()` إلزامي.

---

## ٧. معايير القبول

- [ ] ٦ كلاسات إعدادات موجودة وكلها في `config/settings.php`
- [ ] كل الأسرار في `encrypted()` — مؤكد بمراجعة جدول `settings` في DB
- [ ] الكاش على Redis ومسح تلقائي بعد الحفظ
- [ ] تغيير اللون الأساسي من الصفحة بيغيّر اللوحة بعد refresh
- [ ] تغيير إعدادات البريد بيأثر على الرسالة الجاية من غير ديبلوي
- [ ] `php artisan migrate:fresh` بيعدّي من غير أخطاء (حارس الجدول شغّال)
- [ ] الإعدادات المترجمة (اسم التطبيق، الفوتر) بتتغيّر مع تغيير اللغة
- [ ] صفحات الإعدادات محمية بصلاحيات منفصلة لكل مجموعة
- [ ] الإعدادات عنصر واحد في السايدبار، والمجموعات تبويبات جوّاه
- [ ] المستخدم بيشوف التبويبات اللي من حقه بس، والرابط المباشر لغيرها 403
- [ ] رابط الإعدادات نفسه 403 لمستخدم مالوش ولا تبويب
