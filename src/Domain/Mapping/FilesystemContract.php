<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Domain\Mapping;

use Smoothie\ResumeExporter\Domain\Mapping\Exceptions\UnableToParseJsonException;

interface FilesystemContract
{
    public function exists(string $path): bool;

    public function isAbsolutePath(string $path): bool;

    public function getFileContents(string $path): string;

    /**
     * @throws UnableToParseJsonException when file is not a valid JSON
     */
    public function getJsonContents(string $path): array;
}
