<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Domain\Mapping\Exceptions;

use Smoothie\ResumeExporter\Domain\ExceptionContract;

class UnableToParseJsonException extends \Exception implements ExceptionContract
{
    public function __construct(
        private readonly string $path,
        private readonly \Throwable $previousException,
    ) {
        $message = 'UnableToParseJsonException: Unable to parse JSON.';

        parent::__construct(message: $message, code: static::CODE_UNABLE_TO_PARSE_JSON, previous: $previousException);
    }

    /**
     * @return string[]
     *
     * @psalm-return array{path: string}
     */
    public function getContext(): array
    {
        return [
            'path' => $this->path,
        ];
    }
}
