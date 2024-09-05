<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Infrastructure\File;

use Smoothie\ResumeExporter\Domain\Mapping\MappingStrategy;

class NoOpStrategy implements MappingStrategy
{
    public function translate(array $map, array $from, array $settings): array
    {
        return $from;
    }

    public function normalize(array $map, array $from, array $settings): array
    {
        return $from;
    }

    public function validate(array $map, array $from, array $settings): void
    {
    }
}
