<?php

declare(strict_types=1);

namespace VRPayment\PluginCore\Webhook\Exception;

use VRPayment\PluginCore\SharedKernel\AbstractDomainException;

/**
 * Thrown when a webhook processing step is skipped intentionally.
 */
class SkippedStepException extends AbstractDomainException
{
}
