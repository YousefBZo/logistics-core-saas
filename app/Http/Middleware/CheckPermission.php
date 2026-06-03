<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Permission;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handling the incoming request and performing algebraic bit checking.

     *
     * @param  Closure(Request): (Response)  $next
     * @param  string  $permissionName  The permission name or numeric mask coming from the route (e.g., CREATE_SHIPMENT or 32)
     */
    public function handle(Request $request, Closure $next, string $permissionName): Response
    {
        // 1. Verify that the user is already logged in (Authentication Check)

        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (ctype_digit($permissionName)) {
            $permissionMask = (int) $permissionName;

            if ($permissionMask <= 0) {
                return response()->json(['message' => "Developer Error: Permission mask '{$permissionName}' is invalid."], 500);
            }

            if (($user->permissions_mask & $permissionMask) !== $permissionMask) {
                return response()->json([
                    'message' => 'Access Denied: You do not have the algebraic clearance for this logistics action.',
                ], 403);
            }

            return $next($request);
        }

        // 2. Attempt to match the incoming text with our mathematical Enum

        $permission = Permission::resolveRouteName($permissionName);

        if ($permission === null) {
            return response()->json(['message' => "Developer Error: Permission '{$permissionName}' does not exist."], 500);
        }

        // 3. The Algebraic Gate: Checking with the AND Gate

        // The Trait we programmed earlier contains a hasPermission function

        if (! $user->hasPermission($permission)) {
            return response()->json([
                'message' => 'Access Denied: You do not have the algebraic clearance for this logistics action.',
            ], 403); // 403 Forbidden

        }

        // If the mathematical check is successful, the request is passed smoothly to the Controller

        return $next($request);
    }
}
