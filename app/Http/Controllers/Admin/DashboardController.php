<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditConn;
use App\Models\Device;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Overview dashboard: device/user counts, recent activity and a 14-day
 * connections sparkline.
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        $deviceCount = Device::count();
        $onlineCount = Device::where('is_online', true)->count();
        $userCount = User::count();

        $since = Carbon::now()->subHours(24);
        $sessions24h = AuditConn::where('action', AuditConn::ACTION_NEW)
            ->where('created_at', '>=', $since)
            ->count();

        $stats = [
            ['label' => 'Total Devices', 'value' => number_format($deviceCount), 'icon' => 'ri-computer-line', 'tone' => 'primary', 'trend' => null],
            ['label' => 'Online Now', 'value' => number_format($onlineCount), 'icon' => 'ri-base-station-line', 'tone' => 'success', 'trend' => null],
            ['label' => 'Users', 'value' => number_format($userCount), 'icon' => 'ri-user-line', 'tone' => 'warning', 'trend' => null],
            ['label' => 'Sessions (24h)', 'value' => number_format($sessions24h), 'icon' => 'ri-exchange-line', 'tone' => 'danger', 'trend' => null],
        ];

        $recentDevices = Device::orderByDesc('last_online_at')
            ->limit(8)
            ->get()
            ->map(fn (Device $d) => [
                'id' => $d->rustdesk_id,
                'hostname' => $d->hostname ?: $d->alias ?: $d->rustdesk_id,
                'os' => $d->os,
                'online' => (bool) $d->is_online,
                'last_seen' => $d->last_online_at?->diffForHumans(),
            ])
            ->all();

        // Connections per day for the last 14 days (action = new).
        $days = 14;
        $start = Carbon::today()->subDays($days - 1);

        $counts = AuditConn::where('action', AuditConn::ACTION_NEW)
            ->where('created_at', '>=', $start)
            ->select(DB::raw('DATE(created_at) as d'), DB::raw('COUNT(*) as c'))
            ->groupBy('d')
            ->pluck('c', 'd')
            ->all();

        $chartSeries = [];
        $chartCategories = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $start->copy()->addDays($i);
            $key = $day->toDateString();
            $chartSeries[] = (int) ($counts[$key] ?? 0);
            $chartCategories[] = $day->format('M j');
        }

        return view('admin.dashboard', compact(
            'stats',
            'recentDevices',
            'chartSeries',
            'chartCategories'
        ));
    }
}
