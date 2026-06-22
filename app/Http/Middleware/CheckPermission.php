<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates an admin route behind a console permission string, e.g. `permission:devices.view`.
 *
 * Assumes 'auth' and 'admin' (EnsureAdmin) already ran. `is_admin` users always pass.
 * A delegated admin without the permission is redirected to the dashboard with an error
 * (or gets a 403 for non-GET / API-style requests).
 */
class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = Auth::user();

        if ($user && $user->hasPermission($permission)) {
            return $next($request);
        }

        if ($request->expectsJson() || ! $request->isMethod('GET')) {
            abort(403, 'You do not have permission to perform this action.');
        }

        return redirect()->route('admin.dashboard')
            ->withErrors(['permission' => 'You do not have permission to access that area.']);
    }
}
