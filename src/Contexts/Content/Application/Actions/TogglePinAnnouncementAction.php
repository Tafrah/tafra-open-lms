<?php

declare(strict_types=1);

namespace Src\Contexts\Content\Application\Actions;

use Illuminate\Support\Facades\Gate;
use Src\Contexts\Content\Domain\Models\Announcement;
use Src\Contexts\Identity\Domain\Models\User;

/**
 * تثبيت إعلان أو إلغاء تثبيته — زرار الجدول وأداة MCP بينادوا ده بالظبط.
 *
 * كان `$record->update()` جوه الجدول نفسه: منطق في Presentation، ونسخة
 * تانية كانت هتتكتب للذكاء الاصطناعي (docs/21 بند ٢٥).
 */
final readonly class TogglePinAnnouncementAction
{
    public function handle(Announcement $announcement, ?User $actor = null): Announcement
    {
        if ($actor !== null) {
            Gate::forUser($actor)->authorize('pin', $announcement);
        }

        $announcement->update(['is_pinned' => ! $announcement->is_pinned]);

        return $announcement;
    }
}
