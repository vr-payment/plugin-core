<?php

declare(strict_types=1);

namespace VRPayment\PluginCore\Tests\Sdk\WebServiceAPIV1;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use VRPayment\PluginCore\Log\LoggerInterface;
use VRPayment\PluginCore\ManualTask\Exception\ManualTaskException;
use VRPayment\PluginCore\ManualTask\State;
use VRPayment\PluginCore\Sdk\SdkProvider;
use VRPayment\PluginCore\Sdk\WebServiceAPIV1\ManualTaskGateway;
use VRPayment\Sdk\Model\CriteriaOperator as SdkCriteriaOperator;
use VRPayment\Sdk\Model\EntityQueryFilter as SdkEntityQueryFilter;
use VRPayment\Sdk\Model\EntityQueryFilterType as SdkEntityQueryFilterType;
use VRPayment\Sdk\Service\ManualTaskService as SdkManualTaskService;

class ManualTaskGatewayTest extends TestCase
{
    private ManualTaskGateway $gateway;
    private MockObject|LoggerInterface $logger;
    private MockObject|SdkManualTaskService $manualTaskService;
    private MockObject|SdkProvider $sdkProvider;

    protected function setUp(): void
    {
        $this->sdkProvider = $this->createMock(SdkProvider::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->manualTaskService = $this->createMock(SdkManualTaskService::class);

        $this->sdkProvider->method('getService')
            ->with(SdkManualTaskService::class)
            ->willReturn($this->manualTaskService);

        $this->gateway = new ManualTaskGateway(
            $this->sdkProvider,
            $this->logger,
        );
    }

    public function testCountByStateBuildsAnEqualsFilterOnStateAndReturnsTheCount(): void
    {
        $spaceId = 42;

        $this->manualTaskService->expects($this->once())
            ->method('count')
            ->with(
                $spaceId,
                $this->callback(function (SdkEntityQueryFilter $filter) {
                    return $filter->getType() === SdkEntityQueryFilterType::LEAF
                        && $filter->getOperator() === SdkCriteriaOperator::EQUALS
                        && $filter->getFieldName() === 'state'
                        && $filter->getValue() === State::OPEN->value;
                }),
            )
            ->willReturn(7);

        $this->assertSame(7, $this->gateway->countByState($spaceId, State::OPEN));
    }

    public function testCountByStateWrapsSdkFailuresInManualTaskException(): void
    {
        $spaceId = 42;

        $this->manualTaskService->expects($this->once())
            ->method('count')
            ->willThrowException(new \Exception('SDK unavailable'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Failed to count manual tasks from SDK.'));

        $this->expectException(ManualTaskException::class);
        $this->gateway->countByState($spaceId, State::OPEN);
    }
}
