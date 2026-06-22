<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A key/value system configuration setting.
 */
#[Fillable(['name', 'key', 'value'])]
class SystemSetting extends Model
{
    use HasFactory;
}
