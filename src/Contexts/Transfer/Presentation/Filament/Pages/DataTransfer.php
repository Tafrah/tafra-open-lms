<?php

declare(strict_types=1);

namespace Src\Contexts\Transfer\Presentation\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Src\Contexts\Transfer\Application\Actions\ExportAcademyAction;
use Src\Contexts\Transfer\Application\Actions\ImportAcademyAction;
use Src\Support\Domain\Exceptions\DomainException;
use Src\Support\Presentation\Filament\Navigation\NavigationGroup;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * «نقل البيانات» — تصدير الأكاديمية لملف (للنسخ الاحتياطي أو للانتقال لطفرة)،
 * واستيراد ملف بتجربة الأول (docs/transfer/format.md). على الاستضافة المشتركة
 * مفيش SSH، فالشاشة دي هي البديل لـ `tafra:export` / `tafra:import`.
 */
final class DataTransfer extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrows-up-down';

    protected static ?int $navigationSort = 90;

    protected string $view = 'transfer::filament.data-transfer';

    /** @var array<string, mixed>|null آخر تقرير (تجربة أو استيراد) */
    #[Locked]
    public ?array $report = null;

    /**
     * الملف المرفوع اللي اتعمل عليه تجربة — بيستنى التأكيد.
     * Locked: المتصفح مايقدرش يغيّره (وإلا يشاور على أي ملف على السيرفر).
     */
    #[Locked]
    public ?string $pendingPath = null;

    public static function getSlug(?Panel $panel = null): string
    {
        return 'data-transfer';
    }

    public static function canAccess(): bool
    {
        return Gate::allows('access.data_transfer');
    }

    public static function getNavigationGroup(): string
    {
        return NavigationGroup::System->getLabel();
    }

    public static function getNavigationLabel(): string
    {
        return __('transfer::transfer.page.title');
    }

    public function getTitle(): string
    {
        return __('transfer::transfer.page.title');
    }

    public function getSubheading(): string
    {
        return __('transfer::transfer.page.description');
    }

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label(__('transfer::transfer.page.export'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->authorize('access.data_transfer')
                ->action(function (): ?BinaryFileResponse {
                    @set_time_limit(600);
                    $path = Storage::disk((string) config('transfer.disk'))->path('exports/tafra-export-'.now()->format('Y-m-d-His').'.zip');

                    try {
                        app(ExportAcademyAction::class)->handle($path);
                    } catch (DomainException $e) {
                        Notification::make()->danger()->title($e->getMessage())->send();

                        return null;
                    }

                    return response()->download($path)->deleteFileAfterSend();
                }),

            Action::make('import')
                ->label(__('transfer::transfer.page.import'))
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->authorize('access.data_transfer')
                ->modalDescription(__('transfer::transfer.page.import_help'))
                ->schema([
                    FileUpload::make('file')
                        ->label(__('transfer::transfer.page.file'))
                        ->disk((string) config('transfer.disk'))
                        ->directory('transfer-uploads')
                        ->visibility('private')
                        ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed', 'application/octet-stream'])
                        ->maxSize((int) config('transfer.max_upload_kb'))
                        ->required(),
                ])
                ->modalSubmitActionLabel(__('transfer::transfer.page.try'))
                ->action(function (array $data): void {
                    $this->pendingPath = (string) $data['file'];
                    $this->run(dryRun: true);
                }),

            Action::make('confirm')
                ->label(__('transfer::transfer.page.confirm'))
                ->icon('heroicon-o-check')
                ->color('success')
                ->authorize('access.data_transfer')
                ->visible(fn (): bool => $this->pendingPath !== null && ($this->report['dry_run'] ?? false) === true)
                ->requiresConfirmation()
                ->modalDescription(__('transfer::transfer.page.confirm_help'))
                ->action(fn () => $this->run(dryRun: false)),
        ];
    }

    private function run(bool $dryRun): void
    {
        $disk = Storage::disk((string) config('transfer.disk'));

        if ($this->pendingPath === null
            || ! Str::startsWith($this->pendingPath, 'transfer-uploads/')
            || str_contains($this->pendingPath, '..')
            || ! $disk->exists($this->pendingPath)) {
            $this->pendingPath = null;
            Notification::make()->danger()->title(__('transfer::transfer.errors.file_missing'))->send();

            return;
        }

        @set_time_limit(900);

        try {
            $report = app(ImportAcademyAction::class)->handle($disk->path($this->pendingPath), $dryRun, (int) auth()->id());
        } catch (DomainException $e) {
            $disk->delete($this->pendingPath);
            $this->pendingPath = null;
            Notification::make()->danger()->title($e->getMessage())->send();

            return;
        }

        $this->report = $report->toArray();

        if (! $dryRun) {
            $disk->delete($this->pendingPath);
            $this->pendingPath = null;
            Notification::make()->success()->title(__('transfer::transfer.page.done'))->send();
        }
    }
}
