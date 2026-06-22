<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\DeviceGroup;
use App\Models\Strategy;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Device (peer) management: list, edit assignment/approval, delete.
 */
class DeviceController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $status = $request->query('status');

        $devices = Device::query()
            ->with('user:id,username')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('rustdesk_id', 'like', "%{$q}%")
                        ->orWhere('hostname', 'like', "%{$q}%")
                        ->orWhere('alias', 'like', "%{$q}%");
                });
            })
            ->when($status === 'online', fn ($query) => $query->where('is_online', true))
            ->when($status === 'offline', fn ($query) => $query->where('is_online', false))
            ->orderByDesc('last_online_at')
            ->paginate(20)
            ->appends($request->query());

        return view('admin.devices.index', compact('devices', 'q', 'status'));
    }

    public function edit(Device $device): View
    {
        $users = User::orderBy('username')->get(['id', 'username']);
        $deviceGroups = DeviceGroup::orderBy('name')->get(['id', 'name']);
        $strategies = Strategy::orderBy('name')->get(['id', 'name']);

        return view('admin.devices.edit', compact('device', 'users', 'deviceGroups', 'strategies'));
    }

    public function update(Request $request, Device $device): JsonResponse
    {
        $data = $request->validate([
            'alias' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:255'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'device_group_id' => ['nullable', 'integer', 'exists:device_groups,id'],
            'strategy_id' => ['nullable', 'integer', 'exists:strategies,id'],
            'approved' => ['nullable', 'boolean'],
        ]);

        $device->fill([
            'alias' => $data['alias'] ?? null,
            'note' => $data['note'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'device_group_id' => $data['device_group_id'] ?? null,
            'strategy_id' => $data['strategy_id'] ?? null,
            'approved' => (bool) ($data['approved'] ?? false),
        ])->save();

        return response()->json([]);
    }

    public function destroy(Device $device): RedirectResponse
    {
        $device->delete();

        return redirect()
            ->route('admin.devices.index')
            ->with('status', 'Device deleted.');
    }
}
