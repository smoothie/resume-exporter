<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Domain\Mapping\Exceptions;

use Smoothie\ResumeExporter\Domain\ExceptionContract;

class UnableToReplaceDotNotationException extends \Exception implements ExceptionContract
{
    public function __construct(
        private readonly string $haystack,
        private readonly string $needle,
        private readonly string $replace,
    ) {
        $message = 'UnableToReplaceDotNotationException: Needle not found in haystack.';

        parent::__construct(
            message: $message,
            code: static::CODE_UNABLE_TO_REPLACE_DOT_NOTATION,
        );
    }

    public function getContext(): array
    {
        return [
            'haystack' => $this->haystack,
            'needle' => $this->needle,
            'replace' => $this->replace,
        ];
    }
}
