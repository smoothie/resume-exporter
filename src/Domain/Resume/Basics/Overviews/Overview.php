<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Domain\Resume\Basics\Overviews;

use Webmozart\Assert\Assert;

/**
 * @private
 *
 * @deprecated Will be removed as soon as we support custom fields in JSONResume
 */
class Overview
{
    /**
     * @param Item[] $items
     */
    public function __construct(
        private readonly array $items,
    ) {
        Assert::allIsInstanceOf(value: $items, class: Item::class);
    }

    public function items(): array
    {
        return $this->items;
    }
}
