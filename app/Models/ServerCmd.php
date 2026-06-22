<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A server command that can be pushed to a client target.
 */
#[Fillable(['cmd', 'alias', 'option', 'explain', 'target'])]
class ServerCmd extends Model
{
    use HasFactory;
}
