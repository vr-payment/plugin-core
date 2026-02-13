<?php

declare(strict_types=1);

namespace MyPlugin\ExampleWebhookImplementation\Transaction;

use VRPayment\PluginCore\Log\LoggerInterface;
use VRPayment\PluginCore\Webhook\Command\WebhookCommandInterface;
use VRPayment\PluginCore\Webhook\Listener\WebhookListenerInterface;
use VRPayment\PluginCore\Webhook\WebhookContext;

class TransactionStateChangeListener implements WebhookListenerInterface
{
    public function __construct(private readonly LoggerInterface $logger)
    {}

    public function getCommand(WebhookContext $context): WebhookCommandInterface
    {
        return new UpdateTransactionStateCommand($context, $this->logger);
    }
}
