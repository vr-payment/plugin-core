<?php

declare(strict_types=1);

namespace VRPayment\PluginCore\Document\Exception;

use VRPayment\PluginCore\SharedKernel\AbstractDomainException;

/**
 * Base exception for errors that occur while retrieving rendered documents.
 */
class DocumentException extends AbstractDomainException
{
}
