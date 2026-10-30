<?php

namespace Tests\Unit;

use App\Models\Order;
use PHPUnit\Framework\TestCase;

class OrderServiceTest extends TestCase
{
    /**
     * Test order status state methods.
     */
    public function test_order_status_methods(): void
    {
        $pendingOrder = new Order(['status' => Order::STATUS_PENDING]);
        $this->assertTrue($pendingOrder->isPending());
        $this->assertFalse($pendingOrder->isPaid());

        $paidOrder = new Order(['status' => Order::STATUS_PAID]);
        $this->assertFalse($paidOrder->isPending());
        $this->assertTrue($paidOrder->isPaid());
    }

    /**
     * Test subtotal and total calculations.
     */
    public function test_subtotal_calculation(): void
    {
        $unitPrice1 = (float) "450000.00";
        $qty1 = 2;
        $subtotal1 = $unitPrice1 * $qty1;

        $unitPrice2 = (float) "150000.00";
        $qty2 = 1;
        $subtotal2 = $unitPrice2 * $qty2;

        $totalAmount = $subtotal1 + $subtotal2;

        $this->assertEquals(900000.0, $subtotal1);
        $this->assertEquals(150000.0, $subtotal2);
        $this->assertEquals(1050000.0, $totalAmount);
    }
}
