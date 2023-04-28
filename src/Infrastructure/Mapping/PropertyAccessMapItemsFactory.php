<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Infrastructure\Mapping;

use Smoothie\ResumeExporter\Domain\Mapping\Exceptions\MismatchedMapItemDepthException;
use Smoothie\ResumeExporter\Domain\Mapping\MapItem;
use Smoothie\ResumeExporter\Domain\Mapping\MapItems;
use Smoothie\ResumeExporter\Domain\Mapping\MapItemsFactory;
use Webmozart\Assert\Assert;

class PropertyAccessMapItemsFactory implements MapItemsFactory
{
    /**
     * {@inheritDoc}
     */
    public function createMapItems(array $map): MapItems
    {
        $arrayItems = [];
        $noneArrayItems = [];

        foreach ($map as $fromItem => $toItem) {
            $isFromItemArray = str_contains($fromItem, PropertyAccessStrategy::WILDCARD);
            $isToItemArray = str_contains($toItem, PropertyAccessStrategy::WILDCARD);

            if ($isFromItemArray === false && $isToItemArray === false) {
                $mapItem = $this->createMapItem(
                    fromItem: $fromItem,
                    toItem: $toItem,
                );

                $noneArrayItems[] = $mapItem;

                continue;
            }

            $fromItemDepth = mb_substr_count($fromItem, PropertyAccessStrategy::WILDCARD);
            $toItemDepth = mb_substr_count($toItem, PropertyAccessStrategy::WILDCARD);
            if ($fromItemDepth !== $toItemDepth) {
                throw new MismatchedMapItemDepthException(
                    toItem: $toItem,
                    fromItem: $fromItem,
                    toItemDepth: $toItemDepth,
                    fromItemDepth: $fromItemDepth,
                    map: $map,
                );
            }

            $mapItem = $this->createMapItem(
                fromItem: $fromItem,
                toItem: $toItem,
                isArray: true,
                depth: $fromItemDepth,
            );

            $arrayItems[] = $mapItem;
        }

        $items = array_merge($noneArrayItems, $arrayItems);

        return new MapItems(
            items: $items,
        );
    }

    public function createMapItem(
        string $fromItem,
        string $toItem,
        bool $isArray = false,
        int $depth = 0,
    ): MapItem {
        return new MapItem(
            fromItem: $fromItem,
            toItem: $toItem,
            depth: $depth,
            isArray: $isArray,
        );
    }

    public function fromArray(array $mapItems): MapItems
    {
        Assert::allIsInstanceOf(value: $mapItems, class: MapItem::class);

        return new MapItems($mapItems);
    }
}
