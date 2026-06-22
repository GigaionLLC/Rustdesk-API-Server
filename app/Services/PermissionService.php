<?php

namespace App\Services;

use App\Models\AdminRole;
use App\Models\User;

/**
 * Resolves console permissions for the Admin Role layer
 * (docs/modernization/12-access-control-design.md §Layer 3).
 *
 * Backward compatibility is paramount: an `is_admin` user always has every permission, so
 * installs that only use the single-admin model keep full access exactly as before.
 */
class PermissionService
{
    /**
     * Whether the user holds the given permission.
     *
     * Rules:
     *   - `is_admin` users always pass (full access — preserves legacy behaviour).
     *   - Otherwise true if any of the user's admin roles grants the permission.
     *   - A `global`-type role implies every permission in the catalogue.
     */
    public function can(User $user, string $permission): bool
    {
        if ($user->is_admin) {
            return true;
        }

        foreach ($user->adminRoles as $role) {
            if ($role->type === AdminRole::TYPE_GLOBAL) {
                return true;
            }

            if (in_array($permission, (array) $role->perms, true)) {
                return true;
            }
        }

        return false;
    }
}
