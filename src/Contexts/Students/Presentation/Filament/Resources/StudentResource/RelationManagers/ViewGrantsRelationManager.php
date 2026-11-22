<?php

declare(strict_types=1);

namespace Src\Contexts\Students\Presentation\Filament\Resources\StudentResource\RelationManagers;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Src\Contexts\Academy\Application\Actions\GrantExtraViewsAction;
use Src\Contexts\Academy\Domain\Models\BootcampSectionItem;
use Src\Contexts\Academy\Domain\Models\StudentItemViewGrant;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Students\Domain\Models\Student;
use Src\Support\Domain\Exceptions\DomainException;

/**
 * استثناءات المشاهدات للطالب ده: درس محدود المشاهدات + كام مشاهدة زيادة ليه هو بس.
 */
final class ViewGrantsRelationManager extends RelationManager
{
    protected static string $relationship = 'viewGrants';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('academy::academy.view_grant.plural');
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['item.section.bootcamp:id,name', 'grantor:id,name'])->latest('id'))
            ->columns([
                TextColumn::make('item.name')
                    ->label(__('academy::academy.view_grant.fields.item'))
                    ->description(fn (StudentItemViewGrant $record): ?string => $record->item?->section?->bootcamp?->name)
                    ->wrap(),
                TextColumn::make('item.allowed_views_number')
                    ->label(__('academy::academy.view_grant.fields.allowed'))
                    ->placeholder('∞'),
                TextColumn::make('extra_views')
                    ->label(__('academy::academy.view_grant.fields.extra_views'))
                    ->prefix('+'),
                TextColumn::make('grantor.name')
                    ->label(__('academy::academy.view_grant.fields.granted_by'))
                    ->placeholder('—'),
                TextColumn::make('updated_at')
                    ->label(__('common.updated_at'))
                    ->dateTime('d M Y — H:i'),
            ])
            ->headerActions([
                Action::make('grantViews')
                    ->label(__('academy::academy.view_grant.actions.grant'))
                    ->icon('heroicon-o-eye')
                    ->visible(fn (): bool => auth()->user()?->can('grantViews', $this->getOwnerRecord()) ?? false)
                    ->schema([
                        Select::make('item_id')
                            ->label(__('academy::academy.view_grant.fields.item'))
                            ->helperText(__('academy::academy.view_grant.help.item'))
                            ->options(fn (): array => self::limitedLessons())
                            ->searchable()
                            ->required(),
                        TextInput::make('extra_views')
                            ->label(__('academy::academy.view_grant.fields.extra_views'))
                            ->helperText(__('academy::academy.view_grant.help.extra_views'))
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->maxValue(GrantExtraViewsAction::MAX_PER_GRANT)
                            ->default(1)
                            ->required(),
                    ])
                    ->action(function (array $data, Action $action): void {
                        $item = BootcampSectionItem::query()->find($data['item_id'] ?? 0);

                        try {
                            app(GrantExtraViewsAction::class)->handle($this->student(), $item ?? new BootcampSectionItem, (int) $data['extra_views'], $this->actor());
                        } catch (DomainException $e) {
                            Notification::make()->danger()->title($e->getMessage())->send();
                            $action->halt();

                            return;
                        }

                        Notification::make()->success()->title(__('academy::academy.view_grant.notifications.granted'))->send();
                    }),
            ])
            ->recordActions([
                Action::make('revoke')
                    ->label(__('academy::academy.view_grant.actions.revoke'))
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (): bool => auth()->user()?->can('grantViews', $this->getOwnerRecord()) ?? false)
                    ->action(function (StudentItemViewGrant $record): void {
                        app(GrantExtraViewsAction::class)->revoke($record, $this->actor());

                        Notification::make()->success()->title(__('academy::academy.view_grant.notifications.revoked'))->send();
                    }),
            ])
            ->emptyStateHeading(__('academy::academy.view_grant.empty.heading'))
            ->emptyStateDescription(__('academy::academy.view_grant.empty.description'));
    }

    /** @return array<int, string> الدروس اللي ليها حد مشاهدات: «الكورس — الدرس» */
    private static function limitedLessons(): array
    {
        return BootcampSectionItem::query()
            ->with('section.bootcamp:id,name')
            ->whereNotNull('online_lesson_id')
            ->whereNotNull('allowed_views_number')
            ->latest('id')
            ->limit(500)
            ->get()
            ->mapWithKeys(fn (BootcampSectionItem $item): array => [
                (int) $item->getKey() => trim(($item->section?->bootcamp->name ?? '').' — '.$item->name, ' —'),
            ])
            ->all();
    }

    private function student(): Student
    {
        $student = $this->getOwnerRecord();
        throw_unless($student instanceof Student, AuthorizationException::class);

        return $student;
    }

    private function actor(): User
    {
        $user = auth()->user();
        throw_unless($user instanceof User, AuthorizationException::class);

        return $user;
    }
}
