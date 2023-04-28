<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Infrastructure\File;

use Smoothie\ResumeExporter\Domain\Mapping\Exceptions\UnableToParseJsonException;
use Smoothie\ResumeExporter\Domain\Mapping\FilesystemContract;

class Filesystem implements FilesystemContract
{
    public function __construct(private Filesystem $filesystem)
    {
    }

    public function exists(string $path): bool
    {
        return $this->filesystem->exists(path: $path);
    }

    public function isAbsolutePath(string $path): bool
    {
        return $this->filesystem->isAbsolutePath(path: $path);
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

        return (array)$result;
    }
}
