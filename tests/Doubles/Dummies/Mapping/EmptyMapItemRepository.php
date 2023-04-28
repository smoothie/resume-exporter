<?php

declare(strict_types=1);

namespace Smoothie\Tests\ResumeExporter\Doubles\Dummies\Mapping;

use Smoothie\ResumeExporter\Domain\Mapping\MapItem;
use Smoothie\ResumeExporter\Domain\Mapping\MapItemRepository;

class EmptyMapItemRepository implements MapItemRepository
{
    public function findCountOfParentFromItem(MapItem $mapItem, array $from): int
    {
        return 0;
    }
}
