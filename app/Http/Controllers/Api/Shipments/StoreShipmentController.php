<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Shipments;

use App\Actions\Shipments\CreateShipmentAction;
use App\DataTransferObjects\ShipmentCreationData;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreShipmentRequest;
use App\Http\Resources\ShipmentResource;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class StoreShipmentController extends Controller
{
    public function __invoke(StoreShipmentRequest $request, CreateShipmentAction $action): JsonResponse
    {
        $tenantId = (int) $request->user()->tenant_id;
        $shipment = $action->execute(ShipmentCreationData::fromRequest($request, $tenantId));

        return response()->json([
            'status' => 'success',
            'message' => 'Shipment created successfully.',
            'data' => new ShipmentResource($shipment),
        ], Response::HTTP_CREATED);
    }
}
