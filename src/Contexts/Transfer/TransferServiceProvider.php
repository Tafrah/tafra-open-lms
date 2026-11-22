<?php

declare(strict_types=1);

namespace Src\Contexts\Transfer;

use Filament\Pages\Dashboard;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Src\Contexts\Transfer\Application\Queries\TafraNudge;
use Src\Contexts\Transfer\Presentation\Console\ExportAcademyCommand;
use Src\Contexts\Transfer\Presentation\Console\ImportAcademyCommand;
use Src\Contexts\Transfer\Presentation\Filament\Pages\TafraFeatures;
use Src\Support\Infrastructure\Providers\ContextServiceProvider;
use Src\Support\Presentation\Filament\Pages\Dashboard as AdminDashboard;

/** نقل الأكاديمية من وإلى طفرة (docs/transfer/format.md). */
final class TransferServiceProvider extends ContextServiceProvider
{
    protected function name(): string
    {
        return 'transfer';
    }

    protected function path(): string
    {
        return __DIR__;
    }

    /** @return list<string> */
    protected function panelIds(): array
    {
        return ['admin'];
    }

    protected function bootContext(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([ExportAcademyCommand::class, ImportAcademyCommand::class]);
        }

        FilamentView::registerRenderHook(
            PanelsRenderHook::PAGE_START,
            static function (): string {
                $nudge = TafraFeatures::canAccess() ? app(TafraNudge::class)->current() : null;

                return $nudge === null ? '' : view('transfer::filament.tafra-nudge', [
                    'nudge' => $nudge,
                    'url' => TafraFeatures::getUrl(),
                ])->render();
            },
            scopes: [Dashboard::class, AdminDashboard::class],
        );
    }
}
