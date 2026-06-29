<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates each request by the module its route belongs to (config/modules.php).
 * Uncategorised routes (dashboard, profile, logout) are always allowed for a
 * logged-in user. super_admin bypasses everything via User::canModule().
 */
class EnsureModuleAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user   = $request->user();
        $module = User::moduleForRoute($request->route()?->getName());

        if ($user && $module && !$user->canModule($module)) {
            $label = config("modules.$module.label", 'that area');
            $msg   = "You don't have permission to access {$label}.";

            if ($request->expectsJson()) {
                abort(403, $msg);
            }
            return redirect()->route('dashboard')->with('error', $msg);
        }

        return $next($request);
    }
}
