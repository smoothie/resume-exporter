<?php

declare(strict_types=1);

namespace Smoothie\Tests\ResumeExporter\Doubles\Dummies\Mapping;

use Smoothie\ResumeExporter\Domain\Mapping\MappingStrategy;

class NullMappingStrategy implements MappingStrategy
{
    public function translate(array $map, array $from): array
    {
        return [];
    }

    public function normalize(array $map, array $from): array
    {
        return [];
    }

    public function validate(array $map, array $from): void
    {
    }
}
