<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditConn;
use App\Models\AuditFile;
use App\Models\LoginLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Read-only audit views: connection, file-transfer, and login logs.
 */
class AuditController extends Controller
{
    public function connections(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $action = $request->query('action');

        $logs = AuditConn::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where('peer_id', 'like', "%{$q}%")
                    ->orWhere('from_peer', 'like', "%{$q}%")
                    ->orWhere('from_name', 'like', "%{$q}%")
                    ->orWhere('ip', 'like', "%{$q}%");
            })
            ->when(in_array($action, [AuditConn::ACTION_NEW, AuditConn::ACTION_CLOSE], true),
                fn ($query) => $query->where('action', $action))
            ->orderByDesc('created_at')
            ->paginate(30)
            ->appends($request->query());

        return view('admin.audit.connections', compact('logs', 'q', 'action'));
    }

    public function files(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $logs = AuditFile::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where('peer_id', 'like', "%{$q}%")
                    ->orWhere('from_peer', 'like', "%{$q}%")
                    ->orWhere('from_name', 'like', "%{$q}%")
                    ->orWhere('path', 'like', "%{$q}%")
                    ->orWhere('ip', 'like', "%{$q}%");
            })
            ->orderByDesc('created_at')
            ->paginate(30)
            ->appends($request->query());

        return view('admin.audit.files', compact('logs', 'q'));
    }

    public function logins(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $logs = LoginLog::query()
            ->with('user:id,username')
            ->when($q !== '', function ($query) use ($q) {
                $query->where('client', 'like', "%{$q}%")
                    ->orWhere('device_id', 'like', "%{$q}%")
                    ->orWhere('ip', 'like', "%{$q}%")
                    ->orWhere('platform', 'like', "%{$q}%");
            })
            ->orderByDesc('created_at')
            ->paginate(30)
            ->appends($request->query());

        return view('admin.audit.logins', compact('logs', 'q'));
    }
}
