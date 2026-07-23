<?php

declare(strict_types=1);

namespace VRPayment\PluginCore\Transaction\Completion\Exception;

use VRPayment\PluginCore\SharedKernel\AbstractDomainException;

/**
 * Base exception for errors that occur during capture (completion) or void operations.
 */
class CompletionException extends AbstractDomainException
{
}
