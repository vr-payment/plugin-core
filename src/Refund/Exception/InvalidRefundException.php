<?php

declare(strict_types=1);

namespace VRPayment\PluginCore\Refund\Exception;

use VRPayment\PluginCore\SharedKernel\AbstractDomainException;

/**
 * Thrown when a refund request violates a business rule before reaching the gateway.
 *
 * Always terminal: the request itself is invalid, so retrying it unchanged
 * will fail again.
 */
class InvalidRefundException extends AbstractDomainException
{
    protected bool $retryable = false;
}
