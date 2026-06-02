<?php

namespace Tests\Unit;

use App\Enums\Permission;
use PHPUnit\Framework\TestCase;

class PermissionTest extends TestCase
{
    public function test_permission_bits_use_expected_values(): void
    {
        $this->assertSame(1, Permission::CREATE_SHIPMENT->value);
        $this->assertSame(2, Permission::VIEW_SHIPMENT->value);
        $this->assertSame(4, Permission::SORT_PACKAGES->value);
        $this->assertSame(8, Permission::ASSIGN_DRIVERS->value);
        $this->assertSame(16, Permission::DELIVER_SHIPMENT->value);
        $this->assertSame(32, Permission::MANAGE_TENANT->value);
    }

    public function test_default_permission_masks_combine_expected_bits(): void
    {
        $this->assertSame(3, Permission::merchantDefault());
        $this->assertSame(18, Permission::driverDefault());
        $this->assertSame(6, Permission::warehouseDefault());
    }
}
