<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A grouping of devices used for strategy assignment and organisation.
 */
#[Fillable(['name', 'note', 'is_default'])]
class DeviceGroup extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    /**
     * The id of the default device group (the one new/ungrouped devices fall into), or null
     * when none is designated.
     */
    public static function defaultId(): ?int
    {
        $id = static::query()->where('is_default', true)->value('id');

        return $id === null ? null : (int) $id;
    }

    /**
     * The default device group id, auto-provisioning one when none is designated so new devices
     * never land in "None": promotes the oldest existing group to default, or creates a
     * "Default" group. Returns null only when auto-defaulting is disabled
     * (rustdesk.devices.auto_default_group = false).
     */
    public static function ensureDefaultId(): ?int
    {
        $id = static::defaultId();
        if ($id !== null) {
            return $id;
        }

        if (! config('rustdesk.devices.auto_default_group', true)) {
            return null;
        }

        // Prefer promoting the oldest existing group; otherwise create a "Default" group.
        $group = static::query()->orderBy('id')->first()
            ?? static::create(['name' => 'Default']);

        $group->forceFill(['is_default' => true])->save();

        return (int) $group->id;
    }

    /**
     * @return HasMany<Device, $this>
     */
    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }
}
