<?php

declare(strict_types=1);

namespace Src\Contexts\Ai\Presentation\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Src\Contexts\Ai\Application\Actions\RevokeAiConnectionAction;
use Src\Contexts\Ai\Application\Services\AiAvailability;
use Src\Contexts\Ai\Domain\Models\AiConnection;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Support\Application\Contracts\TenantContext;

/**
 * «تطبيقات الذكاء الاصطناعي المربوطة» — رابط الخادم وإزاي تربط، وكل تطبيق
 * مربوط وآخر استخدام ليه، وزرار فصل بيقفل التوكن فوراً (docs/23 بند ٤).
 *
 * كل مستخدم بيشوف ويلغي روابطه هو دايماً. مدير المؤسسة (`view_any`) بيشوف
 * روابط كل الأعضاء في المؤسسة دي وبيقدر يفصلها.
 */
final class AiConnections extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-sparkles';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'ai::filament.pages.ai-connections';

    public static function getSlug(?Panel $panel = null): string
    {
        return 'ai-connections';
    }

    /**
     * اللي معاه `access.ai` — أو عنده رابط نشط لسه (صلاحيته اتشالت بعد ما
     * ربط): الفصل لازم يفضل متاح دايماً.
     */
    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        return Gate::forUser($user)->allows('access.ai')
            || AiConnection::query()->active()->where('user_id', $user->getKey())->exists();
    }

    public function getTitle(): string
    {
        return __('ai::ai.connections.title');
    }

    public function getSubheading(): string
    {
        return __('ai::ai.connections.description');
    }

    /** رابط الخادم اللي المستخدم بيلزقه في Claude أو ChatGPT */
    public function serverUrl(): string
    {
        return url('/'.trim((string) config('ai.path', 'mcp'), '/'));
    }

    /** الإرشادات تظهر بس لو الربط ممكن فعلاً من المؤسسة دي */
    public function canConnect(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User
            && Gate::forUser($user)->allows('access.ai')
            && app(AiAvailability::class)->enabledFor(app(TenantContext::class)->id());
    }

    public function table(Table $table): Table
    {
        $canSeeAll = Gate::allows('viewAny', AiConnection::class);

        return $table
            ->query(fn (): Builder => $this->connectionsQuery($canSeeAll))
            ->defaultSort('created_at', 'desc')
            ->deferLoading(false)
            ->columns([
                TextColumn::make('client.name')
                    ->label(__('ai::ai.connections.fields.app'))
                    ->weight(FontWeight::Medium)
                    ->placeholder(__('ai::ai.connections.unknown_app')),

                TextColumn::make('user.name')
                    ->label(__('ai::ai.connections.fields.member'))
                    ->visible($canSeeAll),

                TextColumn::make('access')
                    ->label(__('ai::ai.connections.fields.access'))
                    ->badge(),

                TextColumn::make('last_used_at')
                    ->label(__('ai::ai.connections.fields.last_used'))
                    ->since()
                    ->placeholder(__('ai::ai.connections.never_used'))
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label(__('ai::ai.connections.fields.connected_at'))
                    ->dateTime('d M Y — H:i')
                    ->sortable(),

                TextColumn::make('revoked_at')
                    ->label(__('ai::ai.connections.fields.status'))
                    ->badge()
                    ->getStateUsing(fn (AiConnection $record): string => $record->isActive()
                        ? __('ai::ai.connections.status.active')
                        : __('ai::ai.connections.status.revoked'))
                    ->color(fn (AiConnection $record): string => $record->isActive() ? 'success' : 'gray'),
            ])
            ->filters([
                TernaryFilter::make('active')
                    ->label(__('ai::ai.connections.filters.active'))
                    ->default(true)
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereNull('revoked_at'),
                        false: fn (Builder $query): Builder => $query->whereNotNull('revoked_at'),
                    ),

                SelectFilter::make('scope')
                    ->label(__('ai::ai.connections.filters.scope'))
                    ->options([
                        'mine' => __('ai::ai.connections.filters.mine'),
                        'organization' => __('ai::ai.connections.filters.organization'),
                    ])
                    ->default('mine')
                    ->visible($canSeeAll)
                    ->query(fn (Builder $query, array $data): Builder => ($data['value'] ?? 'mine') === 'mine'
                        ? $query->where('user_id', auth()->id())
                        : $query),
            ])
            ->recordActions([
                Action::make('revoke')
                    ->label(__('ai::ai.connections.actions.revoke'))
                    ->icon('heroicon-o-link-slash')
                    ->color('danger')
                    ->authorize('delete')
                    ->requiresConfirmation()
                    ->modalHeading(__('ai::ai.connections.confirm.heading'))
                    ->modalDescription(__('ai::ai.connections.confirm.body'))
                    ->action(function (AiConnection $record): void {
                        app(RevokeAiConnectionAction::class)->handle($record, $this->actor());

                        Notification::make()
                            ->success()
                            ->title(__('ai::ai.connections.notifications.revoked'))
                            ->send();
                    }),
            ])
            ->emptyStateHeading(__('ai::ai.connections.empty.heading'))
            ->emptyStateDescription(__('ai::ai.connections.empty.description'))
            ->emptyStateIcon('heroicon-o-sparkles');
    }

    /**
     * روابطي في كل المؤسسات — ولمدير المؤسسة: روابط أعضاء المؤسسة دي كمان.
     *
     * @return Builder<AiConnection>
     */
    private function connectionsQuery(bool $canSeeAll): Builder
    {
        $userId = auth()->id();
        $tenantId = app(TenantContext::class)->id();

        return AiConnection::query()
            ->with(['client:id,name', 'user:id,name'])
            ->where(function (Builder $query) use ($userId, $tenantId, $canSeeAll): void {
                $query->where('user_id', $userId);

                if ($canSeeAll && $tenantId !== null) {
                    $query->orWhere('tenant_id', $tenantId);
                }
            });
    }

    private function actor(): User
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
