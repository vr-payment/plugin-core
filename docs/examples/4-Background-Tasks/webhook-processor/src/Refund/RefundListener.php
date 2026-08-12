<?php
declare(strict_types=1);
namespace MyPlugin\ExampleWebhookImplementation\Refund;
use VRPayment\PluginCore\Webhook\Listener\WebhookListenerInterface;
use VRPayment\PluginCore\Webhook\Command\WebhookCommandInterface;
use VRPayment\PluginCore\Webhook\WebhookContext;
use VRPayment\PluginCore\Log\LoggerInterface;

// 📖 Concept documentation: See docs/4-Background-Tasks/Webhook-Processor.md

class RefundListener implements WebhookListenerInterface {
    public function __construct(private readonly LoggerInterface $logger) {}
    public function getCommand(WebhookContext $context): WebhookCommandInterface {
        return new SuccessfulCommand($context, $this->logger);
    }
}
