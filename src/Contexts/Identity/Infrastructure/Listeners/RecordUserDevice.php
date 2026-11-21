<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Infrastructure\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Src\Contexts\Identity\Domain\Events\DeviceRegistered;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Identity\Domain\Models\UserDevice;
use Src\Support\Domain\ValueObjects\DeviceSignature;

/**
 * بيسجّل أو بيحدّث الجهاز عند كل دخول.
 * أول ظهور لجهاز = حدث بيولّد إشعار أمني (docs/12 بند ٢).
 */
final class RecordUserDevice
{
    public function handle(Login $event): void
    {
        $user = $event->user;

        if (! $user instanceof User) {
            return;
        }

        $request = request();
        $signature = DeviceSignature::fromUserAgent($request->userAgent());

        $device = UserDevice::query()
            ->where('user_id', $user->getKey())
            ->where('fingerprint', $signature->fingerprint)
            ->first();

        $isNew = $device === null;

        $device = UserDevice::query()->updateOrCreate(
            ['user_id' => $user->getKey(), 'fingerprint' => $signature->fingerprint],
            [
                'session_id' => $request->hasSession() ? $request->session()->getId() : null,
                'device_name' => $signature->name,
                'platform' => $signature->platform,
                'browser' => $signature->browser,
                'ip_address' => (string) $request->ip(),
                'last_active_at' => now(),
            ],
        );

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->saveQuietly();

        if ($isNew) {
            Event::dispatch(new DeviceRegistered(
                userId: (int) $user->getKey(),
                deviceId: (int) $device->getKey(),
                deviceName: $signature->name,
                ipAddress: (string) $request->ip(),
            ));
        }
    }
}
