<?php

declare(strict_types=1);

namespace VRPayment\PluginCore\Sdk\WebServiceAPIV2;

use VRPayment\PluginCore\Localization\LocalizedString;
use VRPayment\PluginCore\Log\LoggerInterface;
use VRPayment\PluginCore\Refund\Exception\RefundException;
use VRPayment\PluginCore\Refund\Refund;
use VRPayment\PluginCore\Refund\RefundContext;
use VRPayment\PluginCore\Refund\RefundGatewayInterface;
use VRPayment\PluginCore\Refund\State as StateEnum;
use VRPayment\PluginCore\Sdk\DateTimeMapperTrait;
use VRPayment\PluginCore\Sdk\FailureReasonMapperTrait;
use VRPayment\PluginCore\Sdk\SdkProvider;
use VRPayment\Sdk\Model\LineItemReductionCreate as SdkLineItemReductionCreate;
use VRPayment\Sdk\Model\Refund as SdkRefund;
use VRPayment\Sdk\Model\RefundCreate as SdkRefundCreate;
use VRPayment\Sdk\Model\RefundType as SdkRefundType;
use VRPayment\Sdk\Service\RefundsService as SdkRefundService;

class RefundGateway implements RefundGatewayInterface
{
    use DateTimeMapperTrait;
    use FailureReasonMapperTrait;

    private SdkRefundService $sdkRefundService;

    public function __construct(
        private readonly SdkProvider $sdkProvider,
        private readonly LoggerInterface $logger,
    ) {
        $this->sdkRefundService = $this->sdkProvider->getService(SdkRefundService::class);
    }

    /**
     * @return Refund[]
     */
    public function findByTransaction(int $spaceId, int $transactionId): array
    {
        // V2 Search: using query string 'transaction.id:<id>'
        // SEARCH for refunds linked to the transaction using the 'query' filter.
        $query = "transaction.id:$transactionId";

        try {
            $sdkRefunds = $this->sdkRefundService->getPaymentRefundsSearch($spaceId, null, null, null, null, $query);
            $refunds = [];
            // Handle ListResponse or Array
            $items = (is_object($sdkRefunds) && method_exists($sdkRefunds, 'getData')) ? $sdkRefunds->getData() : (array)$sdkRefunds;
            foreach ($items as $sdkRefund) {
                $refunds[] = $this->mapToRefund($sdkRefund, $transactionId);
            }
            return $refunds;
        } catch (\Throwable $e) {
            $this->logger->error(
                'Failed to find refunds for transaction: {errorMessage}',
                [
                    'errorMessage' => $e->getMessage(),
                    'exception' => $e,
                    'spaceId' => $spaceId,
                    'transactionId' => $transactionId,
                ],
            );
            throw new RefundException(
                "Failed to find refunds for transaction {$transactionId}: " . $e->getMessage(),
                new LocalizedString($e->getMessage()),
                0,
                $e,
            );
        }
    }

    public function refund(int $spaceId, RefundContext $context): Refund
    {
        $this->logger->debug("Preparing refund for Transaction {$context->transactionId}", [
            'amount' => $context->amount,
            'spaceId' => $spaceId,
        ]);

        try {
            $sdkRefundCreate = new SdkRefundCreate();
            $sdkRefundCreate->setTransaction($context->transactionId);
            $sdkRefundCreate->setAmount($context->amount);
            $sdkRefundCreate->setMerchantReference($context->merchantReference);
            $sdkRefundCreate->setExternalId(uniqid((string)$context->transactionId . '-', true));
            $sdkRefundCreate->setType(match ($context->type->value) {
                'MERCHANT_INITIATED_ONLINE' => SdkRefundType::MERCHANT_INITIATED_ONLINE,
                'MERCHANT_INITIATED_OFFLINE' => SdkRefundType::MERCHANT_INITIATED_OFFLINE,
                'CUSTOMER_INITIATED_AUTOMATIC' => SdkRefundType::CUSTOMER_INITIATED_AUTOMATIC,
                'CUSTOMER_INITIATED_MANUAL' => SdkRefundType::CUSTOMER_INITIATED_MANUAL,
                default => SdkRefundType::MERCHANT_INITIATED_ONLINE,
            });

            if (!empty($context->lineItems)) {
                $sdkReductions = [];
                foreach ($context->lineItems as $item) {
                    $uniqueId = $item['uniqueId'];
                    $qty = $item['quantity'];
                    $amt = $item['amount'];

                    $this->logger->debug("Adding Reduction: ID=$uniqueId, Qty=$qty, Amt=$amt");

                    $sdkReduction = new SdkLineItemReductionCreate();
                    $sdkReduction->setLineItemUniqueId($uniqueId);
                    $sdkReduction->setQuantityReduction($qty);
                    $sdkReduction->setUnitPriceReduction($amt);
                    $sdkReductions[] = $sdkReduction;
                }
                $sdkRefundCreate->setReductions($sdkReductions);
            }

            $this->logger->debug("Refund Create Payload", [
                'amount' => $sdkRefundCreate->getAmount(),
                'reductions_count' => count($sdkRefundCreate->getReductions() ?? []),
            ]);

            $sdkRefund = $this->sdkRefundService->postPaymentRefunds($spaceId, $sdkRefundCreate);

            $this->logger->debug("Refund created successfully. ID: {$sdkRefund->getId()}, State: {$sdkRefund->getState()}");

            return $this->mapToRefund($sdkRefund, $context->transactionId);
        } catch (\Throwable $e) {
            $this->logger->error("Refund failed for Transaction {$context->transactionId}: {$e->getMessage()}", [
                'trace' => $e->getTraceAsString(),
            ]);
            throw new RefundException(
                "Unable to process refund: {$e->getMessage()}",
                new LocalizedString($e->getMessage()),
                0,
                $e,
            );
        }
    }

    private function mapToRefund(SdkRefund $sdkRefund, int $transactionId): Refund
    {
        $refund = new Refund();
        $refund->id = (int)$sdkRefund->getId();
        $refund->amount = (float)$sdkRefund->getAmount();
        $refund->transactionId = $transactionId;
        $refund->externalId = $sdkRefund->getExternalId();

        $refund->state = match ((string)$sdkRefund->getState()) {
            'CREATE' => StateEnum::CREATE,
            'SCHEDULED' => StateEnum::SCHEDULED,
            'PENDING' => StateEnum::PENDING,
            'MANUAL_CHECK' => StateEnum::MANUAL_CHECK,
            'FAILED' => StateEnum::FAILED,
            'SUCCESSFUL' => StateEnum::SUCCESSFUL,
            default => StateEnum::PENDING, // Safe fallback
        };

        $reason = $sdkRefund->getFailureReason();
        if ($reason !== null) {
            $refund->failureReason = $this->mapSdkFailureReason($reason);
        }

        $refund->createdOn = $this->toDateTimeImmutable($sdkRefund->getCreatedOn());
        $refund->failedOn = $this->toDateTimeImmutable($sdkRefund->getFailedOn());

        return $refund;
    }

}
