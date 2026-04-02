<?php

declare(strict_types=1);

namespace VRPayment\PluginCore\Tests\Sdk\SdkV2;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use VRPayment\PluginCore\Log\LoggerInterface;
use VRPayment\PluginCore\Sdk\SdkProvider;
use VRPayment\PluginCore\Sdk\SdkV2\RecurringTransactionGateway;
use VRPayment\PluginCore\Transaction\State as StateEnum;
use VRPayment\PluginCore\Transaction\Transaction;
use VRPayment\Sdk\Model\Charge as SdkCharge;
use VRPayment\Sdk\Model\ChargeState as SdkChargeState;
use VRPayment\Sdk\Model\Transaction as SdkTransaction;
use VRPayment\Sdk\Model\TransactionState as SdkTransactionState;
use VRPayment\Sdk\Service\TransactionsService as SdkTransactionsService;

class RecurringTransactionGatewayTest extends TestCase
{
    private RecurringTransactionGateway $gateway;
    private MockObject|SdkProvider $sdkProvider;
    private MockObject|LoggerInterface $logger;
    private MockObject|SdkTransactionsService $transactionService;

    protected function setUp(): void
    {
        $this->sdkProvider = $this->createMock(SdkProvider::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->transactionService = $this->createMock(SdkTransactionsService::class);

        $this->sdkProvider->method('getService')
            ->with(SdkTransactionsService::class)
            ->willReturn($this->transactionService);

        $this->gateway = new RecurringTransactionGateway($this->sdkProvider, $this->logger);
    }

    public function testProcessRecurringPaymentReturnsTransaction(): void
    {
        $spaceId = 1;
        $transactionId = 200;

        // The charge returned by processWithToken
        $sdkCharge = new SdkCharge();
        $sdkCharge->setState(SdkChargeState::SUCCESSFUL);

        // The transaction fetched after the charge
        $sdkTransaction = new SdkTransaction();
        $sdkTransaction->setId($transactionId);
        $sdkTransaction->setLinkedSpaceId($spaceId);
        $sdkTransaction->setVersion(1);
        $sdkTransaction->setState(SdkTransactionState::FULFILL);

        // Step 1: processWithToken is called to charge via the token
        $this->transactionService->expects($this->once())
            ->method('postPaymentTransactionsIdProcessWithToken')
            ->with($transactionId, $spaceId)
            ->willReturn($sdkCharge);

        // Step 2: getPaymentTransactionsId is called to fetch the updated transaction
        $this->transactionService->expects($this->once())
            ->method('getPaymentTransactionsId')
            ->with($transactionId, $spaceId)
            ->willReturn($sdkTransaction);

        $result = $this->gateway->processRecurringPayment($spaceId, $transactionId);

        $this->assertInstanceOf(Transaction::class, $result);
        $this->assertEquals($transactionId, $result->id);
        $this->assertEquals($spaceId, $result->spaceId);
        $this->assertEquals(StateEnum::FULFILL, $result->state);
    }

    public function testProcessRecurringPaymentThrowsExceptionOnError(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("API Error");

        // When processWithToken fails, the exception propagates
        $this->transactionService->expects($this->once())
            ->method('postPaymentTransactionsIdProcessWithToken')
            ->willThrowException(new \Exception("API Error"));

        $this->gateway->processRecurringPayment(1, 200);
    }
}
