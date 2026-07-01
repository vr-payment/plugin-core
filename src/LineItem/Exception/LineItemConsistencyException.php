<?php

declare(strict_types=1);

namespace VRPayment\PluginCore\LineItem\Exception;

use VRPayment\PluginCore\SharedKernel\AbstractDomainException;

/**
 * Thrown when line item totals cannot be reconciled with the expected grand total.
 */
class LineItemConsistencyException extends AbstractDomainException
{
}
