<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bulk actions on the admin Users list: enable / disable / delete / set-group, with the acting
 * admin protected from self-disable and self-delete.
 */
class BulkUserActionTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'username' => 'admin'.uniqid(), 'password' => 'secret12345',
            'is_admin' => true, 'status' => User::STATUS_NORMAL,
        ]);
    }

    public function test_bulk_disable_skips_the_acting_admin(): void
    {
        $admin = $this->admin();
        $a = User::create(['username' => 'a', 'password' => 'secret12345', 'status' => User::STATUS_NORMAL]);
        $b = User::create(['username' => 'b', 'password' => 'secret12345', 'status' => User::STATUS_NORMAL]);

        $this->actingAs($admin)->post(route('admin.users.bulk'), [
            'ids' => [$a->id, $b->id, $admin->id], 'action' => 'disable',
        ])->assertRedirect();

        $this->assertSame(User::STATUS_DISABLED, $a->refresh()->status);
        $this->assertSame(User::STATUS_DISABLED, $b->refresh()->status);
        $this->assertSame(User::STATUS_NORMAL, $admin->refresh()->status); // protected
    }

    public function test_bulk_enable(): void
    {
        $admin = $this->admin();
        $a = User::create(['username' => 'a', 'password' => 'secret12345', 'status' => User::STATUS_DISABLED]);

        $this->actingAs($admin)->post(route('admin.users.bulk'), [
            'ids' => [$a->id], 'action' => 'enable',
        ])->assertRedirect();

        $this->assertSame(User::STATUS_NORMAL, $a->refresh()->status);
    }

    public function test_bulk_delete_skips_the_acting_admin(): void
    {
        $admin = $this->admin();
        $a = User::create(['username' => 'a', 'password' => 'secret12345', 'status' => User::STATUS_NORMAL]);

        $this->actingAs($admin)->post(route('admin.users.bulk'), [
            'ids' => [$a->id, $admin->id], 'action' => 'delete',
        ])->assertRedirect();

        $this->assertModelMissing($a);
        $this->assertModelExists($admin); // protected
    }

    public function test_bulk_set_group(): void
    {
        $admin = $this->admin();
        $group = Group::create(['name' => 'Team A']);
        $a = User::create(['username' => 'a', 'password' => 'secret12345', 'status' => User::STATUS_NORMAL]);

        $this->actingAs($admin)->post(route('admin.users.bulk'), [
            'ids' => [$a->id], 'action' => 'group', 'value' => $group->id,
        ])->assertRedirect();

        $this->assertSame($group->id, $a->refresh()->group_id);
    }
}
