<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Domain\Mapping;

interface OutputMapping
{
    public function translateFromCanonical(Output $output): array;
}
