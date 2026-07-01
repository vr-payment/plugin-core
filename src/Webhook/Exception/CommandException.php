<?php

declare(strict_types=1);

namespace VRPayment\PluginCore\Webhook\Exception;

use VRPayment\PluginCore\SharedKernel\AbstractDomainException;

/**
 * Base exception for errors that occur during a webhook command execution.
 */
class CommandException extends AbstractDomainException
{
}
