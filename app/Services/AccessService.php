<?php

namespace App\Services;

use App\Models\Device;
use App\Models\DeviceGroup;
use App\Models\DeviceGroupAccess;
use App\Models\User;
use App\Models\UserGroupAccess;

/**
 * Access Control Layer 1 — resolves which devices / users / device groups a given account may
 * see (docs/modernization/12-access-control-design.md §"Layer 1").
 *
 * Default-OPEN: with no access rows configured a regular user still sees their own devices and
 * an admin sees everything, so existing flows are never regressed. Access is cumulative —
 * granted if the user-group rules OR the device-group rules allow it. A disabled (non-active)
 * user resolves to an empty set.
 */
class AccessService
{
    /**
     * Device ids the user may access.
     *
     * Admins get every device. Regular users get the union of:
     *   1. devices they own (`devices.user_id`),
     *   2. devices owned by users whose `group_id` their group is granted via
     *      `user_group_access`,
     *   3. devices in any device group their group is granted via `device_group_access`.
     *
     * @return list<int>
     */
    public function accessibleDeviceIds(User $user): array
    {
        if ($user->is_admin) {
            return Device::query()->pluck('id')->map(static fn ($id): int => (int) $id)->all();
        }

        if (! $user->isActive()) {
            return [];
        }

        $query = Device::query()->where(function ($q) use ($user): void {
            // 1. Devices the user owns.
            $q->where('user_id', $user->id);

            // 2. Devices owned by users in groups this user's group can access.
            $canAccessGroupIds = $this->canAccessGroupIds($user);
            if ($canAccessGroupIds !== []) {
                $q->orWhereIn('user_id', User::query()
                    ->whereIn('group_id', $canAccessGroupIds)
                    ->select('id'));
            }

            // 3. Devices in device groups this user's group is granted.
            $deviceGroupIds = $this->accessibleDeviceGroupIds($user);
            if ($deviceGroupIds !== []) {
                $q->orWhereIn('device_group_id', $deviceGroupIds);
            }
        });

        return $query->pluck('id')->map(static fn ($id): int => (int) $id)->all();
    }

    /**
     * User ids whose devices the given user may see (the owners behind accessibleDeviceIds).
     * Admins get every user. Always includes the user themselves.
     *
     * @return list<int>
     */
    public function accessibleUserIds(User $user): array
    {
        if ($user->is_admin) {
            return User::query()->pluck('id')->map(static fn ($id): int => (int) $id)->all();
        }

        if (! $user->isActive()) {
            return [];
        }

        $ids = [(int) $user->id];

        $canAccessGroupIds = $this->canAccessGroupIds($user);
        if ($canAccessGroupIds !== []) {
            $ids = array_merge($ids, User::query()
                ->whereIn('group_id', $canAccessGroupIds)
                ->pluck('id')
                ->map(static fn ($id): int => (int) $id)
                ->all());
        }

        return array_values(array_unique($ids));
    }

    /**
     * Device group ids the user may access. Admins get every device group.
     *
     * @return list<int>
     */
    public function accessibleDeviceGroupIds(User $user): array
    {
        if ($user->is_admin) {
            return DeviceGroup::query()
                ->pluck('id')
                ->map(static fn ($id): int => (int) $id)
                ->all();
        }

        if (! $user->isActive() || ! $user->group_id) {
            return [];
        }

        return DeviceGroupAccess::query()
            ->where('group_id', $user->group_id)
            ->pluck('device_group_id')
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * The user-group ids that the given user's group is granted access to (via
     * `user_group_access`). Empty when the user has no group.
     *
     * @return list<int>
     */
    private function canAccessGroupIds(User $user): array
    {
        if (! $user->group_id) {
            return [];
        }

        return UserGroupAccess::query()
            ->where('group_id', $user->group_id)
            ->pluck('can_access_group_id')
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
