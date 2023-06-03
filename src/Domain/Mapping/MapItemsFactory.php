<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Domain\Mapping;

use Smoothie\ResumeExporter\Domain\Mapping\Exceptions\MismatchedMapItemDepthException;

interface MapItemsFactory
{
    /**
     * @throws MismatchedMapItemDepthException when a from and to items have different amount of arrays
     */
    public function createMapItems(array $map, array $settings): MapItems;

    /**
     * @param MapItem[] $mapItems
     */
    public function fromArray(array $mapItems): MapItems;
}
