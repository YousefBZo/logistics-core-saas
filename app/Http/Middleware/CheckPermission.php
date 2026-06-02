<?php

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
     * @param  string  $permissionName  The permission name coming from the route (e.g., CREATE_SHIPMENT)
     */
    public function handle(Request $request, Closure $next, string $permissionName): Response
    {
        // 1. Verify that the user is already logged in (Authentication Check)

        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // 2. Attempt to match the incoming text with our mathematical Enum

        try {
            $permission = constant("App\Enums\Permission::$permissionName");
        } catch (\Error $e) {
            // Developer protection: If the developer enters an incorrect permission name in the Route, the system will immediately alert them

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
