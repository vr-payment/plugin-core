<?php

declare(strict_types=1);

namespace VRPayment\PluginCore\Tests\Sdk\WebServiceAPIV2;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use VRPayment\PluginCore\Log\LoggerInterface;
use VRPayment\PluginCore\Refund\Refund;
use VRPayment\PluginCore\Refund\RefundContext;
use VRPayment\PluginCore\Refund\Type as RefundType;
use VRPayment\PluginCore\Sdk\SdkProvider;
use VRPayment\PluginCore\Sdk\WebServiceAPIV2\RefundGateway;
use VRPayment\PluginCore\Transaction\Transaction;
use VRPayment\Sdk\Model\Refund as SdkRefund;
use VRPayment\Sdk\Model\RefundCreate as SdkRefundCreate;
use VRPayment\Sdk\Model\RefundState as SdkRefundState;
use VRPayment\Sdk\Service\RefundsService as SdkRefundsService;

class RefundGatewayTest extends TestCase
{
    private RefundGateway $gateway;
    private MockObject|SdkProvider $sdkProvider;
    private MockObject|LoggerInterface $logger;
    private MockObject|SdkRefundsService $refundService;

    protected function setUp(): void
    {
        $this->sdkProvider = $this->createMock(SdkProvider::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->refundService = $this->createMock(SdkRefundsService::class);

        $this->sdkProvider->method('getService')
            ->with(SdkRefundsService::class)
            ->willReturn($this->refundService);

        $this->gateway = new RefundGateway($this->sdkProvider, $this->logger);
    }

    public function testFindByTransactionReturnsArrayOfRefunds(): void
    {
        $spaceId = 1;
        $transactionId = 2;

        $sdkRefund = new SdkRefund();
        $sdkRefund->setId(10);
        $sdkRefund->setAmount(50.0);
        $sdkRefund->setExternalId('ext-1');
        $sdkRefund->setState(SdkRefundState::SUCCESSFUL);

        // V2: getPaymentRefundsSearch($space, filter, limit, offset, order, query)
        $this->refundService->expects($this->once())
            ->method('getPaymentRefundsSearch')
            ->with($spaceId, null, null, null, null, "transaction.id:$transactionId")
            ->willReturn([$sdkRefund]);

        $results = $this->gateway->findByTransaction($spaceId, $transactionId);

        $this->assertCount(1, $results);
        $result = $results[0];
        $this->assertEquals(10, $result->id);
        $this->assertEquals(50.0, $result->amount);
        $this->assertEquals('SUCCESSFUL', $result->state->value);
    }

    public function testRefundDelegatesToServiceAndMapsResult(): void
    {
        $spaceId = 1;
        $transaction = new Transaction();
        $transaction->id = 2;

        $context = new RefundContext(
            $transaction->id,
            10.0,
            'ref-1',
            RefundType::MERCHANT_INITIATED_ONLINE,
            [],
        );

        $sdkRefund = new SdkRefund();
        $sdkRefund->setId(20);
        $sdkRefund->setAmount(10.0);
        $sdkRefund->setExternalId('ext-2');
        $sdkRefund->setState(SdkRefundState::PENDING);

        // V2: postPaymentRefunds($space, $create)
        $this->refundService->expects($this->once())
            ->method('postPaymentRefunds')
            ->with($this->equalTo($spaceId), $this->callback(function (SdkRefundCreate $create) use ($context) {
                return $create->getTransaction() === $context->transactionId &&
                    $create->getAmount() === $context->amount &&
                    $create->getMerchantReference() === $context->merchantReference;
            }))
            ->willReturn($sdkRefund);

        $result = $this->gateway->refund($spaceId, $context);

        $this->assertInstanceOf(Refund::class, $result);
        $this->assertEquals(20, $result->id);
        $this->assertEquals(10.0, $result->amount);
    }
}
