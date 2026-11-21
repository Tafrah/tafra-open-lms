<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

final class UserDevice extends Model
{
    use CentralConnection;

    /** @var list<string> */
    protected $fillable = [
        'user_id', 'session_id', 'fingerprint', 'device_name',
        'platform', 'browser', 'ip_address', 'country', 'is_trusted', 'last_active_at',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isCurrent(): bool
    {
        return $this->session_id !== null && $this->session_id === session()->getId();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_trusted' => 'boolean',
            'last_active_at' => 'datetime',
        ];
    }
}
