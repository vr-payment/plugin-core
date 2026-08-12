<?php

declare(strict_types=1);

namespace VRPayment\PluginCore\Sdk\WebServiceAPIV2;

use VRPayment\PluginCore\Localization\LocalizedString;
use VRPayment\PluginCore\Log\DomainLoggerTrait;
use VRPayment\PluginCore\Log\LogContext;
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
#[LogContext(domain: 'webhook')]
class WebhookSignatureGateway implements WebhookSignatureGatewayInterface
{
    use DomainLoggerTrait;
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
        LoggerInterface $logger,
    ) {
        $this->initializeLogger($logger);
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
        } catch (\Throwable $e) {
            // TODO: Include spaceId and transactionId in log context when available
            $this->logger->error(
                'Webhook signature validation failed.',
                [
                    'errorMessage' => $e->getMessage(),
                    'exception' => $e,
                ],
            );
            throw SdkProvider::wrapException(
                $e,
                WebhookSignatureValidationException::class,
                'isContentValid',
                [],
                'Webhook signature validation failed.',
            );
        }
    }
}
