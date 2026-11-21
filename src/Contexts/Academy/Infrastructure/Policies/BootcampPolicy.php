<?php

declare(strict_types=1);

namespace Src\Contexts\Academy\Infrastructure\Policies;

use Illuminate\Auth\Access\Response;
use Src\Contexts\Academy\Domain\Models\Bootcamp;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Support\Domain\Authorization\StandardPolicy;

/**
 * الكورسات. النشر صلاحية منفصلة عن التعديل.
 */
final class BootcampPolicy extends StandardPolicy
{
    protected function resource(): string
    {
        return 'bootcamps';
    }

    public function publish(User $user, Bootcamp $record): Response
    {
        return $this->owned($user, $record, 'publish')
            ->rule(! $record->trashed(), 'record_trashed')
            ->rule(! $record->isPublished(), 'bootcamp.already_published')
            ->response();
    }
}
