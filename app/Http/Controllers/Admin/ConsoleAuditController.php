<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConsoleAudit;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Read-only viewer for the console-operation audit log (admin mutations).
 */
class ConsoleAuditController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $logs = ConsoleAudit::query()
            ->with('user:id,username')
            ->when($q !== '', function ($query) use ($q) {
                $query->where('path', 'like', "%{$q}%")
                    ->orWhere('route_name', 'like', "%{$q}%")
                    ->orWhere('method', 'like', "%{$q}%")
                    ->orWhere('ip', 'like', "%{$q}%");
            })
            ->orderByDesc('created_at')
            ->paginate(30)
            ->appends($request->query());

        return view('admin.console_audit.index', compact('logs', 'q'));
    }
}
