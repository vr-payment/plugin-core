<?php

declare(strict_types=1);

namespace VRPayment\PluginCore\Sdk\WebServiceAPIV1;

use VRPayment\PluginCore\Log\LoggerInterface;
use VRPayment\PluginCore\PaymentMethod\PaymentMethod;
use VRPayment\PluginCore\PaymentMethod\PaymentMethodCollection;
use VRPayment\PluginCore\PaymentMethod\PaymentMethodGatewayInterface;
use VRPayment\PluginCore\Sdk\PaymentMethodMapperTrait;
use VRPayment\PluginCore\Sdk\SdkProvider;
use VRPayment\Sdk\Model\CreationEntityState as SdkCreationEntityState;
use VRPayment\Sdk\Model\CriteriaOperator as SdkCriteriaOperator;
use VRPayment\Sdk\Model\EntityQuery as SdkEntityQuery;
use VRPayment\Sdk\Model\EntityQueryFilter as SdkEntityQueryFilter;
use VRPayment\Sdk\Model\EntityQueryFilterType as SdkEntityQueryFilterType;
use VRPayment\Sdk\Service\PaymentMethodConfigurationService as SdkPaymentMethodConfigurationService;

/**
 * Gateway implementation using the SDK.
 */
class PaymentMethodGateway implements PaymentMethodGatewayInterface
{
    use PaymentMethodMapperTrait;

    /**
     * @param SdkProvider $provider The SDK provider.
     * @param LoggerInterface $logger The logger instance.
     */
    public function __construct(
        private readonly SdkProvider $provider,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Helper to create an SDK filter.
     *
     * @param string $fieldName The field to filter on.
     * @param mixed $value The value to filter by.
     * @param string $operator The operator to use (defaults to EQUALS).
     * @return SdkEntityQueryFilter The created filter.
     */
    private function createFilter(string $fieldName, mixed $value, string $operator = SdkCriteriaOperator::EQUALS): SdkEntityQueryFilter
    {
        $filter = new SdkEntityQueryFilter();
        $filter->setType(SdkEntityQueryFilterType::LEAF);
        $filter->setOperator($operator);
        $filter->setFieldName($fieldName);
        $filter->setValue($value);
        return $filter;
    }

    /**
     * @inheritDoc
     */
    public function fetchById(int $spaceId, int $id): PaymentMethod
    {
        try {
            /** @var SdkPaymentMethodConfigurationService $service */
            $service = $this->provider->getService(SdkPaymentMethodConfigurationService::class);

            $config = $service->read($spaceId, $id);

            return $this->mapToPaymentMethod($config);
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch payment method from SDK.', [
                'paymentMethodId' => $id,
                'spaceId' => $spaceId,
                'exception' => $e,
            ]);
            throw new \RuntimeException(sprintf('Payment method %d not found.', $id), 0, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function fetchBySpaceId(int $spaceId, ?string $state = null): PaymentMethodCollection
    {
        try {
            /** @var SdkPaymentMethodConfigurationService $service */
            $service = $this->provider->getService(SdkPaymentMethodConfigurationService::class);

            $query = new SdkEntityQuery();

            if ($state !== null) {
                $query->setFilter($this->createFilter('state', $state));
            } else {
                // By default, we exclude deleted payment methods as they are usually not relevant
                // for active operations.
                $query->setFilter($this->createFilter('state', SdkCreationEntityState::DELETED, SdkCriteriaOperator::NOT_EQUALS));
            }

            $results = $service->search($spaceId, $query);

            return new PaymentMethodCollection(...array_map([$this, 'mapToPaymentMethod'], $results));
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch payment methods from SDK.', [
                'spaceId' => $spaceId,
                'exception' => $e,
            ]);
            throw $e;
        }
    }

}
