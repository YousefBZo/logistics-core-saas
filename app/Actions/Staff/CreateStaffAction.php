<?php

declare(strict_types=1);

namespace App\Actions\Staff;

use App\DataTransferObjects\StaffCreationData;
use App\Enums\Permission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class CreateStaffAction
{
    public function execute(StaffCreationData $data): User
    {
        return DB::transaction(function () use ($data): User {
            $permissionsMask = match ($data->roleType) {
                'driver' => Permission::driverDefault(),
                'warehouse_manager' => Permission::warehouseDefault(),
                default => throw new InvalidArgumentException('Unsupported staff role type.'),
            };

            return User::provision(
                attributes: [
                    'name' => $data->name,
                    'email' => $data->email,
                    'password' => $data->password,
                    'phone' => $data->phone,
                    'status' => 'active',
                ],
                tenantId: $data->tenantId,
                permissionsMask: $permissionsMask,
            );
        });
    }
}
