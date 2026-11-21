<?php

declare(strict_types=1);

namespace Src\Contexts\Tenancy\Infrastructure\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Src\Contexts\Tenancy\Domain\Models\Tenant;

/**
 * آخر خطوة في تجهيز مؤسسة (docs/24 بند ٣): القاعدة اتعملت واتهجّرت
 * واتزرعت — المؤسسة جاهزة تتفتح.
 */
final class MarkTenantProvisioned implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly Tenant $tenant) {}

    public function handle(): void
    {
        $this->tenant->forceFill(['provisioning_status' => Tenant::PROVISIONING_READY])->save();
    }
}
