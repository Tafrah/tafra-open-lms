<?php

declare(strict_types=1);

namespace Src\Contexts\Ai\Application\Actions;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Src\Contexts\Ai\Application\Contracts\OAuthTokens;
use Src\Contexts\Ai\Domain\Events\AiConnectionRevoked;
use Src\Contexts\Ai\Domain\Models\AiConnection;
use Src\Contexts\Identity\Domain\Models\User;

/**
 * فصل تطبيق ذكاء اصطناعي — التوكن وتوكن التجديد بيتقفلوا في نفس اللحظة،
 * فالنداء الجاي من التطبيق بيرجع 401 والتجديد بيرجع `invalid_grant`.
 */
final readonly class RevokeAiConnectionAction
{
    public function __construct(
        private OAuthTokens $tokens,
        private Dispatcher $events,
    ) {}

    public function handle(AiConnection $connection, ?User $actor = null): AiConnection
    {
        // $actor = null: استدعاء نظامي صريح (حذف مستخدم، إيقاف مؤسسة)
        if ($actor !== null) {
            Gate::forUser($actor)->authorize('delete', $connection);
        }

        DB::connection($connection->getConnectionName())->transaction(function () use ($connection): void {
            $this->tokens->revokeFor((int) $connection->user_id, $connection->oauth_client_id);

            $connection->fill(['revoked_at' => now()])->save();
        });

        $this->events->dispatch(new AiConnectionRevoked(
            connectionId: (int) $connection->getKey(),
            userId: (int) $connection->user_id,
            tenantId: $connection->tenant_id,
        ));

        return $connection;
    }
}
