<?php

declare(strict_types=1);

namespace Smoothie\Tests\ResumeExporter\Doubles\Dummies\Mapping;

use Smoothie\ResumeExporter\Application\Mapping\FilesystemRepository as WriteFilesystemRepositoryContract;

class WriteFilesystemRepository implements WriteFilesystemRepositoryContract
{
    public function save(string $outputPath, mixed $outputData): void
    {
    }
}
