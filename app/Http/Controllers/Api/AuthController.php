<?php

namespace App\Http\Controllers\Api;

use App\Actions\Auth\LoginAction;
use App\Actions\Auth\RegisterMerchantAction;
use App\Actions\Auth\RegisterTenantAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterMerchantRequest;
use App\Http\Requests\Auth\RegisterTenantRequest;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function registerCompany(RegisterTenantRequest $request, RegisterTenantAction $action): JsonResponse
    {
        $user = $action->execute($request->validated());

        return response()->json([
            'message' => 'Your logistics company and admin account have been registered successfully.',
            'user' => $user,
        ], 201);
    }

    public function registerMerchant(RegisterMerchantRequest $request, RegisterMerchantAction $action): JsonResponse
    {
        $user = $action->execute($request->validated());

        return response()->json([
            'message' => 'Merchant account created successfully.',

            'user' => $user,
        ], 201);
    }

    // 3. Single sign-on for all parties and issuing a permissions mask

    public function login(LoginRequest $request, LoginAction $action): JsonResponse
    {
        $result = $action->execute(
            $request->validated('email'),

            $request->validated('password')

        );

        return response()->json([
            'message' => 'Logged in successfully.',
            'access_token' => $result['token'],

            'token_type' => 'Bearer',

            'permissions_mask' => $result['permissions_mask'], // Bits that will determine the front-end screens immediately
            'user' => $result['user'],

        ], 200);
    }

    // 4. Logout and destroy the current token
    public function logout(): JsonResponse
    {
        // Delete the current token used for authentication to protect the session
        auth()->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Tokens revoked successfully. Logged out.',

        ], 200);
    }
}
