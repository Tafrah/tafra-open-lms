<?php

declare(strict_types=1);

namespace Src\Contexts\Exams\Presentation\Console;

use Illuminate\Console\Command;
use Src\Contexts\Exams\Application\Actions\SubmitAttemptAction;
use Src\Contexts\Exams\Domain\Models\OnlinePassedExam;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;

/**
 * الطالب قفل الصفحة والوقت خلص: المحاولة بتتسلّم وتتصحح لوحدها
 * (وإلا المدرس مايشوفش نتيجة، والدرس اللي بعد الامتحان يفضل مقفول).
 */
final class FinalizeExpiredAttemptsCommand extends Command
{
    protected $signature = 'exams:finalize-expired';

    protected $description = 'تسليم محاولات الامتحانات اللي وقتها خلص';

    public function handle(TenantContext $context, SubmitAttemptAction $submit): int
    {
        $count = 0;

        Tenant::query()->each(function (Tenant $tenant) use ($context, $submit, &$count): void {
            $context->forTenant($tenant->getKey(), function () use ($submit, &$count): void {
                OnlinePassedExam::query()
                    ->where('finished', false)
                    ->whereNotNull('deadline_at')
                    // سماحية دقيقة عشان آخر إجابة توصل
                    ->where('deadline_at', '<', now()->subMinute())
                    ->chunkById(200, function ($attempts) use ($submit, &$count): void {
                        foreach ($attempts as $attempt) {
                            $submit->handle($attempt);
                            $count++;
                        }
                    });
            });
        });

        $this->components->info(__('exams::exams.console.finalized', ['count' => $count]));

        return self::SUCCESS;
    }
}
