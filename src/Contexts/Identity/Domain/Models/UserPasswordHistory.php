<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

final class UserPasswordHistory extends Model
{
    use CentralConnection;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $fillable = ['user_id', 'password'];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
