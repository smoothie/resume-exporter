<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Domain\Mapping;

use Smoothie\ResumeExporter\Domain\Resume\Resume;

class Output
{
    public function __construct(
        private readonly string $mapSource,
        private readonly string $outputPath,
        private readonly string $outputTemplatePath,
        private readonly OutputFormat $outputFormat,
        private readonly array $mapSettings,
        private readonly array $map,
        private readonly Resume $canonical,
    ) {
    }

    public function outputPath(): string
    {
        return $this->outputPath;
    }

    public function outputTemplatePath(): string
    {
        return $this->outputTemplatePath;
    }

    public function outputFormat(): OutputFormat
    {
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

    public function getMapSettings(): array
    {
        return $this->mapSettings;
    }
}
