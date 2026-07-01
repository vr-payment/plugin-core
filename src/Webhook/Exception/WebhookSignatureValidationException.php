<?php

declare(strict_types=1);

namespace VRPayment\PluginCore\Webhook\Exception;

use VRPayment\PluginCore\SharedKernel\AbstractDomainException;

/**
 * Thrown when webhook signature verification fails due to API or network errors.
 */
class WebhookSignatureValidationException extends AbstractDomainException
{
}
