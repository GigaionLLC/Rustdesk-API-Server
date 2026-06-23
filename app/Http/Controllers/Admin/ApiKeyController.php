<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Manage scoped API keys for the admin REST API (/api/v1). The plaintext secret is shown
 * exactly once, on creation.
 */
class ApiKeyController extends Controller
{
    public function index(): View
    {
        $keys = ApiKey::with('user:id,username')->orderByDesc('id')->get();

        return view('admin.api_keys.index', ['keys' => $keys, 'scopeList' => ApiKey::SCOPES]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'scopes' => ['required', 'array', 'min:1'],
            'scopes.*' => [Rule::in(array_keys(ApiKey::SCOPES))],
            'allowed_ips' => ['nullable', 'string', 'max:1000'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        [$plain, $prefix, $hash] = ApiKey::generateSecret();

        ApiKey::create([
            'user_id' => $request->user()->id,
            'name' => $data['name'],
            'token_hash' => $hash,
            'prefix' => $prefix,
            'scopes' => array_values($data['scopes']),
            'allowed_ips' => $this->normalizeIps($data['allowed_ips'] ?? null),
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        return back()
            ->with('new_api_key', $plain)
            ->with('status', 'API key created — copy it now; it will not be shown again.');
    }

    /**
     * Rotate a key's secret in place (same name/scopes/IP rules). The old secret stops working
     * immediately; the new one is shown once.
     */
    public function rotate(ApiKey $apiKey): RedirectResponse
    {
        [$plain, $prefix, $hash] = ApiKey::generateSecret();

        $apiKey->forceFill([
            'token_hash' => $hash,
            'prefix' => $prefix,
            'last_used_at' => null,
            'last_used_ip' => null,
        ])->save();

        return back()
            ->with('new_api_key', $plain)
            ->with('status', "Key '{$apiKey->name}' rotated — the old secret no longer works.");
    }

    /**
     * Normalise a comma/space/newline-separated IP list to a trimmed comma list, or null.
     */
    private function normalizeIps(?string $raw): ?string
    {
        $ips = array_values(array_filter(array_map('trim', preg_split('/[\s,]+/', (string) $raw) ?: [])));

        return $ips === [] ? null : implode(',', $ips);
    }

    public function destroy(ApiKey $apiKey): RedirectResponse
    {
        $apiKey->delete();

        return back()->with('status', 'API key revoked.');
    }
}
