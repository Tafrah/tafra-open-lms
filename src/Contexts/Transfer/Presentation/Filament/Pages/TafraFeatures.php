<?php

declare(strict_types=1);

namespace Src\Contexts\Transfer\Presentation\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Panel;
use Illuminate\Support\Facades\Gate;
use Src\Support\Presentation\Filament\Navigation\NavigationGroup;

/**
 * «مميزات طفرة» — الحاجات اللي في طفرة ومش في النسخة المفتوحة، بهدوء:
 * صفحة واحدة في آخر السايدبار، كل ميزة بتعمل إيه وزرار تواصل. مفيش
 * إعلانات في باقي الشاشات (docs/product/plan.html بند ٩).
 */
final class TafraFeatures extends Page
{
    /** مفاتيح المميزات بالترتيب — النصوص في transfer::transfer.tafra.features.* */
    public const FEATURES = [
        'payments' => 'heroicon-o-credit-card',
        'whatsapp' => 'heroicon-o-chat-bubble-left-right',
        'app' => 'heroicon-o-device-phone-mobile',
        'paper_exams' => 'heroicon-o-document-check',
        'attendance' => 'heroicon-o-calendar-days',
        'ai' => 'heroicon-o-sparkles',
        'store' => 'heroicon-o-shopping-bag',
        'support' => 'heroicon-o-lifebuoy',
    ];

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-sparkles';

    protected static ?int $navigationSort = 1;

    protected string $view = 'transfer::filament.tafra-features';

    public static function getSlug(?Panel $panel = null): string
    {
        return 'tafra';
    }

    public static function canAccess(): bool
    {
        return (bool) config('transfer.tafra.show_features', true) && Gate::allows('access.data_transfer');
    }

    public static function getNavigationGroup(): string
    {
        return NavigationGroup::Tafra->getLabel();
    }

    public static function getNavigationLabel(): string
    {
        return __('transfer::transfer.tafra.title');
    }

    public function getTitle(): string
    {
        return __('transfer::transfer.tafra.title');
    }

    public function getSubheading(): string
    {
        return __('transfer::transfer.tafra.subheading');
    }

    public static function contactUrl(?string $feature = null): string
    {
        $whatsapp = preg_replace('/\D+/', '', (string) config('transfer.tafra.whatsapp')) ?? '';

        if ($whatsapp !== '') {
            $text = __('transfer::transfer.tafra.whatsapp_message', [
                'feature' => $feature !== null ? __("transfer::transfer.tafra.features.{$feature}.title") : __('transfer::transfer.tafra.title'),
            ]);

            return 'https://wa.me/'.$whatsapp.'?text='.rawurlencode($text);
        }

        return (string) config('transfer.tafra.url');
    }
}
