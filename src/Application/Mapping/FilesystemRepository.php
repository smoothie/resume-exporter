<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Application\Mapping;

interface FilesystemRepository
{
    public function save(string $outputPath, string $outputData): void;
}
