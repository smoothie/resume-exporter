<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Domain\Mapping;

use Smoothie\ResumeExporter\Domain\Mapping\Exceptions\InvalidParentFromItemFormatException;
use Smoothie\ResumeExporter\Domain\Mapping\Exceptions\UnableToFindParentFromItemException;

interface MapItemRepository
{
    /**
     * @throws UnableToFindParentFromItemException when from does not contain a from item
     * @throws InvalidParentFromItemFormatException when parent item is not a list
     */
    public function findCountOfParentFromItem(MapItem $mapItem, array $from): int;
}
