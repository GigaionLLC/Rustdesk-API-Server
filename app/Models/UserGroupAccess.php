<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Access Control Layer 1 (docs/modernization/12-access-control-design.md): a directional
 * grant. Members of `group_id` may access devices owned by users in `can_access_group_id`.
 */
#[Fillable(['group_id', 'can_access_group_id'])]
class UserGroupAccess extends Model
{
    use HasFactory;

    protected $table = 'user_group_access';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'group_id' => 'integer',
            'can_access_group_id' => 'integer',
        ];
    }
}
