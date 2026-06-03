<?php

declare(strict_types=1);

namespace App\Enums;

enum ShipmentStatus: string
{
    case CREATED = 'created';
    case PENDING = 'pending';
    case PICKED_UP = 'picked_up';
    case RECEIVED_IN_WAREHOUSE = 'received_in_warehouse';
    case OUT_FOR_DELIVERY = 'out_for_delivery';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
    case RETURNED = 'returned';
}
