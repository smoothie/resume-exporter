<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Infrastructure\Mapping;

use Smoothie\ResumeExporter\Domain\Mapping\Exceptions\InvalidParentFromItemFormatException;
use Smoothie\ResumeExporter\Domain\Mapping\Exceptions\UnableToFindParentFromItemException;
use Smoothie\ResumeExporter\Domain\Mapping\MapItem;
use Smoothie\ResumeExporter\Domain\Mapping\MapItemRepository;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class PropertyAccessMapItemRepository implements MapItemRepository
{
    public function __construct(private readonly PropertyAccessorInterface $propertyAccessor)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function findCountOfParentFromItem(MapItem $mapItem, array $from): int
    {
        $items = explode(
            separator: PropertyAccessStrategy::WILDCARD,
            string: $mapItem->fromItem(),
            limit: 2,
        );

        if ($this->propertyAccessor->isReadable(objectOrArray: $from, propertyPath: $items[0]) === false) {
            throw new UnableToFindParentFromItemException(
                toItem: $mapItem->toItem(),
                fromItem: $mapItem->fromItem(),
                from: $from,
            );
        }

        $parentFromItem = $this->propertyAccessor->getValue(objectOrArray: $from, propertyPath: $items[0]);
        if (\is_array($parentFromItem) === false || array_is_list($parentFromItem) === false) {
            throw new InvalidParentFromItemFormatException(
                parentItem: $items[0],
                parentItemValue: $parentFromItem,
                from: $from,
            );
        }

        return \count($parentFromItem);
    }
}
