<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Domain\Mapping;

interface MappingStrategy
{
    public function translate(array $map, array $from): array;

    public function normalize(array $map, array $from): array;

    public function validate(array $map, array $from): void;
}
