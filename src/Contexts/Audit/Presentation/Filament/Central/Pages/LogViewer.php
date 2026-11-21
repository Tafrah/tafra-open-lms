<?php

declare(strict_types=1);

namespace Src\Contexts\Audit\Presentation\Filament\Central\Pages;

use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Src\Support\Presentation\Filament\Navigation\NavigationGroup;

/**
 * عارض سجلات بسيط ومقروء فقط.
 *
 * اتكتب بإيدنا بدل `opcodesio/log-viewer` عشان شرط التنقية:
 * الأسرار بتتشال **قبل** العرض، والصفحة مقفولة على صلاحية منفصلة
 * (docs/11 بند ٥). الباكدج الخارجي بيعرض الملف خام.
 */
final class LogViewer extends Page
{
    /** آخر كام سطر نعرضهم */
    private const MAX_LINES = 500;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-magnifying-glass';

    protected static ?int $navigationSort = 95;

    protected string $view = 'audit::filament.pages.log-viewer';

    public ?string $file = null;

    public static function getSlug(?Panel $panel = null): string
    {
        return 'log-viewer';
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.items.log_viewer');
    }

    public function getTitle(): string
    {
        return __('navigation.items.log_viewer');
    }

    public static function getNavigationGroup(): string
    {
        return NavigationGroup::System->getLabel();
    }

    public static function canAccess(): bool
    {
        return Gate::allows('access.log_viewer');
    }

    public function mount(): void
    {
        $this->file = array_key_first($this->files()) ?: null;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('file')
                ->label(__('audit::audit.logs.file'))
                ->options(fn (): array => $this->files())
                ->live()
                ->native(false),
        ]);
    }

    /** @return array<string, string> */
    public function files(): array
    {
        $path = storage_path('logs');

        if (! File::isDirectory($path)) {
            return [];
        }

        return collect(File::files($path))
            ->filter(fn ($file): bool => in_array($file->getExtension(), ['log', 'json'], true))
            ->sortByDesc(fn ($file): int => $file->getMTime())
            ->mapWithKeys(fn ($file): array => [$file->getFilename() => $file->getFilename()])
            ->all();
    }

    /** @return list<string> */
    public function lines(): array
    {
        if ($this->file === null || ! array_key_exists($this->file, $this->files())) {
            return [];
        }

        $path = storage_path('logs/'.$this->file);

        if (! File::exists($path)) {
            return [];
        }

        $lines = array_slice(
            preg_split('/\R/', (string) File::get($path)) ?: [],
            -self::MAX_LINES,
        );

        return array_map($this->redact(...), $lines);
    }

    /** بينقّي أي قيمة لحقل حساس قبل ما تتعرض. */
    private function redact(string $line): string
    {
        foreach ((array) config('logging.redact', []) as $key) {
            $line = (string) preg_replace(
                '/("'.preg_quote($key, '/').'"\s*:\s*)"[^"]*"/i',
                '$1"[محذوف]"',
                $line,
            );

            $line = (string) preg_replace(
                '/\b'.preg_quote($key, '/').'\b\s*[=:]\s*\S+/i',
                $key.'=[محذوف]',
                $line,
            );
        }

        return $line;
    }
}
