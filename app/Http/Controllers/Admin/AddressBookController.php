<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AddressBook;
use App\Models\AddressBookPeer;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Read-mostly address book browser: list collections, inspect peers/tags,
 * and prune entries.
 */
class AddressBookController extends Controller
{
    public function index(): View
    {
        $addressBooks = AddressBook::query()
            ->with('user:id,username')
            ->withCount(['peers', 'tags'])
            ->orderBy('name')
            ->paginate(20);

        return view('admin.address_books.index', compact('addressBooks'));
    }

    public function show(AddressBook $addressBook): View
    {
        $addressBook->load('user:id,username', 'tags');

        $peers = $addressBook->peers()
            ->orderBy('rustdesk_id')
            ->paginate(25);

        return view('admin.address_books.show', compact('addressBook', 'peers'));
    }

    public function destroy(AddressBook $addressBook): RedirectResponse
    {
        $addressBook->peers()->delete();
        $addressBook->tags()->delete();
        $addressBook->delete();

        return redirect()
            ->route('admin.address-books.index')
            ->with('status', 'Address book deleted.');
    }

    public function destroyPeer(AddressBookPeer $peer): RedirectResponse
    {
        $bookId = $peer->address_book_id;
        $peer->delete();

        return redirect()
            ->route('admin.address-books.show', $bookId)
            ->with('status', 'Peer removed.');
    }

    public function destroyTag(Tag $tag): RedirectResponse
    {
        $bookId = $tag->address_book_id;
        $tag->delete();

        return redirect()
            ->route('admin.address-books.show', $bookId)
            ->with('status', 'Tag removed.');
    }
}
