<?php

declare(strict_types=1);

namespace Src\Contexts\Ai\Application\Actions;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Src\Contexts\Ai\Application\Contracts\OAuthTokens;
use Src\Contexts\Ai\Application\DataObjects\AuthorizeAiConnectionData;
use Src\Contexts\Ai\Application\Services\AiAvailability;
use Src\Contexts\Ai\Domain\Enums\AiAccessLevel;
use Src\Contexts\Ai\Domain\Events\AiConnectionAuthorized;
use Src\Contexts\Ai\Domain\Models\AiConnection;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Tenancy\Domain\Models\Tenant;

/**
 * الموافقة على ربط تطبيق ذكاء اصطناعي — بتتنادى من شاشة الموافقة قبل ما
 * Passport يصدر كود التفويض.
 *
 * العميل الواحد مربوط بمؤسسة واحدة للمستخدم. موافقة على مؤسسة **تانية**
 * لنفس العميل بتلغي كل توكنزه القديمة أول — غير كده توكن صادر لمؤسسة
 * كان هيشتغل فجأة على مؤسسة غيرها (docs/20 بند ١١: «مربوط بمؤسسة واحدة»).
 */
final readonly class AuthorizeAiConnectionAction
{
    public function __construct(
        private OAuthTokens $tokens,
        private AiAvailability $availability,
        private Dispatcher $events,
    ) {}

    public function handle(AuthorizeAiConnectionData $data, User $actor): AiConnection
    {
        $tenant = $data->tenantId === null ? null : Tenant::query()->findOrFail($data->tenantId);

        Gate::forUser($actor)->authorize('connect', [AiConnection::class, $tenant]);

        // المؤسسة قافلة الكتابة؟ الربط بيتسجّل قراءة — مش رفض، عشان العضو
        // يفضل يقدر يستخدم القراءة
        $access = $data->access->allowsWrites() && $this->availability->writesAllowedFor($data->tenantId)
            ? AiAccessLevel::Write
            : AiAccessLevel::Read;

        $connection = DB::connection((new AiConnection)->getConnectionName())->transaction(function () use ($data, $actor, $access): AiConnection {
            $connection = AiConnection::query()
                ->where('user_id', $actor->getKey())
                ->where('oauth_client_id', $data->clientId)
                ->lockForUpdate()
                ->first();

            if ($connection !== null && ($connection->tenant_id !== $data->tenantId || ! $connection->isActive())) {
                $this->tokens->revokeFor((int) $actor->getKey(), $data->clientId);
            }

            $connection ??= new AiConnection;
            $connection->forceFill([
                'user_id' => $actor->getKey(),
                'oauth_client_id' => $data->clientId,
            ]);
            $connection->fill([
                'tenant_id' => $data->tenantId,
                'access' => $access,
                'revoked_at' => null,
            ])->save();

            return $connection;
        });

        $this->events->dispatch(new AiConnectionAuthorized(
            connectionId: (int) $connection->getKey(),
            userId: (int) $actor->getKey(),
            tenantId: $data->tenantId,
        ));

        return $connection;
    }
}
