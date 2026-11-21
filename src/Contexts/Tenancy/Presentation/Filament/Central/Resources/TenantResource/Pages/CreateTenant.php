<?php

declare(strict_types=1);

namespace Src\Contexts\Tenancy\Presentation\Filament\Central\Resources\TenantResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Tenancy\Application\Actions\ProvisionTenantAction;
use Src\Contexts\Tenancy\Presentation\Filament\Central\Resources\TenantResource;

final class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    /**
     * التجهيز كله في الـ Action — في وضع multi ده بيعمل قاعدة المؤسسة
     * ويهجّرها (docs/24 بند ٣). المُنشئ مابينضمش للمؤسسة: فريق التشغيل
     * بيدخلها بالانتحال بس.
     *
     * @param array<string, mixed> $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $actor = auth()->user();

        return app(ProvisionTenantAction::class)->handle($data, $actor instanceof User ? $actor : null);
    }
}
