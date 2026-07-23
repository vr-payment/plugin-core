<?php

declare(strict_types=1);

namespace VRPayment\PluginCore\Transaction\Invoice\Exception;

use VRPayment\PluginCore\SharedKernel\AbstractDomainException;

/**
 * Base exception for errors that occur while reading transaction invoices.
 */
class InvoiceException extends AbstractDomainException
{
}
