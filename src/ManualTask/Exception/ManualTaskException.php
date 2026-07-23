<?php

declare(strict_types=1);

namespace VRPayment\PluginCore\ManualTask\Exception;

use VRPayment\PluginCore\SharedKernel\AbstractDomainException;

/**
 * Thrown when a manual task operation fails at the API or transport level.
 */
class ManualTaskException extends AbstractDomainException
{
}
