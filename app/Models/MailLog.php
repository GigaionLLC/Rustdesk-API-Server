<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A record of an email send attempt.
 */
#[Fillable([
    'user_id', 'template_id', 'from_address', 'to_address', 'uuid', 'subject',
    'contents', 'status', 'logs',
])]
class MailLog extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => 'integer',
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
     * @return BelongsTo<MailTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(MailTemplate::class);
    }
}
