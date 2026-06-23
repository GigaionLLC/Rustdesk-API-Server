<?php

namespace Tests\Feature;

use App\Models\AddressBook;
use App\Models\AddressBookPeer;
use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Per-address-book peer cap (rustdesk.ab_max_peers): enforced on the client API, the admin
 * manager and /api/v1, and advertised to the client via /api/ab/settings.
 */
class AddressBookQuotaTest extends TestCase
{
    use RefreshDatabase;

    private function clientToken(): string
    {
        User::create(['username' => 'cli', 'password' => 'secret12345', 'status' => User::STATUS_NORMAL]);

        return $this->postJson('/api/login', [
            'username' => 'cli', 'password' => 'secret12345', 'id' => 'd1', 'uuid' => 'u1',
        ])->json('access_token');
    }

    public function test_settings_advertises_the_cap(): void
    {
        config(['rustdesk.ab_max_peers' => 25]);

        $this->withHeader('Authorization', 'Bearer '.$this->clientToken())
            ->postJson('/api/ab/settings')
            ->assertOk()
            ->assertJsonPath('max_peer_one_ab', 25);
    }

    public function test_client_peer_add_blocked_when_full(): void
    {
        config(['rustdesk.ab_max_peers' => 1]);
        $token = $this->clientToken();

        // First add succeeds (empty ack).
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/ab/peer/add/personal', ['id' => '111'])
            ->assertOk();

        // Second exceeds the cap → error envelope, not an empty ack.
        $res = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/ab/peer/add/personal', ['id' => '222']);
        $res->assertOk();
        $this->assertStringContainsStringIgnoringCase('full', $res->getContent());

        $book = AddressBook::where('user_id', User::where('username', 'cli')->value('id'))->first();
        $this->assertSame(1, AddressBookPeer::where('address_book_id', $book->id)->count());
    }

    public function test_v1_peer_add_blocked_when_full(): void
    {
        config(['rustdesk.ab_max_peers' => 1]);

        $user = User::create([
            'username' => 'op', 'password' => 'secret12345', 'is_admin' => true, 'status' => User::STATUS_NORMAL,
        ]);
        [$plain, $prefix, $hash] = ApiKey::generateSecret();
        ApiKey::create([
            'user_id' => $user->id, 'name' => 'k', 'token_hash' => $hash,
            'prefix' => $prefix, 'scopes' => ['address_book.write'],
        ]);
        $book = AddressBook::create(['user_id' => $user->id, 'name' => 'My address book']);
        AddressBookPeer::create(['address_book_id' => $book->id, 'user_id' => $user->id, 'rustdesk_id' => '111']);

        $this->withHeader('Authorization', 'Bearer '.$plain)
            ->postJson("/api/v1/address-books/{$book->id}/peers", ['id' => '222'])
            ->assertStatus(422);
    }

    public function test_unlimited_by_default(): void
    {
        config(['rustdesk.ab_max_peers' => 0]);
        $token = $this->clientToken();

        foreach (['1', '2', '3'] as $id) {
            $this->withHeader('Authorization', 'Bearer '.$token)
                ->postJson('/api/ab/peer/add/personal', ['id' => $id])
                ->assertOk();
        }

        $book = AddressBook::where('user_id', User::where('username', 'cli')->value('id'))->first();
        $this->assertSame(3, AddressBookPeer::where('address_book_id', $book->id)->count());
    }
}
