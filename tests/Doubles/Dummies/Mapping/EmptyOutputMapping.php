<?php

declare(strict_types=1);

namespace Smoothie\Tests\ResumeExporter\Doubles\Dummies\Mapping;

use Smoothie\ResumeExporter\Domain\Mapping\Output;
use Smoothie\ResumeExporter\Domain\Mapping\OutputMapping;

class EmptyOutputMapping implements OutputMapping
{
    public function translateFromCanonical(Output $output): array
    {
        return [];
    }
}
