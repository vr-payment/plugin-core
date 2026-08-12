<?php

declare(strict_types=1);

namespace VRPayment\PluginCore\Sdk\WebServiceAPIV1;

use VRPayment\PluginCore\Log\DomainLoggerTrait;
use VRPayment\PluginCore\Log\LogContext;
use VRPayment\PluginCore\Log\LoggerInterface;
use VRPayment\PluginCore\ManualTask\Exception\ManualTaskException;
use VRPayment\PluginCore\ManualTask\ManualTaskGatewayInterface;
use VRPayment\PluginCore\ManualTask\State;
use VRPayment\PluginCore\Sdk\SdkProvider;
use VRPayment\Sdk\Model\CriteriaOperator as SdkCriteriaOperator;
use VRPayment\Sdk\Model\EntityQueryFilter as SdkEntityQueryFilter;
use VRPayment\Sdk\Model\EntityQueryFilterType as SdkEntityQueryFilterType;
use VRPayment\Sdk\Service\ManualTaskService as SdkManualTaskService;

/**
 * Gateway implementation using the SDK.
 */
#[LogContext(domain: 'manual_task')]
class ManualTaskGateway implements ManualTaskGatewayInterface
{
    use DomainLoggerTrait;

    /**
     * @param SdkProvider $sdkProvider The SDK provider.
     * @param LoggerInterface $logger The logger instance.
     */
    public function __construct(
        private readonly SdkProvider $sdkProvider,
        LoggerInterface $logger,
    ) {
        $this->initializeLogger($logger);
    }

    /**
     * @inheritDoc
     */
    public function countAll(int $spaceId): int
    {
        try {
            /** @var SdkManualTaskService $service */
            $service = $this->sdkProvider->getService(SdkManualTaskService::class);

            // No filter: the endpoint then counts every manual task in the space.
            return (int)$service->count($spaceId);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to count manual tasks from SDK.', [
                'spaceId' => $spaceId,
                'exception' => $e,
            ]);
            throw SdkProvider::wrapException(
                $e,
                ManualTaskException::class,
                'count',
                ['spaceId' => $spaceId],
                'Failed to retrieve manual tasks. Please try again or contact support.',
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function countByState(int $spaceId, State $state): int
    {
        try {
            /** @var SdkManualTaskService $service */
            $service = $this->sdkProvider->getService(SdkManualTaskService::class);

            $filter = new SdkEntityQueryFilter();
            $filter->setType(SdkEntityQueryFilterType::LEAF);
            $filter->setOperator(SdkCriteriaOperator::EQUALS);
            $filter->setFieldName('state');
            $filter->setValue($state->value);

            return (int)$service->count($spaceId, $filter);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to count manual tasks from SDK.', [
                'spaceId' => $spaceId,
                'state' => $state->value,
                'exception' => $e,
            ]);
            throw SdkProvider::wrapException(
                $e,
                ManualTaskException::class,
                'count',
                ['spaceId' => $spaceId, 'state' => $state->value],
                'Failed to retrieve manual tasks. Please try again or contact support.',
            );
        }
    }

}
