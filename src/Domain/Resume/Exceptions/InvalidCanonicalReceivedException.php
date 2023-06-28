<?php

declare(strict_types=1);

namespace Smoothie\ResumeExporter\Domain\Resume\Exceptions;

use Smoothie\ResumeExporter\Domain\ExceptionContract;

class InvalidCanonicalReceivedException extends \Exception implements ExceptionContract
{
    public function __construct(
        private readonly array $violations,
        private readonly array $input,
    ) {
        $message = 'InvalidCanonicalReceivedException: Unable to validate canonical array.';

        parent::__construct(message: $message, code: static::CODE_INVALID_CANONICAL_RECEIVED);
    }

    /**
     * @return array[]
     *
     * @psalm-return array{violations: array, input: array}
     */
    public function getContext(): array
    {
        return [
            'violations' => $this->violations,
            'input' => $this->input,
        ];
    }
}
