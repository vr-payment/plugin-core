<?php

declare(strict_types=1);

namespace VRPayment\PluginCore\Refund\Exception;

use VRPayment\PluginCore\SharedKernel\AbstractDomainException;

/**
 * Thrown when a refund request violates a business rule before reaching the gateway.
 */
class InvalidRefundException extends AbstractDomainException
{
}
