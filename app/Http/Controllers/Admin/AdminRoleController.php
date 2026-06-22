<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminRole;
use App\Models\Group;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Admin Role management (Admin Role Layer 3, docs/modernization/12-access-control-design.md).
 * CRUD over scoped console roles: each role grants a set of permission strings and may be
 * scoped to user/device groups. Gated behind the `roles.*` permissions.
 */
class AdminRoleController extends Controller
{
    public function index(): View
    {
        $roles = AdminRole::query()
            ->withCount('users')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.admin_roles.index', compact('roles'));
    }

    public function create(): View
    {
        $role = new AdminRole(['type' => AdminRole::TYPE_GLOBAL, 'perms' => [], 'scope' => []]);
        $groups = Group::orderBy('name')->get(['id', 'name']);

        return view('admin.admin_roles.create', compact('role', 'groups'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateRole($request);

        AdminRole::create($data);

        return redirect()
            ->route('admin.roles.index')
            ->with('status', 'Role created.');
    }

    public function edit(AdminRole $role): View
    {
        $groups = Group::orderBy('name')->get(['id', 'name']);

        return view('admin.admin_roles.edit', compact('role', 'groups'));
    }

    public function update(Request $request, AdminRole $role): RedirectResponse
    {
        $role->fill($this->validateRole($request))->save();

        return redirect()
            ->route('admin.roles.index')
            ->with('status', 'Role updated.');
    }

    public function destroy(AdminRole $role): RedirectResponse
    {
        $role->users()->detach();
        $role->delete();

        return redirect()
            ->route('admin.roles.index')
            ->with('status', 'Role deleted.');
    }

    /**
     * Validate and normalise the submitted role: name, type, the checkbox-grid permissions
     * (restricted to the catalogue), and group scope (only meaningful for the group type).
     *
     * @return array<string, mixed>
     */
    private function validateRole(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(AdminRole::TYPES)],
            'perms' => ['array'],
            'perms.*' => ['string', Rule::in(AdminRole::allPermissions())],
            'scope' => ['array'],
            'scope.*' => ['integer', 'exists:groups,id'],
        ]);

        $perms = array_values(array_intersect(AdminRole::allPermissions(), $validated['perms'] ?? []));

        $scope = $validated['type'] === AdminRole::TYPE_GROUP
            ? array_values(array_map('intval', $validated['scope'] ?? []))
            : [];

        return [
            'name' => $validated['name'],
            'type' => $validated['type'],
            'perms' => $perms,
            'scope' => $scope,
        ];
    }
}
