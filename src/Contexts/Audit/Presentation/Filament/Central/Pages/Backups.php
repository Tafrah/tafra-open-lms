<?php

declare(strict_types=1);

namespace Src\Contexts\Audit\Presentation\Filament\Central\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Src\Support\Presentation\Filament\Navigation\NavigationGroup;

/** إدارة النسخ الاحتياطي — عرض وتشغيل يدوي (docs/11 بند ٩). */
final class Backups extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    protected static ?int $navigationSort = 96;

    protected string $view = 'audit::filament.pages.backups';

    public static function getSlug(?Panel $panel = null): string
    {
        return 'backups';
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.items.backups');
    }

    public function getTitle(): string
    {
        return __('navigation.items.backups');
    }

    public static function getNavigationGroup(): string
    {
        return NavigationGroup::System->getLabel();
    }

    public static function canAccess(): bool
    {
        return Gate::allows('access.backups');
    }

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('run')
                ->label(__('audit::audit.backups.run'))
                ->icon('heroicon-o-play')
                ->requiresConfirmation()
                ->modalDescription(__('audit::audit.backups.run_confirm'))
                ->action(function (): void {
                    // النسخة الاحتياطية عملية طويلة — بتروح الطابور
                    dispatch(function (): void {
                        Artisan::call('backup:run');
                    })->onQueue('default');

                    Notification::make()
                        ->success()
                        ->title(__('audit::audit.backups.queued'))
                        ->send();
                }),
        ];
    }

    /** @return Collection<int, array<string, mixed>> */
    public function backups(): Collection
    {
        $disks = (array) config('backup.backup.destination.disks', []);
        $name = (string) config('backup.backup.name');

        $files = collect();

        foreach ($disks as $disk) {
            try {
                $storage = Storage::disk($disk);
            } catch (\Throwable) {
                continue;
            }

            foreach ($storage->files($name) as $path) {
                $files->push([
                    'disk' => $disk,
                    'path' => $path,
                    'size' => $storage->size($path),
                    'modified' => $storage->lastModified($path),
                ]);
            }
        }

        return $files->sortByDesc('modified')->values();
    }
}
