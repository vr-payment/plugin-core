<?php

declare(strict_types=1);

namespace VRPayment\PluginCore\Transaction;

use VRPayment\PluginCore\Localization\LocalizedString;
use VRPayment\PluginCore\Log\DomainLoggerTrait;
use VRPayment\PluginCore\Log\LogContext;
use VRPayment\PluginCore\Log\LoggerInterface;
use VRPayment\PluginCore\Token\Exception\MissingTokenException;
use VRPayment\PluginCore\Transaction\Exception\TransactionException;
use VRPayment\PluginCore\Transaction\Transaction;
use VRPayment\PluginCore\Transaction\TransactionContext;
use VRPayment\PluginCore\Transaction\TransactionService;

/**
 * Service for handling recurring transactions.
 */
#[LogContext(domain: 'transaction', subdomain: 'recurring')]
class RecurringTransactionService
{
    use DomainLoggerTrait;
    public function __construct(
        private readonly TransactionService $transactionService,
        private readonly RecurringTransactionGatewayInterface $recurringGateway,
        LoggerInterface $logger,
    ) {
        $this->initializeLogger($logger);
    }

    /**
     * Processes a recurring payment for an existing transaction.
     *
     * @param int $spaceId The space ID.
     * @param int $transactionId The transaction ID.
     * @return Transaction The processed transaction.
     * @throws \Throwable If processing fails.
     */
    public function processRecurringPayment(int $spaceId, int $transactionId): Transaction
    {
        $this->logger->debug("Processing recurring payment.", [
            'transactionId' => $transactionId,
            'spaceId' => $spaceId,
        ]);

        $originalTransaction = $this->transactionService->getTransaction($spaceId, $transactionId);

        // A token with stored payment credentials is required for recurring charges.
        // The original transaction must have been created with tokenizationMode = FORCE_CREATION
        // so the API automatically generates a token when the payment completes.
        if (!$originalTransaction->token) {
            $this->logger->error(
                "Transaction has no token. Recurring payments require the original transaction to have been created with tokenizationMode = FORCE_CREATION.",
                [
                    'transactionId' => $transactionId,
                ],
            );
            throw new MissingTokenException(
                "Transaction $transactionId has no token. "
                    . "The original transaction must be created with tokenizationMode = FORCE_CREATION "
                    . "to enable recurring payments.",
                new LocalizedString('The transaction has no token available for recurring payments.'),
            );
        }

        if ($originalTransaction->billingAddress === null) {
            throw new TransactionException(
                "Transaction $transactionId has no billing address.",
                new LocalizedString('The transaction is missing a billing address.'),
            );
        }

        $context = TransactionContext::fromTransaction($originalTransaction);

        $newTransaction = $this->transactionService->createTransaction($context);

        return $this->recurringGateway->processRecurringPayment($spaceId, $newTransaction->id);
    }
}
