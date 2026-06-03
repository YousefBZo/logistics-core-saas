<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Staff;

use App\Actions\Staff\CreateStaffAction;
use App\DataTransferObjects\StaffCreationData;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStaffRequest;
use App\Http\Resources\StaffResource;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class StoreStaffController extends Controller
{
    /**
     * Handle the request to add new tenant staff and return a strict API contract.
     */
    public function __invoke(StoreStaffRequest $request, CreateStaffAction $action): JsonResponse
    {
        $tenantId = (int) $request->user()->tenant_id;
        $dto = StaffCreationData::fromRequest($request, $tenantId);
        $staff = $action->execute($dto);

        return response()->json([
            'status' => 'success',
            'message' => 'Staff member onboarded successfully inside your tenant.',
            'data' => new StaffResource($staff),
        ], Response::HTTP_CREATED);
    }
}
