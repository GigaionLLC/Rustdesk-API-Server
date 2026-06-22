<?php

namespace Tests\Feature;

use App\Models\AdminRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers Admin Role Layer 3 (docs/modernization/12-access-control-design.md): delegated,
 * scoped console permissions. The headline requirement is backward compatibility — an
 * `is_admin` user keeps full access — so the first case asserts exactly that.
 */
class AdminRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_admin_reaches_users_page(): void
    {
        $admin = User::create([
            'username' => 'fulladmin', 'password' => 'secret12345',
            'is_admin' => true, 'status' => User::STATUS_NORMAL,
        ]);

        $this->actingAs($admin)->get('/admin/users')->assertOk();
    }

    public function test_scoped_admin_reaches_only_granted_area(): void
    {
        $role = AdminRole::create([
            'name' => 'Device viewer',
            'type' => AdminRole::TYPE_INDIVIDUAL,
            'scope' => [],
            'perms' => ['devices.view'],
        ]);

        $delegate = User::create([
            'username' => 'delegate', 'password' => 'secret12345',
            'is_admin' => false, 'status' => User::STATUS_NORMAL,
        ]);
        $delegate->adminRoles()->attach($role);

        // Granted area is reachable.
        $this->actingAs($delegate)->get('/admin/devices')->assertOk();

        // A non-granted area is denied (redirected to the dashboard with an error).
        $this->actingAs($delegate)->get('/admin/users')
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHasErrors('permission');
    }

    public function test_plain_non_admin_is_rejected_from_console(): void
    {
        $user = User::create([
            'username' => 'plain', 'password' => 'secret12345',
            'is_admin' => false, 'status' => User::STATUS_NORMAL,
        ]);

        // No is_admin and no roles: EnsureAdmin logs them out and sends them to login.
        $this->actingAs($user)->get('/admin')->assertRedirect('/admin/login');
    }
}
