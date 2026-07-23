<?php

declare(strict_types=1);

namespace VRPayment\PluginCore\Tests\Refund;

use PHPUnit\Framework\TestCase;
use VRPayment\PluginCore\Refund\LineItem\RefundLineItem;
use VRPayment\PluginCore\Refund\LineItem\RefundLineItemCollection;
use VRPayment\PluginCore\Refund\RefundContext;
use VRPayment\PluginCore\Refund\Type;

class RefundContextTest extends TestCase
{
    public function testToString(): void
    {
        $context = new RefundContext(
            1001,
            10.50,
            'REF-001-REQ',
            Type::MERCHANT_INITIATED_ONLINE,
            new RefundLineItemCollection(new RefundLineItem('ITEM-1', 1.0, 10.50)),
        );

        $json = (string) $context;
        $this->assertJson($json);
        $decoded = json_decode($json, true);

        $this->assertEquals(1001, $decoded['transactionId']);
        $this->assertEquals(10.50, $decoded['amount']);
        $this->assertEquals('REF-001-REQ', $decoded['merchantReference']);
        $this->assertArrayHasKey('type', $decoded);
        $this->assertEquals(
            [['uniqueId' => 'ITEM-1', 'returnedQuantity' => 1.0, 'unitPriceReduction' => 10.50]],
            $decoded['lineItems'],
        );
    }
}
