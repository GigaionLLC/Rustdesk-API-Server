<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A configuration strategy whose options are pushed to clients.
 */
#[Fillable(['name', 'enabled', 'options', 'extra', 'modified_at', 'note'])]
class Strategy extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'options' => 'array',
            'extra' => 'array',
            'modified_at' => 'integer',
        ];
    }

    /**
     * @return HasMany<StrategyAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(StrategyAssignment::class);
    }
}
