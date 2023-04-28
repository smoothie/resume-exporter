<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Domain\Mapping;

use Smoothie\ResumeExporter\Domain\Resume\Resume;

class Output
{
    public function __construct(
        private string $outputSource,
        private string $outputFormat,
        private string $mapSource,
        private Resume $canonical,
        private array $map,
    ) {
    }

    public function outputSource(): string
    {
        return $this->outputSource;
    }

    public function outputFormat(): string
    {
        // todo add enum (PDF + Doctrine)
        return $this->outputFormat;
    }

    public function mapSource(): string
    {
        return $this->mapSource;
    }

    public function getCanonical(): Resume
    {
        return $this->canonical;
    }

    public function getMap(): array
    {
        return $this->map;
    }
}
