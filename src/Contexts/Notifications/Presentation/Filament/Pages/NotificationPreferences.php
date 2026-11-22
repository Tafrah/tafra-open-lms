<?php

declare(strict_types=1);

namespace Src\Contexts\Notifications\Presentation\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;
use Illuminate\Support\Facades\Gate;
use Src\Contexts\Notifications\Domain\Models\NotificationPreference;
use Src\Support\Application\Contracts\TenantContext;

/**
 * تفضيلات الإشعارات — الكتالوج مجمّع حسب `group`، والقنوات الإجبارية
 * بتظهر مفعّلة ومعطّلة مع سبب (docs/09 بند ٣).
 */
final class NotificationPreferences extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bell-alert';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'notifications::filament.pages.notification-preferences';

    /** @var array<string, array<string, bool>> */
    public array $state = [];

    public static function getSlug(?Panel $panel = null): string
    {
        return 'notification-preferences';
    }

    public static function canAccess(): bool
    {
        return Gate::allows('access.notification_preferences');
    }

    public function getTitle(): string
    {
        return __('notifications::notifications.preferences.title');
    }

    public function getSubheading(): string
    {
        return __('notifications::notifications.preferences.description');
    }

    public function mount(): void
    {
        $this->state = $this->currentState();
    }

    /** @return array<string, array<string, mixed>> */
    public function catalog(): array
    {
        // إشعارات الطلبة (`audience => students`) مش لمستخدمي اللوحة
        return array_filter(
            (array) config('notifications.catalog', []),
            static fn (array $definition): bool => ($definition['audience'] ?? 'users') === 'users',
        );
    }

    /** @return array<string, array<string, array<string, mixed>>> */
    public function groupedCatalog(): array
    {
        $grouped = [];

        foreach ($this->catalog() as $key => $definition) {
            $grouped[$definition['group'] ?? 'system'][$key] = $definition;
        }

        return $grouped;
    }

    public function isRequired(string $key, string $channel): bool
    {
        return in_array($channel, $this->catalog()[$key]['required'] ?? [], true);
    }

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label(__('common.save'))
                ->icon('heroicon-o-check')
                ->action('save'),
        ];
    }

    public function save(): void
    {
        $tenantId = app(TenantContext::class)->id();

        foreach ($this->catalog() as $key => $definition) {
            $channels = [];

            foreach ($definition['channels'] ?? [] as $channel) {
                if ($this->isRequired($key, $channel) || ($this->state[$key][$channel] ?? false)) {
                    $channels[] = $channel;
                }
            }

            NotificationPreference::query()->updateOrCreate(
                [
                    'user_id' => auth()->id(),
                    'tenant_id' => $tenantId,
                    'notification_key' => $key,
                ],
                [
                    'channels' => $channels,
                    'enabled' => true,
                ],
            );
        }

        Notification::make()
            ->success()
            ->title(__('notifications::notifications.preferences.saved'))
            ->send();
    }

    /** @return array<string, array<string, bool>> */
    private function currentState(): array
    {
        $tenantId = app(TenantContext::class)->id();

        $saved = NotificationPreference::query()
            ->where('user_id', auth()->id())
            ->where(fn ($query) => $query->whereNull('tenant_id')->orWhere('tenant_id', $tenantId))
            ->get()
            ->keyBy('notification_key');

        $state = [];

        foreach ($this->catalog() as $key => $definition) {
            $preference = $saved->get($key);
            $selected = $preference === null ? ($definition['default'] ?? []) : $preference->channels;

            foreach ($definition['channels'] ?? [] as $channel) {
                $state[$key][$channel] = in_array($channel, $selected, true)
                    || $this->isRequired($key, $channel);
            }
        }

        return $state;
    }
}
