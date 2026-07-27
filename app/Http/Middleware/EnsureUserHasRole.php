<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route middleware: `role:hr_officer,hr_admin` etc.
 *
 * This is a convenience for area-level gating only — record-level
 * authorisation always goes through Policies.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        abort_unless($user !== null && $user->is_active, 403);

        $allowed = array_map(fn (string $role) => Role::from($role), $roles);

        abort_unless(in_array($user->role, $allowed, true), 403);

        return $next($request);
    }
}
