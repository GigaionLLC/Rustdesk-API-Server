<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Assigns a strategy to a target (device, user, or device group).
 */
#[Fillable(['strategy_id', 'target_type', 'target_id'])]
class StrategyAssignment extends Model
{
    use HasFactory;

    public const TARGET_DEVICE = 'device';

    public const TARGET_USER = 'user';

    public const TARGET_DEVICE_GROUP = 'device_group';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'target_id' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Strategy, $this>
     */
    public function strategy(): BelongsTo
    {
        return $this->belongsTo(Strategy::class);
    }
}
