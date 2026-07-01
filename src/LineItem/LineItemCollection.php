<?php

declare(strict_types=1);

namespace VRPayment\PluginCore\LineItem;

use VRPayment\PluginCore\SharedKernel\AbstractCollection;

/**
 * Strictly typed, iterable collection of {@see LineItem} entities.
 *
 * @extends AbstractCollection<LineItem>
 */
final class LineItemCollection extends AbstractCollection
{
    public function __construct(LineItem ...$items)
    {
        $this->items = array_values($items);
    }

    public function first(): ?LineItem
    {
        return $this->items[0] ?? null;
    }
}
