<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Infrastructure\File;

use Smoothie\ResumeExporter\Application\Mapping\FilesystemRepository as WriteFilesystemContract;
use Smoothie\ResumeExporter\Domain\Mapping\Exceptions\UnableToParseJsonException;
use Smoothie\ResumeExporter\Domain\Mapping\FilesystemRepository as ReadFilesystemContract;
use Symfony\Component\Filesystem\Filesystem;

class FilesystemRepository implements ReadFilesystemContract, WriteFilesystemContract
{
    public function __construct(private readonly Filesystem $filesystem)
    {
    }

    public function exists(string $path): bool
    {
        return $this->filesystem->exists(files: $path);
    }

    public function isAbsolutePath(string $path): bool
    {
        return $this->filesystem->isAbsolutePath(file: $path);
    }

    public function getFileContents(string $path): string
    {
        return file_get_contents(filename: $path);
    }

    public function getJsonContents(string $path): array
    {
        $contents = $this->getFileContents(path: $path);

        try {
            $result = json_decode(json: $contents, associative: true, flags: \JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new UnableToParseJsonException(path: $path, previousException: $exception);
        }

        return (array) $result;
    }

    public function save(string $outputPath, string $outputData): void
    {
        file_put_contents(filename: $outputPath, data: $outputData);
    }
}
