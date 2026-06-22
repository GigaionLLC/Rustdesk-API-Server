<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A tag used to organise peers within an address book.
 */
#[Fillable(['address_book_id', 'user_id', 'name', 'color'])]
class Tag extends Model
{
    use HasFactory;

    /**
     * @return BelongsTo<AddressBook, $this>
     */
    public function addressBook(): BelongsTo
    {
        return $this->belongsTo(AddressBook::class);
    }
}
