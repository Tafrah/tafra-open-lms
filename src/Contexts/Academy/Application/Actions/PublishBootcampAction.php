<?php

declare(strict_types=1);

namespace Src\Contexts\Academy\Application\Actions;

use Illuminate\Support\Facades\Gate;
use Src\Contexts\Academy\Domain\Enums\PublishStatus;
use Src\Contexts\Academy\Domain\Models\Bootcamp;
use Src\Contexts\Identity\Domain\Models\User;

final readonly class PublishBootcampAction
{
    public function handle(Bootcamp $bootcamp, User $actor): Bootcamp
    {
        Gate::forUser($actor)->authorize('publish', $bootcamp);

        $bootcamp->update([
            'publish_status' => PublishStatus::Published,
            'publish_date' => $bootcamp->publish_date ?? now(),
        ]);

        return $bootcamp;
    }

    public function unpublish(Bootcamp $bootcamp, User $actor): Bootcamp
    {
        Gate::forUser($actor)->authorize('update', $bootcamp);

        $bootcamp->update(['publish_status' => PublishStatus::Draft]);

        return $bootcamp;
    }
}
