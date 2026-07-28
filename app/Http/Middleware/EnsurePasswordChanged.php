<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Forces users flagged with must_change_password (accounts provisioned by HR)
 * onto the password-change screen until they set their own password.
 */
class EnsurePasswordChanged
{
    /**
     * Routes reachable while a password change is still pending.
     */
    private const ALLOWED = [
        'password.change',
        'password.change.update',
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && $user->must_change_password && ! $this->isAllowed($request)) {
            return redirect()->route('password.change');
        }

        return $next($request);
    }

    private function isAllowed(Request $request): bool
    {
        foreach (self::ALLOWED as $name) {
            if ($request->routeIs($name)) {
                return true;
            }
        }

        return false;
    }
}
