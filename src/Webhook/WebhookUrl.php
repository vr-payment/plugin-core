<?php

declare(strict_types=1);

namespace VRPayment\PluginCore\Webhook;

use VRPayment\PluginCore\SharedKernel\JsonStringableTrait;

class WebhookUrl
{
    use JsonStringableTrait;

    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $url,
        public readonly int $state,
    ) {
    }
}
