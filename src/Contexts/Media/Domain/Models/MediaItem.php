<?php

declare(strict_types=1);

namespace Src\Contexts\Media\Domain\Models;

use Spatie\MediaLibrary\MediaCollections\Models\Media as SpatieMedia;
use Src\Support\Application\Contracts\TenantContext;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * موديل الوسائط بتاعنا.
 *
 * موجود لسببين:
 * ١. اكتشاف الـ Policy التلقائي (`\Domain\Models\` → `\Infrastructure\Policies\`).
 * ٢. **حقن `tenant_id` عند الإنشاء** — وده اللي بيخلّي العزل شغّال فعلاً.
 *
 * ليه هنا مش في الـ trait بس؟ `InteractsWithResolvedMedia::attachMedia()`
 * بتحقن `tenant_id`، بس `SpatieMediaLibraryFileUpload` بتاع Filament
 * **مابيعديش عليها** — بيستخدم FileAdder بتاع الباكدج مباشرة. يعني كل
 * رفع من الواجهة (صور المستخدمين، مرفقات الإعلانات) كان بيتخزّن تحت
 * `tenants/global/` وبيختفي من شاشة الوسائط.
 *
 * الحقن هنا بيغطّي **كل** مسارات الرفع، وبيحصل قبل مولّد المسارات
 * لأن الـ FileAdder بيحفظ الصف الأول.
 *
 * مركزي في الوضعين: صور المستخدمين وملفات الهوية ملك موديلات مركزية،
 * وعزل ملفات المؤسسات بـ `custom_properties.tenant_id` زي ما هو (docs/03 بند ٣).
 */
final class MediaItem extends SpatieMedia
{
    use CentralConnection;

    protected static function booted(): void
    {
        self::creating(static function (self $media): void {
            if ($media->getCustomProperty('tenant_id') !== null) {
                return;
            }

            $tenantId = self::currentTenantId();

            if ($tenantId !== null) {
                $media->setCustomProperty('tenant_id', $tenantId);
            }
        });
    }

    public function tenantId(): ?int
    {
        $value = $this->getCustomProperty('tenant_id');

        return is_numeric($value) ? (int) $value : null;
    }

    private static function currentTenantId(): ?int
    {
        try {
            return app(TenantContext::class)->id();
        } catch (\Throwable) {
            // رفع من سياق إداري بدون مؤسسة (أصول العلامة مثلاً) — مقصود
            return null;
        }
    }
}
