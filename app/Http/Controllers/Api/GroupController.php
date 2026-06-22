<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\DeviceGroup;
use App\Models\User;
use App\Services\AccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Client "group" endpoints — the accessible users / peers / device groups the signed-in
 * account may see (docs/modernization/02-client-api-contract.md §10). Go reference:
 * http/controller/api/group.go (Users / Peers / Device).
 *
 * All three are rustauth-protected. The visible set is resolved by AccessService (Access
 * Control Layer 1, docs/modernization/12-access-control-design.md). Default-OPEN: with no
 * access rows configured a regular user sees only their own, admins see everything.
 *
 * The JSON keys here are the wire protocol the RustDesk client speaks; do not rename them.
 */
class GroupController extends Controller
{
    public function __construct(private readonly AccessService $access) {}

    /**
     * GET /api/users
     * UserPayload list (Go Group::Users). Shape: {name, email, note, is_admin, status, info}.
     */
    public function users(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $userIds = $this->access->accessibleUserIds($user);

        $users = User::query()
            ->whereIn('id', $userIds)
            ->orderBy('id')
            ->get();

        $data = $users->map(static fn (User $u): array => [
            'name' => (string) $u->username,
            'email' => (string) ($u->email ?? ''),
            'note' => (string) ($u->note ?? ''),
            'is_admin' => (bool) $u->is_admin,
            'status' => (int) $u->status,
            'info' => (object) [],
        ])->all();

        return response()->json([
            'total' => count($data),
            'data' => $data,
        ]);
    }

    /**
     * GET /api/peers
     * GroupPeerPayload list (Go Group::Peers), filtered by accessible device ids. Shape:
     * {id, info:{device_name, os, username}, status, user, user_name, note, device_group_name}.
     */
    public function peers(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $deviceIds = $this->access->accessibleDeviceIds($user);

        $devices = Device::query()
            ->whereIn('id', $deviceIds)
            ->orderBy('id')
            ->get();

        // Owner usernames and device-group names for the payload, resolved in bulk.
        $userNames = User::query()
            ->whereIn('id', $devices->pluck('user_id')->filter()->unique()->all())
            ->pluck('username', 'id');

        $deviceGroupNames = DeviceGroup::query()
            ->whereIn('id', $devices->pluck('device_group_id')->filter()->unique()->all())
            ->pluck('name', 'id');

        $data = $devices->map(static function (Device $d) use ($userNames, $deviceGroupNames): array {
            $userName = $d->user_id ? (string) ($userNames[$d->user_id] ?? '') : '';
            $deviceGroupName = $d->device_group_id ? (string) ($deviceGroupNames[$d->device_group_id] ?? '') : '';

            return [
                'id' => (string) $d->rustdesk_id,
                'info' => [
                    'device_name' => (string) ($d->device_name ?: $d->hostname ?? ''),
                    'os' => (string) ($d->os ?? ''),
                    'username' => (string) ($d->device_username ?: $d->username ?? ''),
                ],
                'status' => $d->is_online ? 1 : 0,
                'user' => $userName,
                'user_name' => $userName,
                'note' => (string) ($d->note ?? ''),
                'device_group_name' => $deviceGroupName,
            ];
        })->all();

        return response()->json([
            'total' => count($data),
            'data' => $data,
        ]);
    }

    /**
     * GET /api/device-group/accessible?current=1&pageSize=100
     * Paginated accessible device groups (Go Group::Device), filtered by access grants.
     */
    public function deviceGroupAccessible(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $groupIds = $this->access->accessibleDeviceGroupIds($user);

        $current = max(1, (int) $request->input('current', 1));
        $pageSize = (int) $request->input('pageSize', 100);
        $pageSize = $pageSize > 0 ? $pageSize : 100;

        $query = DeviceGroup::query()
            ->whereIn('id', $groupIds)
            ->orderBy('id');

        $total = $query->count();

        $groups = $query
            ->forPage($current, $pageSize)
            ->get();

        return response()->json([
            'total' => $total,
            'data' => $groups,
        ]);
    }
}
