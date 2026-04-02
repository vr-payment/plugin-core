<?php

declare(strict_types=1);

namespace VRPayment\PluginCore\Sdk\SdkV2;

use VRPayment\PluginCore\Log\LoggerInterface;
use VRPayment\PluginCore\Sdk\SdkProvider;
use VRPayment\PluginCore\Webhook\WebhookSignatureGatewayInterface;
use VRPayment\Sdk\Service\WebhookEncryptionKeysService as SdkWebhookEncryptionKeysService;

/**
 * Class WebhookSignatureGateway
 *
 * Implementation of the WebhookSignatureGatewayInterface using the VRPayment SDK V2.
 */
class WebhookSignatureGateway implements WebhookSignatureGatewayInterface
{
    private SdkWebhookEncryptionKeysService $webhookEncryptionKeysService;

    /**
     * WebhookSignatureGateway constructor.
     *
     * @param SdkProvider $sdkProvider
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly SdkProvider $sdkProvider,
        private readonly LoggerInterface $logger,
    ) {
        $this->webhookEncryptionKeysService = $this->sdkProvider->getService(SdkWebhookEncryptionKeysService::class);
    }

    /**
     * @inheritDoc
     */
    public function validate(string $signatureHeader, string $payload): bool
    {
        try {
            return $this->webhookEncryptionKeysService->isContentValid($signatureHeader, $payload);
        } catch (\Exception $e) {
            $this->logger->warning("Webhook signature validation failed: " . $e->getMessage());
            return false;
        }
    }
}
