<?php

declare(strict_types=1);

namespace Src\Contexts\Content\Infrastructure\Policies;

use Illuminate\Auth\Access\Response;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Src\Contexts\Content\Domain\Enums\AnnouncementStatus;
use Src\Contexts\Content\Domain\Models\Announcement;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Settings\Domain\Settings\GeneralSettings;
use Src\Support\Application\Contracts\TenantContext;
use Src\Support\Domain\Authorization\Policy;
use Src\Support\Infrastructure\Authorization\PolicyMemo;

final class AnnouncementPolicy extends Policy
{
    protected function resource(): string
    {
        return 'announcements';
    }

    /** حتى المدير العام لازم يعدّي على قواعد السلامة دي */
    public function invariants(): array
    {
        return ['publish', 'delete'];
    }

    // ══════════════ قدرات بدون سجل ══════════════

    public function viewAny(User $user): Response
    {
        return $this->decide()->permission($user, $this, 'view_any')->response();
    }

    public function create(User $user): Response
    {
        return $this->decide()
            ->permission($user, $this, 'create')
            ->ruleUsing(fn (): bool => ! $this->maintenanceMode(), 'maintenance_mode')
            ->response();
    }

    public function export(User $user): Response
    {
        return $this->decide()->permission($user, $this, 'export')->response();
    }

    // ══════════════ قدرات على سجل ══════════════

    public function view(User $user, Announcement $announcement): Response
    {
        return $this->decide()
            ->permission($user, $this, 'view')
            // السجل من مؤسسة تانية؟ نخفي وجوده أصلاً — مش «ممنوع»
            ->ruleOrNotFound(
                $announcement->tenant_id === app(TenantContext::class)->id(),
                'record_not_found',
            )
            ->response();
    }

    public function update(User $user, Announcement $announcement): Response
    {
        return $this->decide()
            ->permission($user, $this, 'update')
            ->rule(! $announcement->trashed(), 'record_trashed')
            /*
             * إعلان منشور مايتعدّلش إلا بصلاحية النشر.
             *
             * ⚠️ لاحظ إننا بنسأل عن **الصلاحية** مش عن القدرة `publish`
             * على السجل ده: القدرة بتشترط إن الإعلان لسه مسودة، فلو
             * سألنا عنها هنا مكانش حد هيقدر يعدّل إعلان منشور أبداً
             * — ولا حتى المدير. الفرق ده صغير في القراءة وضخم في الأثر.
             */
            ->ruleUsing(
                fn (): bool => $announcement->status !== AnnouncementStatus::Published
                    || $this->canPublishAtAll($user),
                'announcement.published_needs_publish_permission',
            )
            ->response();
    }

    public function delete(User $user, Announcement $announcement): Response
    {
        return $this->decide()
            ->permission($user, $this, 'delete')
            ->rule(! $announcement->trashed(), 'record_trashed')
            // قاعدة سلامة: إعلان منشور وواصل للناس مايتحذفش، يتأرشف
            ->rule(
                $announcement->status !== AnnouncementStatus::Published,
                'announcement.published_must_archive',
            )
            ->response();
    }

    public function restore(User $user, Announcement $announcement): Response
    {
        return $this->decide()
            ->permission($user, $this, 'restore')
            ->rule($announcement->trashed(), 'record_not_trashed')
            ->response();
    }

    public function forceDelete(User $user, Announcement $announcement): Response
    {
        return $this->decide()
            ->permission($user, $this, 'force_delete')
            ->rule($announcement->trashed(), 'record_not_trashed')
            ->response();
    }

    public function publish(User $user, Announcement $announcement): Response
    {
        $fallback = (string) config('app.fallback_locale');

        return $this->decide()
            ->permission($user, $this, 'publish')
            ->rule(! $announcement->trashed(), 'record_trashed')
            ->rule(
                $announcement->status === AnnouncementStatus::Draft,
                'announcement.not_draft',
            )
            ->rule(
                filled($announcement->getTranslation('title', $fallback, false)),
                'announcement.missing_fallback_title',
                ['locale' => __("common.locales.{$fallback}")],
            )
            ->rule(
                $announcement->expires_at === null || $announcement->expires_at->isFuture(),
                'announcement.already_expired',
            )
            ->response();
    }

    public function pin(User $user, Announcement $announcement): Response
    {
        $limit = (int) config('content.max_pinned', 3);

        return $this->decide()
            ->permission($user, $this, 'pin')
            ->rule(
                $announcement->status === AnnouncementStatus::Published,
                'announcement.pin_requires_published',
            )
            // العدّ واحد للصفحة كلها — من غير الذاكرة دي كان هيبقى
            // استعلام لكل صف في الجدول
            ->ruleUsing(
                fn (): bool => $announcement->is_pinned || $this->pinnedCount() < $limit,
                'announcement.pin_limit_reached',
                ['limit' => fc_number($limit)],
            )
            ->response();
    }

    /**
     * فحص صلاحية مجرّد — من غير قواعد أعمال.
     * مسموح هنا لأننا جوه طبقة التفويض نفسها (docs/19 بند ٢).
     */
    private function canPublishAtAll(User $user): bool
    {
        try {
            return $user->hasPermissionTo($this->permissionFor('publish'), config('authorization.guard'));
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    private function pinnedCount(): int
    {
        return app(PolicyMemo::class)->remember(
            'announcements.pinned-count:'.(app(TenantContext::class)->id() ?? 'global'),
            static fn (): int => Announcement::query()->where('is_pinned', true)->count(),
        );
    }

    private function maintenanceMode(): bool
    {
        try {
            return app(GeneralSettings::class)->maintenance_mode;
        } catch (\Throwable) {
            return false;
        }
    }
}
