<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\DeviceGroup;
use App\Models\Strategy;
use App\Models\StrategyAssignment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Strategy management: list, create, and an editor for the config_options
 * key/value map, enable toggle, and target assignments.
 */
class StrategyController extends Controller
{
    public function index(): View
    {
        $strategies = Strategy::withCount('assignments')->orderBy('name')->paginate(20);

        return view('admin.strategies.index', compact('strategies'));
    }

    public function create(): View
    {
        $strategy = new Strategy(['enabled' => true, 'options' => []]);

        return view('admin.strategies.create', compact('strategy'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        Strategy::create([
            'name' => $data['name'],
            'note' => $data['note'] ?? null,
            'enabled' => true,
            'options' => [],
            'modified_at' => time(),
        ]);

        return redirect()
            ->route('admin.strategies.index')
            ->with('status', 'Strategy created.');
    }

    public function edit(Strategy $strategy): View
    {
        $strategy->load('assignments');

        $devices = Device::orderBy('rustdesk_id')->get(['id', 'rustdesk_id', 'hostname', 'alias']);
        $users = User::orderBy('username')->get(['id', 'username']);
        $deviceGroups = DeviceGroup::orderBy('name')->get(['id', 'name']);

        // Build readable labels for the existing assignments.
        $deviceMap = $devices->keyBy('id');
        $userMap = $users->keyBy('id');
        $deviceGroupMap = $deviceGroups->keyBy('id');

        return view('admin.strategies.edit', compact(
            'strategy',
            'devices',
            'users',
            'deviceGroups',
            'deviceMap',
            'userMap',
            'deviceGroupMap'
        ));
    }

    public function update(Request $request, Strategy $strategy): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:255'],
            'enabled' => ['nullable', 'boolean'],
            'option_keys' => ['nullable', 'array'],
            'option_keys.*' => ['nullable', 'string', 'max:255'],
            'option_values' => ['nullable', 'array'],
            'option_values.*' => ['nullable', 'string'],
        ]);

        // Zip parallel key/value arrays into the options map; skip empty keys.
        $keys = $request->input('option_keys', []);
        $values = $request->input('option_values', []);
        $options = [];
        foreach ($keys as $i => $key) {
            $key = trim((string) $key);
            if ($key === '') {
                continue;
            }
            $options[$key] = (string) ($values[$i] ?? '');
        }

        $strategy->fill([
            'name' => $request->input('name'),
            'note' => $request->input('note'),
            'enabled' => $request->boolean('enabled'),
            'options' => $options,
            // Bump so clients pull the new config within one heartbeat.
            'modified_at' => time(),
        ])->save();

        return response()->json([]);
    }

    public function storeAssignment(Request $request, Strategy $strategy): RedirectResponse
    {
        $data = $request->validate([
            'target_type' => ['required', Rule::in([
                StrategyAssignment::TARGET_DEVICE,
                StrategyAssignment::TARGET_USER,
                StrategyAssignment::TARGET_DEVICE_GROUP,
            ])],
            'target_id' => ['required', 'integer'],
        ]);

        $strategy->assignments()->firstOrCreate([
            'target_type' => $data['target_type'],
            'target_id' => $data['target_id'],
        ]);

        $strategy->forceFill(['modified_at' => time()])->save();

        return redirect()
            ->route('admin.strategies.edit', $strategy)
            ->with('status', 'Assignment added.');
    }

    public function destroyAssignment(StrategyAssignment $assignment): RedirectResponse
    {
        $strategyId = $assignment->strategy_id;
        $assignment->delete();

        Strategy::whereKey($strategyId)->update(['modified_at' => time()]);

        return redirect()
            ->route('admin.strategies.edit', $strategyId)
            ->with('status', 'Assignment removed.');
    }

    public function destroy(Strategy $strategy): RedirectResponse
    {
        $strategy->assignments()->delete();
        $strategy->delete();

        return redirect()
            ->route('admin.strategies.index')
            ->with('status', 'Strategy deleted.');
    }
}
