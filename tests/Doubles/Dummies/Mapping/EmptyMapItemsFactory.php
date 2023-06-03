<?php

declare(strict_types=1);

namespace Smoothie\Tests\ResumeExporter\Doubles\Dummies\Mapping;

use Smoothie\ResumeExporter\Domain\Mapping\MapItem;
use Smoothie\ResumeExporter\Domain\Mapping\MapItems;
use Smoothie\ResumeExporter\Domain\Mapping\MapItemsFactory;

class EmptyMapItemsFactory implements MapItemsFactory
{
    public function createMapItems(array $map, array $settings = []): MapItems
    {
        return new MapItems(items: []);
    }

    public function createMapItem(string $fromItem, string $toItem, bool $isArray, int $depth): MapItem
    {
        return new MapItem(fromItem: '', toItem: '', depth: 0, isArray: false);
    }

    public function fromArray(array $mapItems): MapItems
    {
        return new MapItems(items: []);
    }
}
