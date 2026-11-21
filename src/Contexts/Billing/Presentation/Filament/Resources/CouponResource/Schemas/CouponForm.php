<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Presentation\Filament\Resources\CouponResource\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;
use Src\Contexts\Academy\Domain\Models\Bootcamp;
use Src\Contexts\Billing\Domain\Enums\CouponScope;
use Src\Contexts\Billing\Domain\Enums\CouponType;
use Src\Contexts\Billing\Domain\Models\Coupon;
use Src\Support\Application\Contracts\TenantContext;

final class CouponForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextInput::make('code')
                        ->label(__('billing::billing.coupon.fields.code'))
                        ->required()
                        ->maxLength(64)
                        ->alphaDash()
                        ->dehydrateStateUsing(fn (?string $state): string => mb_strtoupper(trim((string) $state)))
                        ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule): Unique => $rule->where('tenant_id', app(TenantContext::class)->id())),
                    TextInput::make('name')
                        ->label(__('billing::billing.coupon.fields.name'))
                        ->maxLength(255),
                    Select::make('type')
                        ->label(__('billing::billing.coupon.fields.type'))
                        ->options(CouponType::class)
                        ->default(CouponType::Percentage)
                        ->native(false)
                        ->live()
                        ->required(),
                    TextInput::make('value')
                        ->label(__('billing::billing.coupon.fields.value'))
                        ->numeric()
                        ->minValue(0.01)
                        ->maxValue(fn (Get $get): ?int => self::isPercentage($get) ? 100 : null)
                        ->suffix(fn (Get $get): string => self::isPercentage($get) ? '%' : __('academy::academy.currency'))
                        ->required(),
                    TextInput::make('max_discount_amount')
                        ->label(__('billing::billing.coupon.fields.max_discount_amount'))
                        ->numeric()
                        ->minValue(0)
                        ->visible(fn (Get $get): bool => self::isPercentage($get)),
                    TextInput::make('max_redemptions')
                        ->label(__('billing::billing.coupon.fields.max_redemptions'))
                        ->helperText(__('billing::billing.coupon.help.unlimited'))
                        ->numeric()
                        ->minValue(1),
                    TextInput::make('max_per_user')
                        ->label(__('billing::billing.coupon.fields.max_per_user'))
                        ->numeric()
                        ->minValue(1)
                        ->default(1),
                    Select::make('applicable_type')
                        ->label(__('billing::billing.coupon.fields.applicable_type'))
                        ->options(CouponScope::class)
                        ->default(CouponScope::All)
                        ->native(false)
                        ->live()
                        ->required(),
                    Select::make('bootcamp_ids')
                        ->label(__('billing::billing.coupon.fields.bootcamps'))
                        ->multiple()
                        ->options(fn (): array => Bootcamp::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->required(fn (Get $get): bool => self::scope($get) === CouponScope::Specific)
                        ->visible(fn (Get $get): bool => self::scope($get) === CouponScope::Specific)
                        ->afterStateHydrated(function (Select $component, ?Coupon $record): void {
                            if ($record !== null) {
                                $component->state($record->items()->where('item_type', 'bootcamp')->pluck('item_id')->all());
                            }
                        })
                        ->saveRelationshipsUsing(function (Coupon $record, ?array $state, Get $get): void {
                            $ids = self::scope($get) === CouponScope::Specific ? array_map('intval', (array) $state) : [];
                            $record->items()->where('item_type', 'bootcamp')->whereNotIn('item_id', $ids)->delete();

                            foreach ($ids as $id) {
                                $record->items()->firstOrCreate(['item_type' => 'bootcamp', 'item_id' => $id]);
                            }
                        })
                        ->dehydrated(false)
                        ->columnSpanFull(),
                    DateTimePicker::make('starts_at')
                        ->label(__('billing::billing.coupon.fields.starts_at'))
                        ->seconds(false),
                    DateTimePicker::make('expires_at')
                        ->label(__('billing::billing.coupon.fields.expires_at'))
                        ->seconds(false)
                        ->after('starts_at'),
                    Toggle::make('is_active')
                        ->label(__('billing::billing.coupon.fields.is_active'))
                        ->default(true),
                ]),
        ]);
    }

    private static function isPercentage(Get $get): bool
    {
        $type = $get('type');

        return ($type instanceof CouponType ? $type : CouponType::tryFrom((string) $type)) === CouponType::Percentage;
    }

    private static function scope(Get $get): ?CouponScope
    {
        $scope = $get('applicable_type');

        return $scope instanceof CouponScope ? $scope : CouponScope::tryFrom((string) $scope);
    }
}
