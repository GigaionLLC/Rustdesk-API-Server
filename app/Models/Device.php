<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A RustDesk client device (peer) known to this server.
 */
#[Fillable([
    'rustdesk_id', 'uuid', 'cpu', 'hostname', 'memory', 'os', 'username', 'version',
    'alias', 'user_id', 'group_id', 'device_group_id', 'strategy_id', 'is_online',
    'conns', 'last_online_at', 'last_online_ip', 'device_username', 'device_name',
    'note', 'approved',
])]
class Device extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_online' => 'boolean',
            'conns' => 'integer',
            'last_online_at' => 'datetime',
            'approved' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<DeviceGroup, $this>
     */
    public function deviceGroup(): BelongsTo
    {
        return $this->belongsTo(DeviceGroup::class);
    }

    /**
     * @return BelongsTo<Strategy, $this>
     */
    public function strategy(): BelongsTo
    {
        return $this->belongsTo(Strategy::class);
    }
}
