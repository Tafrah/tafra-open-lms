<?php

declare(strict_types=1);

namespace Src\Contexts\Transfer;

use Src\Contexts\Transfer\Presentation\Console\ExportAcademyCommand;
use Src\Contexts\Transfer\Presentation\Console\ImportAcademyCommand;
use Src\Support\Infrastructure\Providers\ContextServiceProvider;

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
    }
}
