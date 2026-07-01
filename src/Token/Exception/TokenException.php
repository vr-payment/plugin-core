<?php

declare(strict_types=1);

namespace VRPayment\PluginCore\Token\Exception;

use VRPayment\PluginCore\SharedKernel\AbstractDomainException;

/**
 * Thrown when token creation fails at the API or transport level.
 */
class TokenException extends AbstractDomainException
{
}
