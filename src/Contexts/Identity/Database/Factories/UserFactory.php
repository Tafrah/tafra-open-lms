<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Src\Contexts\Identity\Domain\Enums\UserStatus;
use Src\Contexts\Identity\Domain\Models\User;

/** @extends Factory<User> */
final class UserFactory extends Factory
{
    protected $model = User::class;

    private static ?string $password = null;

    public function definition(): array
    {
        return [
            'code' => 'USR-'.Str::upper(Str::random(8)),
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => null,
            'email_verified_at' => now(),
            'password' => self::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'status' => UserStatus::Active,
            'is_active' => true,
            'locale' => 'ar',
        ];
    }

    public function suspended(): self
    {
        return $this->state(fn (): array => [
            'status' => UserStatus::Suspended,
            'is_active' => false,
        ]);
    }

    public function invited(): self
    {
        return $this->state(fn (): array => ['status' => UserStatus::Invited]);
    }

    public function unverified(): self
    {
        return $this->state(fn (): array => ['email_verified_at' => null]);
    }
}
