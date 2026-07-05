<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates each request by the module its route belongs to (config/modules.php),
 * plus the fine-grained action permission for conventional resource writes:
 *
 *   *.store   -> {module}.create
 *   *.update  -> {module}.edit
 *   *.destroy -> {module}.delete
 *
 * So a user needs both module access (open the area) and the matching action
 * permission (perform the write). Only standard resource route names are
 * action-gated — custom routes keep module-level gating only, so existing
 * flows are never blocked unexpectedly. super_admin bypasses everything via
 * the Gate::before god-mode used by User::can()/canModule().
 */
class EnsureModuleAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user      = $request->user();
        $routeName = $request->route()?->getName();
        $module    = User::moduleForRoute($routeName);

        if ($user && $module) {
            // 1. Module-level access — may the user open this area at all?
            if (! $user->canModule($module)) {
                $label = config("modules.$module.label", 'that area');

                return $this->deny($request, "You don't have permission to access {$label}.");
            }

            // 2. Action-level — may the user perform this write?
            $action = $this->actionFor($routeName);
            if ($action && ! $user->can("{$module}.{$action}")) {
                return $this->deny($request, "You don't have permission to {$action} here.");
            }
        }

        return $next($request);
    }

    /** Map a resource route name to its action permission verb. */
    private function actionFor(?string $routeName): ?string
    {
        if ($routeName === null) {
            return null;
        }

        return match (true) {
            str_ends_with($routeName, '.store')   => 'create',
            str_ends_with($routeName, '.update')  => 'edit',
            str_ends_with($routeName, '.destroy') => 'delete',
            default                               => null,
        };
    }

    private function deny(Request $request, string $msg): Response
    {
        if ($request->expectsJson()) {
            abort(403, $msg);
        }

        return redirect()->route('dashboard')->with('error', $msg);
    }
}
