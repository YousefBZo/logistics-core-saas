<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Auth\LoginAction;
use App\Actions\Auth\LogoutAction;
use App\Actions\Auth\RegisterMerchantAction;
use App\Actions\Auth\RegisterTenantAction;
use App\DataTransferObjects\LoginCredentialsData;
use App\DataTransferObjects\MerchantRegistrationData;
use App\DataTransferObjects\TenantRegistrationData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterMerchantRequest;
use App\Http\Requests\Auth\RegisterTenantRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class AuthController extends Controller
{
    public function registerCompany(RegisterTenantRequest $request, RegisterTenantAction $action): JsonResponse
    {
        $user = $action->execute(TenantRegistrationData::fromRequest($request));

        return response()->json([
            'message' => 'Your logistics company and admin account have been registered successfully.',
            'user' => new UserResource($user),
        ], Response::HTTP_CREATED);
    }

    public function registerMerchant(RegisterMerchantRequest $request, RegisterMerchantAction $action): JsonResponse
    {
        $user = $action->execute(MerchantRegistrationData::fromRequest($request));

        return response()->json([
            'message' => 'Merchant account created successfully.',
            'user' => new UserResource($user),
        ], Response::HTTP_CREATED);
    }

    public function login(LoginRequest $request, LoginAction $action): JsonResponse
    {
        $result = $action->execute(LoginCredentialsData::fromRequest($request));

        return response()->json([
            'message' => 'Logged in successfully.',
            'access_token' => $result->token,
            'token_type' => 'Bearer',
            'permissions_mask' => $result->permissionsMask,
            'user' => new UserResource($result->user),
        ], Response::HTTP_OK);
    }

    public function logout(LogoutAction $action): JsonResponse
    {
        $action->execute(auth()->user());

        return response()->json([
            'message' => 'Tokens revoked successfully. Logged out.',
        ], Response::HTTP_OK);
    }
}
