<?php

declare(strict_types=1);

namespace VRPayment\PluginCore\PaymentMethod\Exception;

use VRPayment\PluginCore\SharedKernel\AbstractDomainException;

/**
 * Base exception for errors that occur while fetching payment methods.
 */
class PaymentMethodException extends AbstractDomainException
{
}
