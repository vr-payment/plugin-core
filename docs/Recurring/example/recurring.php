<?php

namespace MyPlugin\ExampleRecurringImplementation;

/**
 * Recurring Payment Example
 * 
 * This script demonstrates how to trigger a recurring payment (MIT) on an existing transaction.
 * 
 * USAGE:
 * php recurring.php [session_file_or_dir] [transaction_id]
 * 
 * See src/TransactionIdLoader.php for argument handling details.
 */

error_reporting(E_ALL & ~E_DEPRECATED);

require_once __DIR__ . '/../../examples/Common/bootstrap.php';

use VRPayment\PluginCore\Sdk\SdkProvider;
use VRPayment\PluginCore\Sdk\SdkV2\TransactionCompletionGateway;
use VRPayment\PluginCore\Sdk\SdkV2\TransactionGateway;
use VRPayment\PluginCore\Sdk\SdkV2\RecurringTransactionGateway;
use VRPayment\PluginCore\Settings\Settings;
use VRPayment\PluginCore\Transaction\RecurringTransactionService;
use VRPayment\PluginCore\Transaction\TransactionService;
use VRPayment\PluginCore\Token\TokenService;
use VRPayment\PluginCore\Sdk\SdkV2\TokenGateway;
use VRPayment\PluginCore\LineItem\LineItemConsistencyService;
use VRPayment\PluginCore\Examples\Common\TransactionIdLoader;

// 1. Initialize Services via Bootstrap
$common = require __DIR__ . '/../../examples/Common/bootstrap.php';

$spaceId = $common['spaceId'];
$userId = $common['userId'];
$apiSecret = $common['apiSecret'];
$logger = $common['logger'];
$settings = $common['settings'];
$sdkProvider = $common['sdkProvider'];

// 2. Load Transaction ID
try {
    $transactionId = TransactionIdLoader::load($argv);
} catch (\Exception $e) {
    exit(1);
}

// 3. Setup Services
$transactionGateway = new TransactionGateway($sdkProvider, $logger, $settings);
$recurringGateway = new RecurringTransactionGateway($sdkProvider, $logger);
$consistencyService = new LineItemConsistencyService($settings, $logger);

$transactionService = new TransactionService($transactionGateway, $consistencyService, $logger);
$tokenService = new TokenService(new TokenGateway($sdkProvider, $logger), $logger);

$recurringService = new RecurringTransactionService(
    $transactionService,
    $recurringGateway,
    $tokenService,
    $logger
);

echo "Attempting to Process Recurring Payment for Transaction ID: $transactionId\n";

// Pre-check: Inspect original transaction's token
$originalTx = $transactionService->getTransaction((int)$spaceId, $transactionId);
echo "Original Transaction State: " . $originalTx->state->value . "\n";
if ($originalTx->token) {
    echo "Token Found: ID=" . $originalTx->token->id . " State=" . $originalTx->token->state->value . "\n";
} else {
    echo "Token: None (will attempt to create one)\n";
}

// 4. Execute Recurring Payment
try {
    $newTransaction = $recurringService->processRecurringPayment((int)$spaceId, $transactionId);

    echo "---------------------------------------------------\n";
    echo "RECURRING PAYMENT PROCESSED\n";
    echo "---------------------------------------------------\n";
    echo "New Transaction ID: " . $newTransaction->id . "\n";
    echo "New State:          " . $newTransaction->state->value . "\n";
    echo "---------------------------------------------------\n";
} catch (\Throwable $e) {
    echo "---------------------------------------------------\n";
    echo "RECURRING PAYMENT FAILED\n";
    echo "---------------------------------------------------\n";
    echo "Reason: " . $e->getMessage() . "\n";
    echo "Hint: Ensure the original transaction was successful and has a valid token.\n";
    echo "---------------------------------------------------\n";
    exit(1);
}
