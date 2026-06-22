<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate admin-console routes behind an active account that may manage the console.
 * Assumes the 'auth' middleware already ensured the request is authenticated.
 *
 * Access is granted to full administrators (`is_admin`) and to delegated admins who hold at
 * least one scoped admin role (Admin Role Layer 3). Per-area authorization is then enforced
 * by the `permission:` middleware. Backward compatible: with only `is_admin` users present,
 * behaviour is identical to before.
 */
class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user || ! $user->isActive() || (! $user->is_admin && $user->adminRoles()->doesntExist())) {
            Auth::logout();

            return redirect()->route('admin.login')
                ->withErrors(['username' => 'Administrator access required.']);
        }

        return $next($request);
    }
}
