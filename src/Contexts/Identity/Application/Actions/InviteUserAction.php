<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Application\Actions;

use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Password;
use LogicException;
use Src\Contexts\Identity\Application\DataObjects\InviteUserData;
use Src\Contexts\Identity\Domain\Events\UserInvited;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Identity\Domain\Repositories\UserRepository;
use Src\Support\Application\Contracts\TenantContext;

final readonly class InviteUserAction
{
    public function __construct(
        private UserRepository $users,
        private Dispatcher $events,
        private TenantContext $tenants,
    ) {}

    public function handle(InviteUserData $data, ?User $actor = null): User
    {
        if ($actor !== null) {
            Gate::forUser($actor)->authorize('create', User::class);
        }

        return DB::transaction(function () use ($data): User {
            $user = $this->users->create($data);

            // الأدوار مربوطة بالمستأجر — لازم السياق يكون مضبوط قبل الإسناد
            $this->tenants->forTenant($data->tenantId, static fn () => $user->assignRole($data->role));

            $this->events->dispatch(new UserInvited(
                userId: (int) $user->getKey(),
                tenantId: $data->tenantId,
                inviteUrl: $this->inviteUrl($user),
            ));

            return $user;
        });
    }

    /**
     * رابط قبول الدعوة = رابط إعادة تعيين كلمة المرور.
     *
     * المدعو مالوش كلمة مرور يعرفها، فأول خطوة عنده هي إنه يحطّ واحدة —
     * ونفس التوكن بينتهي بنفس صلاحية `auth.passwords`.
     */
    private function inviteUrl(User $user): string
    {
        // `createToken()` مش في الـ contract — موجودة في broker Laravel نفسه بس.
        // broker مخصص من غيرها مايقدرش يعمل دعوة، فالأحسن يفشل هنا بوضوح
        $broker = Password::broker();

        if (! $broker instanceof PasswordBroker) {
            throw new LogicException(PasswordBroker::class);
        }

        return route('filament.admin.auth.password-reset.reset', [
            'token' => $broker->createToken($user),
            'email' => $user->email,
        ]);
    }
}
