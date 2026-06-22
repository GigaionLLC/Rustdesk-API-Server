<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A user group. type 1 = default group, type 2 = shared group.
 */
#[Fillable(['name', 'type', 'note'])]
class Group extends Model
{
    use HasFactory;

    public const TYPE_DEFAULT = 1;

    public const TYPE_SHARED = 2;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => 'integer',
        ];
    }
}
