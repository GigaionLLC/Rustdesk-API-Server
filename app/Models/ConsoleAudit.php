<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A record of an admin-console mutation (POST/PUT/PATCH/DELETE): who changed what.
 * Written by the LogConsoleOperation middleware; read-only in the panel.
 */
#[Fillable(['user_id', 'method', 'route_name', 'path', 'ip'])]
class ConsoleAudit extends Model
{
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
