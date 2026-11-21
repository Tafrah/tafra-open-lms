<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Broadcast;
use Src\Contexts\Identity\Domain\Models\User;

/*
|--------------------------------------------------------------------------
| قنوات البث
|--------------------------------------------------------------------------
| **كل قناة خاصة.** مفيش قناة عامة فيها بيانات مستخدمين (docs/20 بند ١١).
*/

// إشعارات المستخدم — هو بس اللي بيسمعها
Broadcast::channel('users.{id}', fn (User $user, int $id): bool => $user->getKey() === $id);

// أحداث المؤسسة — الأعضاء بس
Broadcast::channel('tenants.{tenantId}', fn (User $user, int $tenantId): bool => $user->tenants()
    ->whereKey($tenantId)
    ->exists());
