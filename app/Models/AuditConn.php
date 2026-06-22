<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * An audit record of a connection event between peers.
 */
#[Fillable([
    'action', 'conn_id', 'peer_id', 'from_peer', 'from_name', 'ip', 'session_id',
    'type', 'uuid', 'closed_at', 'note',
])]
class AuditConn extends Model
{
    use HasFactory;

    public const ACTION_NEW = 'new';

    public const ACTION_CLOSE = 'close';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'conn_id' => 'integer',
            'type' => 'integer',
            'closed_at' => 'datetime',
        ];
    }
}
