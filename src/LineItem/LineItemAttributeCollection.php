<?php

declare(strict_types=1);

namespace VRPayment\PluginCore\LineItem;

use VRPayment\PluginCore\SharedKernel\AbstractCollection;

/**
 * Strictly typed, iterable collection of {@see LineItemAttribute} value objects.
 *
 * @extends AbstractCollection<LineItemAttribute>
 */
final class LineItemAttributeCollection extends AbstractCollection
{
    public function __construct(LineItemAttribute ...$items)
    {
        $this->items = array_values($items);
    }

    public function first(): ?LineItemAttribute
    {
        return $this->items[0] ?? null;
    }
}
