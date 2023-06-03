<?php

declare(strict_types=1);

namespace Smoothie\Tests\ResumeExporter\Doubles\Dummies\Mapping;

use Smoothie\ResumeExporter\Domain\Mapping\FilesystemRepository as ReadFilesystemRepositoryContract;

class ReadFilesystemRepository implements ReadFilesystemRepositoryContract
{
    public function exists(string $path): bool
    {
        return false;
    }

    public function isAbsolutePath(string $path): bool
    {
        return false;
    }

    public function getFileContents(string $path): string
    {
        return '';
    }

    public function getJsonContents(string $path): array
    {
        return [];
    }
}
