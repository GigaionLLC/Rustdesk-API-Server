<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * System settings editor: an arbitrary key/value table plus a dedicated SMTP
 * section. Both persist into the system_settings table.
 */
class SettingController extends Controller
{
    /**
     * Keys reserved for the SMTP section (excluded from the generic editor).
     *
     * @var array<int, string>
     */
    private const SMTP_KEYS = [
        'smtp.host', 'smtp.port', 'smtp.username', 'smtp.password',
        'smtp.from', 'smtp.encryption',
    ];

    public function index(): View
    {
        $all = SystemSetting::orderBy('key')->get();

        $settings = $all->whereNotIn('key', self::SMTP_KEYS)->values();

        $smtp = [];
        foreach (self::SMTP_KEYS as $key) {
            $smtp[$key] = optional($all->firstWhere('key', $key))->value ?? '';
        }

        return view('admin.settings.index', compact('settings', 'smtp'));
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'setting_keys' => ['nullable', 'array'],
            'setting_keys.*' => ['nullable', 'string', 'max:255'],
            'setting_values' => ['nullable', 'array'],
            'setting_values.*' => ['nullable', 'string'],
        ]);

        $keys = $request->input('setting_keys', []);
        $values = $request->input('setting_values', []);

        $seen = [];
        foreach ($keys as $i => $key) {
            $key = trim((string) $key);
            if ($key === '' || in_array($key, self::SMTP_KEYS, true)) {
                continue;
            }
            $seen[] = $key;
            SystemSetting::updateOrCreate(
                ['key' => $key],
                ['value' => (string) ($values[$i] ?? '')]
            );
        }

        // Remove generic settings that were deleted from the editor.
        SystemSetting::whereNotIn('key', array_merge($seen, self::SMTP_KEYS))->delete();

        return response()->json([]);
    }

    public function updateSmtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'host' => ['nullable', 'string', 'max:255'],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'from' => ['nullable', 'email', 'max:255'],
            'encryption' => ['nullable', Rule::in(['', 'tls', 'ssl', 'none'])],
        ]);

        $map = [
            'smtp.host' => $data['host'] ?? '',
            'smtp.port' => isset($data['port']) ? (string) $data['port'] : '',
            'smtp.username' => $data['username'] ?? '',
            'smtp.from' => $data['from'] ?? '',
            'smtp.encryption' => $data['encryption'] ?? '',
        ];

        // Only overwrite the password when a new value is supplied.
        if (! empty($data['password'])) {
            $map['smtp.password'] = $data['password'];
        }

        foreach ($map as $key => $value) {
            SystemSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        return response()->json([]);
    }
}
