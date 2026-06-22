<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Access Control Layer 1 (docs/modernization/12-access-control-design.md): user group
 * `group_id` may access devices in device group `device_group_id`.
 */
#[Fillable(['group_id', 'device_group_id'])]
class DeviceGroupAccess extends Model
{
    use HasFactory;

    protected $table = 'device_group_access';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'group_id' => 'integer',
            'device_group_id' => 'integer',
        ];
    }
}
