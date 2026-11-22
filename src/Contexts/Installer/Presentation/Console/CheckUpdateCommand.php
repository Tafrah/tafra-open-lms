<?php

declare(strict_types=1);

namespace Src\Contexts\Installer\Presentation\Console;

use Illuminate\Console\Command;
use Src\Contexts\Installer\Infrastructure\InstallationState;
use Src\Contexts\Installer\Infrastructure\Updates\ReleaseChecker;

final class CheckUpdateCommand extends Command
{
    protected $signature = 'lms:check-update';

    protected $description = 'Check GitHub for a newer Tafra Open LMS release (no data is sent)';

    public function handle(ReleaseChecker $checker, InstallationState $state): int
    {
        $release = $checker->check();

        if ($release === null) {
            $this->components->warn('Update check is disabled or GitHub did not answer.');

            return self::SUCCESS;
        }

        $this->components->info("Installed {$state->version()} — latest {$release['version']}");

        return self::SUCCESS;
    }
}
