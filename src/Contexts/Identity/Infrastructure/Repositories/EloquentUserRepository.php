<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Infrastructure\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Src\Contexts\Identity\Application\DataObjects\InviteUserData;
use Src\Contexts\Identity\Domain\Enums\UserStatus;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Identity\Domain\Repositories\UserRepository;
use Src\Support\Infrastructure\Tenancy\TenancyMode;

final class EloquentUserRepository implements UserRepository
{
    public function find(int $id): ?User
    {
        return User::query()->find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return User::query()->where('email', $email)->first();
    }

    public function create(InviteUserData $data): User
    {
        $user = new User;

        $user->fill([
            'name' => $data->name,
            'email' => $data->email,
            'phone' => $data->phone,
            'locale' => $data->locale,
            'password' => $data->password,
        ]);

        // حقول الحالة مش في $fillable عن قصد — بتتحط برمجياً بس
        $user->code = $this->nextCode();
        $user->status = UserStatus::Invited;
        $user->is_active = true;
        $user->save();

        $user->tenants()->syncWithoutDetaching([$data->tenantId => ['joined_at' => now()]]);

        return $user;
    }

    /** @return Collection<int, User> */
    public function activeInTenant(int $tenantId): Collection
    {
        return User::query()
            ->ofTenant($tenantId)
            ->active()
            ->get();
    }

    /** كود متسلسل مقروء للمستخدم: USR-000123 */
    public function nextCode(): string
    {
        $next = (int) DB::connection(TenancyMode::centralConnection())->table('users')->max('id') + 1;

        return sprintf('USR-%06d', $next);
    }
}
