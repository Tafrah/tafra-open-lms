<?php

declare(strict_types=1);

namespace Src\Contexts\Storefront\Application\DataObjects;

use Illuminate\Support\Facades\Storage;
use Src\Support\Application\Contracts\DiskResolver;

/**
 * إعدادات الموقع بعد التنظيف — الألوان والخط والروابط اتأكد منها هنا
 * عشان أي قيمة غريبة في القاعدة ماتوصلش لـ `<style>` ولا لـ `href`.
 */
final readonly class SiteSettingsData
{
    private const HEX = '/^#[0-9A-Fa-f]{6}$/';

    /**
     * @param list<string> $chips
     * @param array<string, string> $socials
     */
    public function __construct(
        public string $platformName,
        public ?string $teacherName,
        public ?string $subject,
        public ?string $heroHeadline,
        public ?string $heroDescription,
        public ?string $teacherPhotoUrl,
        public array $chips,
        public ?string $aboutText,
        public bool $showStats,
        public array $socials,
        public ?string $whatsappNumber,
        public bool $floatingWhatsapp,
        public string $primaryColor,
        public string $secondaryColor,
        public string $fontFamily,
        public ?string $footerText,
        public bool $parentPhoneRequired,
        public bool $askGovernorate,
        public bool $askSchoolType,
    ) {}

    /** @param array<string, mixed> $values */
    public static function fromArray(array $values, ?string $tenantName = null): self
    {
        $text = static fn (string $key): ?string => filled($values[$key] ?? null) ? trim((string) $values[$key]) : null;

        $socials = [];

        foreach (['facebook', 'youtube', 'tiktok', 'instagram', 'telegram'] as $network) {
            $url = $text("{$network}_url");

            if ($url !== null && preg_match('~^https?://~i', $url) === 1 && filter_var($url, FILTER_VALIDATE_URL) !== false) {
                $socials[$network] = $url;
            }
        }

        $whatsapp = preg_replace('/\D+/', '', (string) ($values['whatsapp_number'] ?? '')) ?? '';

        if ($whatsapp !== '') {
            // رقم مصري محلي ← دولي عشان wa.me
            $whatsapp = str_starts_with($whatsapp, '0') ? '2'.$whatsapp : $whatsapp;
            $socials['whatsapp'] = 'https://wa.me/'.$whatsapp;
        }

        $fonts = array_keys((array) config('storefront.fonts', []));
        $font = (string) ($values['font_family'] ?? '');

        return new self(
            platformName: $text('platform_name') ?? ($tenantName !== null && $tenantName !== '' ? $tenantName : (string) config('app.name')),
            teacherName: $text('teacher_name'),
            subject: $text('subject'),
            heroHeadline: $text('hero_headline'),
            heroDescription: $text('hero_description'),
            teacherPhotoUrl: self::photoUrl($text('teacher_photo')),
            chips: array_values(array_filter([$text('hero_chip_1'), $text('hero_chip_2'), $text('hero_chip_3')])),
            aboutText: $text('about_text'),
            showStats: (bool) ($values['show_stats'] ?? true),
            socials: $socials,
            whatsappNumber: $whatsapp !== '' ? $whatsapp : null,
            floatingWhatsapp: (bool) ($values['floating_whatsapp'] ?? true),
            primaryColor: self::color($values['primary_color'] ?? null, 'primary_color'),
            secondaryColor: self::color($values['secondary_color'] ?? null, 'secondary_color'),
            fontFamily: in_array($font, $fonts, true) ? $font : (string) config('storefront.defaults.font_family'),
            footerText: $text('footer_text'),
            parentPhoneRequired: (bool) ($values['parent_phone_required'] ?? false),
            askGovernorate: (bool) ($values['ask_governorate'] ?? true),
            askSchoolType: (bool) ($values['ask_school_type'] ?? true),
        );
    }

    public function whatsappUrl(): ?string
    {
        return $this->socials['whatsapp'] ?? null;
    }

    /** معامل Google Fonts للخط المختار */
    public function fontQuery(): string
    {
        return (string) config("storefront.fonts.{$this->fontFamily}", config('storefront.fonts.Almarai'));
    }

    private static function color(mixed $value, string $key): string
    {
        return is_string($value) && preg_match(self::HEX, $value) === 1
            ? $value
            : (string) config("storefront.defaults.{$key}");
    }

    private static function photoUrl(?string $path): ?string
    {
        if ($path === null || str_contains($path, '..')) {
            return null;
        }

        try {
            return Storage::disk(app(DiskResolver::class)->for('branding'))->url($path);
        } catch (\Throwable) {
            return null;
        }
    }
}
