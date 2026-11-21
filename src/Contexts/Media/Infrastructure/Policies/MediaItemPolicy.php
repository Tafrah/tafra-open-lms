<?php

declare(strict_types=1);

namespace Src\Contexts\Media\Infrastructure\Policies;

use Illuminate\Auth\Access\Response;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Media\Domain\Models\MediaItem;
use Src\Support\Application\Contracts\TenantContext;
use Src\Support\Domain\Authorization\Policy;

final class MediaItemPolicy extends Policy
{
    protected function resource(): string
    {
        return 'media';
    }

    public function invariants(): array
    {
        return ['delete'];
    }

    public function viewAny(User $user): Response
    {
        return $this->decide()->permission($user, $this, 'view_any')->response();
    }

    public function view(User $user, MediaItem $media): Response
    {
        return $this->decide()
            ->permission($user, $this, 'view')
            ->ruleOrNotFound($this->belongsToCurrentTenant($media), 'record_not_found')
            ->response();
    }

    public function create(User $user): Response
    {
        return $this->decide()->permission($user, $this, 'create')->response();
    }

    public function delete(User $user, MediaItem $media): Response
    {
        return $this->decide()
            ->permission($user, $this, 'delete')
            ->ruleOrNotFound($this->belongsToCurrentTenant($media), 'record_not_found')
            ->response();
    }

    public function download(User $user, MediaItem $media): Response
    {
        return $this->decide()
            ->permission($user, $this, 'download')
            ->ruleOrNotFound($this->belongsToCurrentTenant($media), 'record_not_found')
            ->response();
    }

    public function changeDisk(User $user, MediaItem $media): Response
    {
        return $this->decide()->permission($user, $this, 'change_disk')->response();
    }

    /**
     * الملف بدون `tenant_id` = أصل عام (لوجو، أيقونة الموقع) اترفع من
     * إعدادات النظام قبل أي سياق مؤسسة. دي الحالة الوحيدة المسموح فيها
     * بالوصول العابر، وهي مقصودة — الأصول دي بتظهر لكل المؤسسات أصلاً.
     *
     * أي ملف بيترفع جوه مؤسسة بياخد `tenant_id` تلقائياً من
     * `MediaItem::booted()`، فمفيش ملف مؤسسة بيقع في الفرع ده.
     */
    private function belongsToCurrentTenant(MediaItem $media): bool
    {
        $current = app(TenantContext::class)->id();

        return $current === null || $media->tenantId() === null || $media->tenantId() === $current;
    }
}
