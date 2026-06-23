<?php

namespace Tests\Feature;

use App\Models\AddressBook;
use App\Models\AddressBookPeer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Per-book peer-cap override (address_books.max_peers): a book's own cap wins over the
 * server-wide default; null falls back to the global setting.
 */
class AddressBookPerBookQuotaTest extends TestCase
{
    use RefreshDatabase;

    private function clientToken(): string
    {
        User::create(['username' => 'cli', 'password' => 'secret12345', 'status' => User::STATUS_NORMAL]);

        return $this->postJson('/api/login', [
            'username' => 'cli', 'password' => 'secret12345', 'id' => 'd1', 'uuid' => 'u1',
        ])->json('access_token');
    }

    public function test_effective_cap_prefers_the_per_book_value(): void
    {
        config(['rustdesk.ab_max_peers' => 100]);

        $book = new AddressBook(['name' => 'b', 'max_peers' => 5]);
        $this->assertSame(5, $book->effectiveMaxPeers());

        $book->max_peers = null;
        $this->assertSame(100, $book->effectiveMaxPeers()); // falls back to global

        $book->max_peers = 0;
        $this->assertSame(0, $book->effectiveMaxPeers()); // explicit unlimited
    }

    public function test_per_book_cap_blocks_add_even_when_global_is_higher(): void
    {
        config(['rustdesk.ab_max_peers' => 100]); // generous global
        $token = $this->clientToken();
        $user = User::where('username', 'cli')->firstOrFail();

        // The client's personal book, capped at 1 specifically.
        $book = AddressBook::create(['user_id' => $user->id, 'name' => 'My address book', 'max_peers' => 1]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/ab/peer/add/'.$book->id, ['id' => '111'])->assertOk();

        $res = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/ab/peer/add/'.$book->id, ['id' => '222']);
        $res->assertOk();
        $this->assertStringContainsStringIgnoringCase('full', $res->getContent());

        $this->assertSame(1, AddressBookPeer::where('address_book_id', $book->id)->count());
    }

    public function test_admin_sets_the_per_book_cap(): void
    {
        $admin = User::create([
            'username' => 'admin', 'password' => 'secret12345', 'is_admin' => true, 'status' => User::STATUS_NORMAL,
        ]);
        $book = AddressBook::create(['user_id' => $admin->id, 'name' => 'Book']);

        $this->actingAs($admin)->put(route('admin.address-books.sharing', $book), [
            'max_peers' => 25,
        ])->assertRedirect();

        $this->assertSame(25, $book->refresh()->max_peers);

        // Blanking it clears back to the default.
        $this->actingAs($admin)->put(route('admin.address-books.sharing', $book), [])->assertRedirect();
        $this->assertNull($book->refresh()->max_peers);
    }
}
