<?php

declare(strict_types=1);

namespace VRPayment\PluginCore\Sdk\WebServiceAPIV1;

use VRPayment\PluginCore\Log\DomainLoggerTrait;
use VRPayment\PluginCore\Log\LogContext;
use VRPayment\PluginCore\Log\LoggerInterface;
use VRPayment\PluginCore\Sdk\SdkProvider;
use VRPayment\PluginCore\Sdk\TransactionMapperTrait;
use VRPayment\PluginCore\Transaction\Exception\TransactionException;
use VRPayment\PluginCore\Transaction\RecurringTransactionGatewayInterface;
use VRPayment\PluginCore\Transaction\Transaction;
use VRPayment\Sdk\Service\TransactionService as SdkTransactionService;

/**
 * Class RecurringTransactionGateway
 *
 * Implementation of the RecurringTransactionGatewayInterface using the SDK V1.
 */
#[LogContext(domain: 'transaction', subdomain: 'recurring')]
class RecurringTransactionGateway implements RecurringTransactionGatewayInterface
{
    use DomainLoggerTrait;
    use TransactionMapperTrait;

    /**
     * @var SdkTransactionService The SDK transaction service.
     */
    private SdkTransactionService $transactionService;

    /**
     * RecurringTransactionGateway constructor.
     *
     * @param SdkProvider $sdkProvider The SDK provider.
     * @param LoggerInterface $logger The logger instance.
     */
    public function __construct(
        private readonly SdkProvider $sdkProvider,
        LoggerInterface $logger,
    ) {
        $this->initializeLogger($logger);
        $this->transactionService = $this->sdkProvider->getService(SdkTransactionService::class);
    }

    /**
     * Processes a recurring payment for an existing transaction.
     *
     * @param int $spaceId The space ID.
     * @param int $transactionId The transaction ID.
     * @return Transaction The processed transaction.
     * @throws \Exception If the processing fails.
     */
    public function processRecurringPayment(int $spaceId, int $transactionId): Transaction
    {
        $this->logger->debug("Processing recurring payment.", [
            'transactionId' => $transactionId,
            'spaceId' => $spaceId,
        ]);

        try {
            $sdkTransaction = $this->transactionService->processWithoutUserInteraction($spaceId, $transactionId);
            $this->logger->debug("Recurring payment processed successfully.", [
                'transactionId' => $transactionId,
                'spaceId' => $spaceId,
            ]);

            return $this->mapToTransaction($sdkTransaction);
        } catch (\Throwable $e) {
            $this->logger->error("Failed to process recurring payment.", [
                'transactionId' => $transactionId,
                'spaceId' => $spaceId,
                'exception' => $e,
            ]);
            throw SdkProvider::wrapException(
                $e,
                TransactionException::class,
                'processWithoutUserInteraction',
                ['spaceId' => $spaceId, 'transactionId' => $transactionId],
                'The recurring payment could not be processed.',
            );
        }
    }
}
