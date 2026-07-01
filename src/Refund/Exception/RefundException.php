<?php

declare(strict_types=1);

namespace VRPayment\PluginCore\Refund\Exception;

use VRPayment\PluginCore\SharedKernel\AbstractDomainException;

/**
 * Thrown when a refund operation fails at the API or transport level.
 */
class RefundException extends AbstractDomainException
{
}
