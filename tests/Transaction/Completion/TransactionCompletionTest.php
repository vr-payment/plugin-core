<?php

declare(strict_types=1);

namespace VRPayment\PluginCore\Tests\Transaction\Completion;

use PHPUnit\Framework\TestCase;
use VRPayment\PluginCore\Transaction\Completion\TransactionCompletion;
use VRPayment\PluginCore\Transaction\Completion\State;

class TransactionCompletionTest extends TestCase
{
    public function testToString(): void
    {
        $completion = new TransactionCompletion();
        $completion->id = 70;
        $completion->linkedTransactionId = 1001;
        $completion->state = State::SUCCESSFUL;

        $json = (string) $completion;
        $this->assertJson($json);
        $decoded = json_decode($json, true);

        $this->assertEquals(70, $decoded['id']);
        $this->assertEquals(1001, $decoded['linkedTransactionId']);
        $this->assertArrayHasKey('state', $decoded);
    }
}
