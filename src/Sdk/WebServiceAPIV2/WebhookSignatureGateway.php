<?php

declare(strict_types=1);

namespace VRPayment\PluginCore\Sdk\WebServiceAPIV2;

use VRPayment\PluginCore\Log\LoggerInterface;
use VRPayment\PluginCore\Sdk\SdkProvider;
use VRPayment\PluginCore\Webhook\Exception\WebhookSignatureValidationException;
use VRPayment\PluginCore\Webhook\WebhookSignatureGatewayInterface;
use VRPayment\Sdk\Service\WebhookEncryptionKeysService as SdkWebhookEncryptionKeysService;

/**
 * Class WebhookSignatureGateway
 *
 * Implementation of the WebhookSignatureGatewayInterface using the VRPayment SDK V2.
 */
class WebhookSignatureGateway implements WebhookSignatureGatewayInterface
{
    /**
     * @var SdkWebhookEncryptionKeysService
     */
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
     * Validates the payload signature.
     *
     * @param string $signatureHeader The signature string from the request headers.
     * @param string $payload The raw request body content.
     * @return bool True if the signature is valid, false otherwise.
     * @throws WebhookSignatureValidationException If signature validation fails due to key/API errors.
     */
    public function validate(string $signatureHeader, string $payload): bool
    {
        try {
            return (bool)$this->webhookEncryptionKeysService->isContentValid($signatureHeader, $payload);
        } catch (\Exception $e) {
            // TODO: Include spaceId and transactionId in log context when available
            $this->logger->error(
                'Webhook signature validation failed: {errorMessage}',
                [
                    'errorMessage' => $e->getMessage(),
                    'exception' => $e,
                ],
            );
            throw new WebhookSignatureValidationException(
                "Webhook signature validation failed: " . $e->getMessage(),
                null,
                0,
                $e,
            );
        }
    }
}
